<?php

namespace Tests\Unit\ThemeCanvas;

use App\Services\CustomerMediaService;
use App\Services\ThemeCanvas\CustomerMediaResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * MEDIA-4: CustomerMediaResolver is the one path that turns a Builder prop
 * value into a safe, renderable URL — canonical {media_id}, legacy string,
 * or a value that must fail safely (missing/deleted/foreign/malformed).
 */
class CustomerMediaResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('customer_media_usages');
        Schema::dropIfExists('customer_media');
        (require database_path('migrations/2026_09_10_100000_create_customer_media_tables.php'))->up();
        (require database_path('migrations/2026_09_15_100000_add_description_to_customer_media_table.php'))->up();
        Storage::fake('uploads');
        config(['site.platform_site_id' => 301]);
    }

    private function resolver(): CustomerMediaResolver
    {
        return app(CustomerMediaResolver::class);
    }

    private function upload(string $name = 'photo.jpg'): \App\Models\CustomerMedia
    {
        return app(CustomerMediaService::class)->upload(UploadedFile::fake()->image($name));
    }

    public function test_legacy_string_value_passes_through_unchanged(): void
    {
        $this->assertSame('https://example.com/a.jpg', $this->resolver()->resolveValue('https://example.com/a.jpg'));
        $this->assertSame('/storage/theme-media/old.png', $this->resolver()->resolveValue('/storage/theme-media/old.png'));
    }

    public function test_canonical_media_id_resolves_to_the_real_public_url(): void
    {
        $media = $this->upload();
        $service = app(CustomerMediaService::class);

        $resolved = $this->resolver()->resolveValue(['media_id' => $media->uuid]);

        $this->assertSame($service->publicUrl($media), $resolved);
        $this->assertNotSame('', $resolved);
    }

    public function test_unknown_media_id_fails_safely_to_empty_string(): void
    {
        $this->assertSame('', $this->resolver()->resolveValue(['media_id' => (string) \Illuminate\Support\Str::uuid()]));
    }

    public function test_foreign_site_media_id_fails_safely_to_empty_string(): void
    {
        $media = $this->upload();
        config(['site.platform_site_id' => 999999]);

        $this->assertSame('', $this->resolver()->resolveValue(['media_id' => $media->uuid]));
    }

    public function test_soft_deleted_media_id_fails_safely_to_empty_string(): void
    {
        $service = app(CustomerMediaService::class);
        $media = $this->upload();
        $media->deleted_at = now();
        $media->save();

        $this->assertSame('', $this->resolver()->resolveValue(['media_id' => $media->uuid]));
        unset($service);
    }

    public function test_malformed_values_fail_safely_to_empty_string(): void
    {
        $resolver = $this->resolver();
        $this->assertSame('', $resolver->resolveValue([]));
        $this->assertSame('', $resolver->resolveValue(['media_id' => '']));
        $this->assertSame('', $resolver->resolveValue(['media_id' => 123]));
        $this->assertSame('', $resolver->resolveValue(null));
        $this->assertSame('', $resolver->resolveValue(['not_media_id' => 'x']));
    }

    public function test_resolves_every_known_simple_media_capable_prop(): void
    {
        $media = $this->upload();
        $service = app(CustomerMediaService::class);
        $url = $service->publicUrl($media);
        $resolver = $this->resolver();

        $cases = [
            'hero' => 'background_image',
            'section' => 'background_image',
            'image' => 'url',
            'hero_split' => 'image_url',
            'module:motion' => 'fallback_image',
        ];

        foreach ($cases as $type => $propKey) {
            $props = $resolver->resolveBlockProps($type, [$propKey => ['media_id' => $media->uuid], 'other' => 'untouched']);
            $this->assertSame($url, $props[$propKey], "expected {$type}.{$propKey} to resolve");
            $this->assertSame('untouched', $props['other']);
        }
    }

    public function test_unknown_block_type_and_props_pass_through_untouched(): void
    {
        $resolver = $this->resolver();
        $props = ['title' => 'x', 'url' => ['media_id' => 'whatever']];

        $result = $resolver->resolveBlockProps('heading', $props);

        $this->assertSame($props, $result);
    }

    public function test_resolves_gallery_items_array_storage_repeater(): void
    {
        $media = $this->upload();
        $service = app(CustomerMediaService::class);
        $url = $service->publicUrl($media);

        $props = $this->resolver()->resolveBlockProps('gallery', [
            'title' => 'گالری',
            'items' => [
                ['url' => ['media_id' => $media->uuid], 'alt' => 'one'],
                ['url' => 'https://external.example.com/legacy.jpg', 'alt' => 'legacy'],
            ],
        ]);

        $this->assertSame($url, $props['items'][0]['url']);
        $this->assertSame('one', $props['items'][0]['alt']);
        $this->assertSame('https://external.example.com/legacy.jpg', $props['items'][1]['url']);
    }

    public function test_resolves_team_cards_members_json_string_storage_repeater(): void
    {
        $media = $this->upload();
        $service = app(CustomerMediaService::class);
        $url = $service->publicUrl($media);

        $membersJson = json_encode([
            ['name' => 'سارا', 'role' => 'مدیر', 'image' => ['media_id' => $media->uuid]],
        ], JSON_UNESCAPED_UNICODE);

        $props = $this->resolver()->resolveBlockProps('team_cards', [
            'title' => 'تیم ما',
            'members' => $membersJson,
        ]);

        $this->assertIsString($props['members']);
        $decoded = json_decode($props['members'], true);
        $this->assertSame($url, $decoded[0]['image']);
        $this->assertSame('سارا', $decoded[0]['name']);
    }

    /**
     * team_cards.members is declared 'storage' => 'json' in BlockRegistry —
     * the resolver's output always normalizes to that canonical shape
     * (matching what the shared renderer's json_decode-if-needed handling
     * expects), even if a caller happens to hand it a native array.
     */
    public function test_resolves_team_cards_members_when_input_is_a_native_array(): void
    {
        $media = $this->upload();
        $service = app(CustomerMediaService::class);
        $url = $service->publicUrl($media);

        $props = $this->resolver()->resolveBlockProps('team_cards', [
            'members' => [
                ['name' => 'علی', 'image' => ['media_id' => $media->uuid]],
            ],
        ]);

        $this->assertIsString($props['members']);
        $decoded = json_decode($props['members'], true);
        $this->assertSame($url, $decoded[0]['image']);
        $this->assertSame('علی', $decoded[0]['name']);
    }
}

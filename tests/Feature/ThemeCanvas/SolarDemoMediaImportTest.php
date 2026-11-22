<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\CustomerMedia;
use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use App\Services\ThemeCanvas\CustomerMediaResolver;
use App\Services\CustomerMediaService;
use App\Services\ThemeCanvas\ThemeInstanceRevisionService;
use App\Services\ThemeCanvas\ThemeMediaLocalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SolarDemoMediaImportTest extends TestCase
{
    use RefreshDatabase;

    private function onePixelPng(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['site.platform_site_id' => 1]);
        @touch(storage_path('installed.lock'));
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    public function test_demo_images_are_uploaded_to_active_disk_and_recorded_in_customer_media_and_rewritten_in_blocks(): void
    {
        Storage::fake('uploads');
        Storage::fake('public');

        $demoPath = 'solar-assets/about-plant-feature.jpg';
        Storage::disk('public')->put($demoPath, $this->onePixelPng());

        $themeData = [
            'name' => 'صنعت برق',
            'slug' => 'solar-industry',
            'pages' => [
                [
                    'key' => 'about',
                    'title' => 'درباره ما',
                    'blocks' => [
                        [
                            'id' => 'hero-1',
                            'type' => 'hero_with_stats',
                            'props' => [
                                'title' => 'عنوان بخش',
                                'image' => '/storage/solar-assets/about-plant-feature.jpg',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $service = app(ThemeMediaLocalizationService::class);
        $localized = $service->localize($themeData, ['theme_identifier' => 'solar-industry']);

        // 1. Assert block prop rewritten to canonical {"media_id": "<uuid>"}
        $rewrittenImage = $localized['pages'][0]['blocks'][0]['props']['image'];
        $this->assertIsArray($rewrittenImage);
        $this->assertArrayHasKey('media_id', $rewrittenImage);
        $uuid = $rewrittenImage['media_id'];
        $this->assertNotEmpty($uuid);

        // 2. Assert CustomerMedia record created with full metadata
        $media = CustomerMedia::forSite(1)->where('uuid', $uuid)->first();
        $this->assertNotNull($media);
        $this->assertSame('image', $media->category);
        $this->assertSame('image/png', $media->mime_type);
        $this->assertSame('about-plant-feature.jpg', $media->original_filename);
        $this->assertSame('/storage/solar-assets/about-plant-feature.jpg', $media->metadata['source_url'] ?? null);
        $this->assertSame('solar-industry', $media->metadata['theme_identifier'] ?? null);

        // 3. Assert file uploaded to active media disk (uploads in test / arvan in prod)
        Storage::disk('uploads')->assertExists($media->storage_key);

        // 4. Assert CustomerMediaResolver resolves prop to the public URL
        $resolver = app(CustomerMediaResolver::class);
        $resolvedProps = $resolver->resolveBlockProps('hero_with_stats', $localized['pages'][0]['blocks'][0]['props']);
        $this->assertIsString($resolvedProps['image']);
        $this->assertSame(Storage::disk('uploads')->url($media->storage_key), $resolvedProps['image']);
    }

    public function test_demo_images_are_deduplicated_across_multiple_blocks_and_reinstall(): void
    {
        Storage::fake('uploads');
        Storage::fake('public');

        $demoPath = 'solar-assets/about-plant-feature.jpg';
        Storage::disk('public')->put($demoPath, $this->onePixelPng());

        $themeData = [
            'name' => 'صنعت برق',
            'slug' => 'solar-industry',
            'pages' => [
                [
                    'key' => 'about',
                    'title' => 'درباره ما',
                    'blocks' => [
                        [
                            'id' => 'hero-1',
                            'type' => 'hero_with_stats',
                            'props' => [
                                'title' => 'عنوان بخش ۱',
                                'image' => '/storage/solar-assets/about-plant-feature.jpg',
                            ],
                        ],
                        [
                            'id' => 'zigzag-1',
                            'type' => 'zigzag_rows',
                            'props' => [
                                'title' => 'عنوان بخش ۲',
                                'rows' => [
                                    [
                                        'title' => 'ردیف ۱',
                                        'image' => '/storage/solar-assets/about-plant-feature.jpg',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $service = app(ThemeMediaLocalizationService::class);
        $localized = $service->localize($themeData, ['theme_identifier' => 'solar-industry']);

        $heroImage = $localized['pages'][0]['blocks'][0]['props']['image'];
        $rowImage = $localized['pages'][0]['blocks'][1]['props']['rows'][0]['image'];

        // Both blocks must reference the exact same media_id
        $this->assertIsArray($heroImage);
        $this->assertIsArray($rowImage);
        $this->assertSame($heroImage['media_id'], $rowImage['media_id']);

        // Exactly 1 media record and 1 storage file must exist
        $this->assertSame(1, CustomerMedia::forSite(1)->count());
        $this->assertCount(1, Storage::disk('uploads')->allFiles());

        // Re-run localization (simulating re-install or upgrade)
        $localizedSecondRun = $service->localize($themeData, ['theme_identifier' => 'solar-industry']);
        $this->assertSame(1, CustomerMedia::forSite(1)->count());
        $this->assertCount(1, Storage::disk('uploads')->allFiles());
        $this->assertSame($heroImage['media_id'], $localizedSecondRun['pages'][0]['blocks'][0]['props']['image']['media_id']);
    }

    public function test_deletion_of_unused_media_physically_deletes_from_storage_disk(): void
    {
        Storage::fake('uploads');

        $mediaService = app(CustomerMediaService::class);
        $media = $mediaService->importFromRemoteBytes(
            $this->onePixelPng(),
            'demo.png',
            'image/png',
            ['source_url' => '/storage/solar-assets/demo.png']
        );

        // Verify physical file exists on disk
        Storage::disk($media->disk)->assertExists($media->storage_key);
        $this->assertNull($media->deleted_at);
        $this->assertNull($media->purged_at);

        // Delete unused media
        $mediaService->deleteUnused($media);

        // Verify physical file is completely removed from storage disk (no orphan)
        Storage::disk($media->disk)->assertMissing($media->storage_key);

        $fresh = $media->fresh();
        $this->assertNotNull($fresh->deleted_at);
        $this->assertNotNull($fresh->purged_at);
    }

    public function test_artisan_command_migrates_existing_demo_media_and_is_idempotent(): void
    {
        Storage::fake('uploads');
        Storage::fake('public');

        $demoPath = 'solar-assets/hero-industrial-slide1.jpg';
        Storage::disk('public')->put($demoPath, $this->onePixelPng());

        $themeData = [
            'name' => 'صنعت برق',
            'slug' => 'solar-industry',
            'pages' => [
                [
                    'key' => 'home',
                    'title' => 'خانه',
                    'blocks' => [
                        [
                            'id' => 'hero-1',
                            'type' => 'hero_with_stats',
                            'props' => [
                                'title' => 'اسلاید اصلی',
                                'image' => '/storage/solar-assets/hero-industrial-slide1.jpg',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        Setting::set('active_canvas_theme_slug', 'solar-industry', 'theme');
        Setting::set('active_canvas_theme_data', json_encode($themeData), 'theme');

        $exitCode = Artisan::call('theme:migrate-demo-media', ['--site-id' => 1]);
        $this->assertSame(0, $exitCode);

        // Verify setting was rewritten with media_id
        $updatedData = json_decode(Setting::get('active_canvas_theme_data'), true);
        $rewrittenImage = $updatedData['pages'][0]['blocks'][0]['props']['image'];
        $this->assertIsArray($rewrittenImage);
        $this->assertArrayHasKey('media_id', $rewrittenImage);

        // Exactly 1 media row and 1 storage file
        $this->assertSame(1, CustomerMedia::forSite(1)->count());
        $this->assertCount(1, Storage::disk('uploads')->allFiles());

        // Run command a second time: must be idempotent and succeed with code 0
        $secondExitCode = Artisan::call('theme:migrate-demo-media', ['--site-id' => 1]);
        $this->assertSame(0, $secondExitCode);
        $this->assertSame(1, CustomerMedia::forSite(1)->count());
        $this->assertCount(1, Storage::disk('uploads')->allFiles());
    }

    public function test_installing_theme_via_controller_localizes_local_demo_images_and_registers_usages(): void
    {
        Storage::fake('uploads');
        Storage::fake('public');

        \App\Models\License::create([
            'license_key' => 'MEDIA-LOC-' . uniqid(),
            'domain' => 'localhost',
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);

        config([
            'site.owner_site' => true,
            'services.customer_provision_secret' => 'demo-secret',
        ]);

        $demoPath = 'solar-assets/about-plant-feature.jpg';
        Storage::disk('public')->put($demoPath, $this->onePixelPng());

        $response = $this->postJson('/builder/api/theme/install', [
            'theme_id' => 'solar-industry',
            'theme_name' => 'قالب صنعت برق',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => [
                [
                    'key' => 'about',
                    'title' => 'درباره ما',
                    'blocks' => [
                        [
                            'id' => 'hero-1',
                            'type' => 'hero_with_stats',
                            'props' => [
                                'title' => 'درباره ما',
                                'image' => '/storage/solar-assets/about-plant-feature.jpg',
                            ],
                        ],
                    ],
                ],
            ],
        ], ['X-Provision-Secret' => 'demo-secret']);

        $response->assertOk()->assertJson(['success' => true]);

        // Assert customer_media created
        $media = CustomerMedia::forSite(1)->whereJsonContains('metadata->source_url', '/storage/solar-assets/about-plant-feature.jpg')->sole();
        $this->assertSame('/storage/solar-assets/about-plant-feature.jpg', $media->metadata['source_url'] ?? null);

        // Assert file exists on active uploads disk
        Storage::disk('uploads')->assertExists($media->storage_key);

        // Assert revision created and usages registered
        $revision = ThemeInstanceRevision::query()->latest('id')->first();
        $this->assertNotNull($revision);
        $this->assertSame($media->uuid, $revision->theme_data['pages'][0]['blocks'][0]['props']['image']['media_id']);

        // Assert usage row in customer_media_usages
        $this->assertDatabaseHas('customer_media_usages', [
            'customer_media_id' => $media->id,
            'consumer_type' => 'theme_instance_revision',
            'consumer_id' => $revision->id,
        ]);
    }
}

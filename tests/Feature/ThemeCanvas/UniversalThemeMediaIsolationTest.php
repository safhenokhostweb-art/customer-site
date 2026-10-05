<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\CustomerMedia;
use App\Services\CustomerMediaService;
use App\Services\ThemeCanvas\CustomerMediaResolver;
use App\Services\ThemeCanvas\ThemeMediaLocalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Commercial isolation acceptance for universal-multipurpose-qa.
 * Covers two sites, two themes on one site, and two releases of Universal.
 */
class UniversalThemeMediaIsolationTest extends TestCase
{
    use RefreshDatabase;

    private const SOURCE = 'https://saas-platform.test/storage/theme-media/1/hero.jpg';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('uploads');
        config(['site.platform_url' => 'https://saas-platform.test']);
        Http::fake([
            'saas-platform.test/storage/theme-media/*' => Http::response($this->png(), 200, ['Content-Type' => 'image/png']),
        ]);
    }

    public function test_same_universal_release_is_physically_isolated_between_two_sites(): void
    {
        $localizer = app(ThemeMediaLocalizationService::class);

        config(['site.platform_site_id' => 101]);
        $siteA = $localizer->localize($this->themeData(), $this->scope('universal-multipurpose-qa', 11));
        $uuidA = $this->uuid($siteA);
        $mediaA = CustomerMedia::where('uuid', $uuidA)->sole();

        config(['site.platform_site_id' => 202]);
        $siteB = $localizer->localize($this->themeData(), $this->scope('universal-multipurpose-qa', 11));
        $uuidB = $this->uuid($siteB);
        $mediaB = CustomerMedia::where('uuid', $uuidB)->sole();

        self::assertNotSame($uuidA, $uuidB, 'a long-lived localizer must not leak its per-run UUID cache across sites');
        self::assertStringStartsWith('customer-media/101/themes/universal-multipurpose-qa/release-11/', $mediaA->storage_key);
        self::assertStringStartsWith('customer-media/202/themes/universal-multipurpose-qa/release-11/', $mediaB->storage_key);
        self::assertStringNotContainsString('saas-platform.test', json_encode([$siteA, $siteB]));

        config(['site.platform_site_id' => 101]);
        app(CustomerMediaService::class)->deleteUnused($mediaA);
        self::assertFalse(Storage::disk($mediaA->disk)->exists($mediaA->storage_key));

        config(['site.platform_site_id' => 202]);
        self::assertTrue(Storage::disk($mediaB->disk)->exists($mediaB->storage_key));
        self::assertNotSame('', app(CustomerMediaResolver::class)->resolveValue(['media_id' => $uuidB]));
    }

    public function test_two_themes_on_one_site_never_share_or_overwrite_imported_media(): void
    {
        config(['site.platform_site_id' => 303]);
        $localizer = app(ThemeMediaLocalizationService::class);

        $universal = $localizer->localize($this->themeData(), $this->scope('universal-multipurpose-qa', 21));
        $other = $localizer->localize($this->themeData(), $this->scope('another-commercial-theme', 21));
        $universalMedia = CustomerMedia::where('uuid', $this->uuid($universal))->sole();
        $otherMedia = CustomerMedia::where('uuid', $this->uuid($other))->sole();

        self::assertNotSame($universalMedia->uuid, $otherMedia->uuid);
        self::assertStringContainsString('/themes/universal-multipurpose-qa/release-21/', $universalMedia->storage_key);
        self::assertStringContainsString('/themes/another-commercial-theme/release-21/', $otherMedia->storage_key);

        app(CustomerMediaService::class)->deleteUnused($universalMedia);
        self::assertFalse(Storage::disk($universalMedia->disk)->exists($universalMedia->storage_key));
        self::assertTrue(Storage::disk($otherMedia->disk)->exists($otherMedia->storage_key));
        self::assertNotSame('', app(CustomerMediaResolver::class)->resolveValue(['media_id' => $otherMedia->uuid]));
    }

    public function test_universal_releases_are_separate_and_reinstall_is_idempotent_within_one_release(): void
    {
        config(['site.platform_site_id' => 404]);
        $localizer = app(ThemeMediaLocalizationService::class);

        $releaseOne = $localizer->localize($this->themeData(), $this->scope('universal-multipurpose-qa', 31));
        $releaseOneAgain = $localizer->localize($this->themeData(), $this->scope('universal-multipurpose-qa', 31));
        self::assertSame($this->uuid($releaseOne), $this->uuid($releaseOneAgain));
        self::assertSame(1, CustomerMedia::count(), 'reinstalling the same release must reuse its scoped asset');

        $releaseTwo = $localizer->localize($this->themeData(), $this->scope('universal-multipurpose-qa', 32));
        self::assertNotSame($this->uuid($releaseOne), $this->uuid($releaseTwo));
        self::assertSame(2, CustomerMedia::count());

        $mediaOne = CustomerMedia::where('uuid', $this->uuid($releaseOne))->sole();
        $mediaTwo = CustomerMedia::where('uuid', $this->uuid($releaseTwo))->sole();
        self::assertStringContainsString('/release-31/', $mediaOne->storage_key);
        self::assertStringContainsString('/release-32/', $mediaTwo->storage_key);
        self::assertStringNotContainsString('saas-platform.test', json_encode($releaseTwo));

        app(CustomerMediaService::class)->deleteUnused($mediaOne);
        self::assertFalse(Storage::disk($mediaOne->disk)->exists($mediaOne->storage_key));
        self::assertTrue(Storage::disk($mediaTwo->disk)->exists($mediaTwo->storage_key));
    }

    private function themeData(): array
    {
        return ['pages' => ['home' => ['blocks' => [[
            'id' => 'hero-isolation',
            'type' => 'hero',
            'props' => ['title' => 'Universal', 'background_image' => self::SOURCE],
        ]]]]];
    }

    private function scope(string $theme, int $release): array
    {
        return ['theme_identifier' => $theme, 'source_release_id' => $release];
    }

    private function uuid(array $themeData): string
    {
        return $themeData['pages']['home']['blocks'][0]['props']['background_image']['media_id'];
    }

    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    }
}

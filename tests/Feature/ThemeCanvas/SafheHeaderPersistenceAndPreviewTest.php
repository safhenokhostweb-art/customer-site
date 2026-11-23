<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use App\Models\User;
use App\Services\ThemeCanvas\ThemeInstanceRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * Feature Test: Safhe Nokhost Header Persistence, Parity & Live Preview.
 *
 * Verifies that:
 * 1. Admin can save customized Safhe Header props (logo, megamenu columns, CTA)
 *    via canvasSaveBlocks on virtual page '__header__'.
 * 2. Canvas publish persists changes to active_canvas_theme_data and records
 *    an immutable ThemeInstanceRevision.
 * 3. canvas-live-preview accurately reflects updated header properties
 *    in both header-focused preview (__header__) and full-page preview (home).
 */
class SafheHeaderPersistenceAndPreviewTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();
    }

    private function admin(): User
    {
        $this->seedValidLicense(['pages']);
        return User::factory()->create(['is_admin' => true]);
    }

    private function installSafheTheme(): void
    {
        $initialHeaderProps = [
            'logo_text' => 'صفحه',
            'logo_text_accent' => 'نخست',
            'logo_tagline' => 'زیرساخت ابری و توسعه وب',
            'logo_image' => '/theme-assets/safhenokhost/safhe-icon.svg',
            'mobile_logo' => '/theme-assets/safhenokhost/safhe-icon.svg',
            'variant' => 'theme-header--safhe-architectural',
            'custom_class' => 'theme-header--safhe-architectural',
            'nav_source' => 'custom',
            'nav_links' => [
                [
                    'label' => 'خدمات',
                    'url' => '#services',
                    'mega_columns' => [
                        [
                            'title' => 'طراحی و توسعه وب',
                            'items' => [
                                ['label' => 'پلتفرم ابری', 'url' => '#cloud', 'icon' => 'cloud'],
                            ],
                        ],
                    ],
                ],
            ],
            'cta_text' => 'درخواست مشاوره',
            'cta_url' => '/contact',
            'cta2_text' => 'ورود',
            'cta2_url' => '/login',
        ];

        app(ThemeInstanceRevisionService::class)->record([
            'name' => 'Safhe Nokhost Theme',
            'slug' => 'safhenokhost',
            'header_blocks' => [
                [
                    'id' => 'safhe-header-nav',
                    'type' => 'nav_header',
                    'layout_slot' => 'header',
                    'props' => $initialHeaderProps,
                ],
            ],
            'footer_blocks' => [],
            'pages' => [
                'home' => [
                    'title' => 'خانه',
                    'slug' => 'home',
                    'blocks' => [
                        [
                            'id' => 'hero-1',
                            'type' => 'text',
                            'props' => ['content' => 'محتوای صفحه نخست'],
                        ],
                    ],
                ],
            ],
            'global_styles' => [],
        ], ThemeInstanceRevision::TYPE_INSTALL, ['theme_identifier' => 'safhenokhost']);
    }

    public function test_safhe_header_props_persistence_and_preview_roundtrip(): void
    {
        $this->installSafheTheme();
        $admin = $this->admin();

        $updatedHeaderProps = [
            'logo_text' => 'صفحه نخست اختصاصی',
            'logo_text_accent' => 'پرو',
            'logo_tagline' => 'معماری مدرن وب ایرانی',
            'logo_image' => '/theme-assets/safhenokhost/safhe-logo.svg',
            'mobile_logo' => '/theme-assets/safhenokhost/safhe-icon.svg',
            'variant' => 'theme-header--safhe-architectural',
            'custom_class' => 'theme-header--safhe-architectural',
            'nav_source' => 'custom',
            'nav_links' => [
                [
                    'label' => 'خدمات سازمانی',
                    'url' => '#enterprise',
                    'mega_columns' => [
                        [
                            'title' => 'راهکارهای ابری پیشرفته',
                            'items' => [
                                ['label' => 'زیرساخت اختصاصی کلود', 'url' => '#dedicated', 'icon' => 'server'],
                                ['label' => 'طراحی سیستم‌های توزیع‌شده', 'url' => '#distributed', 'icon' => 'network'],
                            ],
                        ],
                    ],
                ],
                [
                    'label' => 'نمونه‌کارها',
                    'url' => '#portfolio',
                ],
            ],
            'cta_text' => 'شروع پروژه سازمانی',
            'cta_url' => '/start-project',
            'cta2_text' => 'پرتال مشتریان',
            'cta2_url' => '/portal',
        ];

        // 1. Save modified header blocks via canvasSaveBlocks on virtual page '__header__'
        $saveResponse = $this->actingAs($admin)->putJson(route('admin.theme-design.canvas-save-blocks'), [
            'page_key' => '__header__',
            'blocks' => [
                [
                    'id' => 'safhe-header-nav',
                    'type' => 'nav_header',
                    'layout_slot' => 'header',
                    'props' => $updatedHeaderProps,
                ],
            ],
        ]);

        $saveResponse->assertOk();
        $saveResponse->assertJson(['success' => true]);

        // Verify draft was saved
        $draft = json_decode(Setting::get('canvas_draft_data'), true);
        $this->assertNotEmpty($draft);
        $this->assertSame('صفحه نخست اختصاصی', $draft['header_blocks'][0]['props']['logo_text']);

        // 2. Publish draft and verify immutable ThemeInstanceRevision
        $publishResponse = $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'));
        $publishResponse->assertOk();
        $publishResponse->assertJson(['success' => true]);

        $activeData = json_decode(Setting::get('active_canvas_theme_data'), true);
        $this->assertSame('صفحه نخست اختصاصی', $activeData['header_blocks'][0]['props']['logo_text']);
        $this->assertSame('شروع پروژه سازمانی', $activeData['header_blocks'][0]['props']['cta_text']);

        $latestRevision = ThemeInstanceRevision::latest('id')->first();
        $this->assertNotNull($latestRevision);
        $this->assertSame(ThemeInstanceRevision::TYPE_PUBLISH, $latestRevision->type);

        // 3. Verify Live Preview for '__header__'
        $previewHeaderResponse = $this->actingAs($admin)->get(route('admin.theme-design.canvas-live-preview', ['page' => '__header__']));
        $previewHeaderResponse->assertOk();
        $previewHeaderContent = $previewHeaderResponse->getContent();

        $this->assertStringContainsString('صفحه نخست اختصاصی', $previewHeaderContent);
        $this->assertStringContainsString('معماری مدرن وب ایرانی', $previewHeaderContent);
        $this->assertStringContainsString('خدمات سازمانی', $previewHeaderContent);
        $this->assertStringContainsString('راهکارهای ابری پیشرفته', $previewHeaderContent);
        $this->assertStringContainsString('شروع پروژه سازمانی', $previewHeaderContent);
        $this->assertStringContainsString('پرتال مشتریان', $previewHeaderContent);

        // 4. Verify Live Preview for full page 'home'
        $previewHomeResponse = $this->actingAs($admin)->get(route('admin.theme-design.canvas-live-preview', ['page' => 'home']));
        $previewHomeResponse->assertOk();
        $previewHomeContent = $previewHomeResponse->getContent();

        $this->assertStringContainsString('صفحه نخست اختصاصی', $previewHomeContent);
        $this->assertStringContainsString('شروع پروژه سازمانی', $previewHomeContent);
        $this->assertStringContainsString('محتوای صفحه نخست', $previewHomeContent);
    }
}

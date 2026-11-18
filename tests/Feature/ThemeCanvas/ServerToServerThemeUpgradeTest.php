<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Setting;
use App\Models\SiteThemeTemplate;
use App\Models\ThemeInstanceRevision;
use App\Services\ThemeCanvas\ThemeInstanceRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerToServerThemeUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.customer_provision_secret' => 'test-secret-12345']);
    }

    public function test_upgrade_endpoint_rejects_invalid_provision_secret(): void
    {
        $response = $this->postJson('/builder/api/theme/upgrade', [
            'theme_id'        => 'solar-industry',
            'theme_name'      => 'Solar Industry',
            'remote_snapshot' => ['pages' => []],
        ], [
            'X-Provision-Secret' => 'wrong-secret',
        ]);

        $response->assertStatus(403);
    }

    public function test_upgrade_endpoint_is_non_destructive_and_deposits_blocks_for_edited_pages(): void
    {
        // 1. Initial Installation (BASE)
        $initialData = [
            'name'          => 'Solar Industry',
            'slug'          => 'solar-industry',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages'         => [
                'home' => [
                    'title'  => 'صفحه اصلی',
                    'blocks' => [
                        [
                            'id'    => 'block-hero-1',
                            'type'  => 'hero',
                            'props' => ['heading' => 'تیتر اولیه کارخانه'],
                        ],
                    ],
                ],
                'contact' => [
                    'title'  => 'تماس با ما',
                    'blocks' => [
                        [
                            'id'    => 'block-contact-1',
                            'type'  => 'contact_info',
                            'props' => ['phone' => '02111111111'],
                        ],
                    ],
                ],
            ],
            'global_styles' => ['primary_color' => '#FF7900'],
        ];

        $service = app(ThemeInstanceRevisionService::class);
        $rev1 = $service->record($initialData, ThemeInstanceRevision::TYPE_INSTALL, [
            'theme_identifier'     => 'solar-industry',
            'theme_name'           => 'Solar Industry',
            'source_theme_version' => '1.0.0',
            'source_release_id'    => 10,
        ]);

        // 2. Customer edits the 'home' page (changes title and text) and adds a custom page
        $editedData = $initialData;
        $editedData['pages']['home']['blocks'][0]['props']['heading'] = 'عنوان اختصاصی ویرایش‌شده توسط مشتری';
        $editedData['pages']['about_us'] = [
            'title'  => 'درباره شرکت ما',
            'blocks' => [
                ['id' => 'block-custom-about', 'type' => 'text', 'props' => ['content' => 'محتوای دلخواه مشتری']],
            ],
        ];

        $rev2 = $service->record($editedData, ThemeInstanceRevision::TYPE_CUSTOMER_EDIT, [
            'theme_identifier' => 'solar-industry',
            'theme_name'       => 'Solar Industry',
        ]);

        $this->assertSame(2, $rev2->revision_number);

        // 3. Remote releases v1.1.0 with:
        //    - An extra new section on 'home' page (which customer has edited)
        //    - An extra new section on 'contact' page (which customer has NOT edited)
        //    - Updated global styles
        $remoteSnapshot = [
            'name'          => 'Solar Industry',
            'slug'          => 'solar-industry',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages'         => [
                'home' => [
                    'title'  => 'صفحه اصلی',
                    'blocks' => [
                        [
                            'id'    => 'block-hero-1',
                            'type'  => 'hero',
                            'props' => ['heading' => 'تیتر جدید v1.1.0'],
                        ],
                        [
                            'id'    => 'block-new-solar-calc',
                            'type'  => 'solar_calculator',
                            'props' => ['title' => 'محاسبه‌گر توان نیروگاهی خورشیدی'],
                        ],
                    ],
                ],
                'contact' => [
                    'title'  => 'تماس با ما',
                    'blocks' => [
                        [
                            'id'    => 'block-contact-1',
                            'type'  => 'contact_info',
                            'props' => ['phone' => '02111111111'],
                        ],
                        [
                            'id'    => 'block-contact-map',
                            'type'  => 'map',
                            'props' => ['title' => 'نقشه جدید'],
                        ],
                    ],
                ],
            ],
            'global_styles' => ['primary_color' => '#FF7900', 'font_family' => 'Vazirmatn'],
        ];

        // 4. Dispatch server-to-server upgrade call
        $response = $this->postJson('/builder/api/theme/upgrade', [
            'theme_id'              => 'solar-industry',
            'theme_name'            => 'Solar Industry',
            'source_theme_id'       => '1',
            'source_theme_version'  => '1.1.0',
            'source_release_id'     => 11,
            'remote_snapshot'       => $remoteSnapshot,
        ], [
            'X-Provision-Secret' => 'test-secret-12345',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json();
        $this->assertGreaterThan(2, $data['revision_number']);

        // 5. CRITICAL VERIFICATION:
        // Customer's 'home' page MUST keep their custom heading and MUST NOT have the new block automatically injected!
        $currentLiveTheme = json_decode(Setting::get('active_canvas_theme_data'), true);

        $homeBlocks = $currentLiveTheme['pages']['home']['blocks'];
        $this->assertCount(1, $homeBlocks, 'Customer-edited home page must NOT have new remote blocks injected into its body!');
        $this->assertSame('عنوان اختصاصی ویرایش‌شده توسط مشتری', $homeBlocks[0]['props']['heading'], 'Customer custom text must be 100% preserved!');

        // Customer's created 'about_us' page must be fully intact
        $this->assertArrayHasKey('about_us', $currentLiveTheme['pages']);
        $this->assertSame('محتوای دلخواه مشتری', $currentLiveTheme['pages']['about_us']['blocks'][0]['props']['content']);

        // Pristine 'contact' page (which customer never edited) received the new map block
        $this->assertCount(2, $currentLiveTheme['pages']['contact']['blocks']);

        // The newly introduced block from 'home' was deposited into SiteThemeTemplate for user choice!
        $depositedTemplate = SiteThemeTemplate::where('name', 'like', '%محاسبه‌گر توان نیروگاهی خورشیدی%')->first();
        $this->assertNotNull($depositedTemplate, 'New block from customer-edited page must be deposited into Canvas block library!');
        $this->assertSame('solar-industry', $depositedTemplate->tags[0]);
    }
}

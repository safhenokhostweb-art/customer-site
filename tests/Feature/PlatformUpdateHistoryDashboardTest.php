<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\User;
use App\Services\UpdateCheckService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlatformUpdateHistoryDashboardTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_update_check_service_fetches_history_from_platform(): void
    {
        config(['site.platform_url' => 'https://saas.test']);

        License::updateOrCreate(
            ['domain' => 'localhost'],
            [
                'license_key' => 'valid-license-test-123',
                'status' => 'active',
                'platform_valid' => true,
                'modules' => ['shop', 'blog'],
            ]
        );

        Http::fake([
            'https://saas.test/api/updates/history*' => Http::response([
                'allowed' => true,
                'history' => [
                    [
                        'id' => 1,
                        'version' => 'v1.4.0',
                        'title' => 'هدر شناور شیشه‌ای مدرن و پلتفرم Canvas',
                        'shamsi_date' => '۱۴۰۵/۰۹/۰۸',
                        'update_type' => 'feature',
                        'type_label' => 'امکان جدید',
                        'features' => [
                            [
                                'category' => 'هدر شیشه‌ای مدرن',
                                'items' => ['پوسته جدید glass_nimbus'],
                            ],
                        ],
                        'notes' => 'توضیحات تست پلتفرم',
                    ],
                ],
                'latest' => [
                    'id' => 1,
                    'version' => 'v1.4.0',
                    'title' => 'هدر شناور شیشه‌ای مدرن و پلتفرم Canvas',
                    'shamsi_date' => '۱۴۰۵/۰۹/۰۸',
                    'update_type' => 'feature',
                    'type_label' => 'امکان جدید',
                    'features' => [
                        [
                            'category' => 'هدر شیشه‌ای مدرن',
                            'items' => ['پوسته جدید glass_nimbus'],
                        ],
                    ],
                    'notes' => 'توضیحات تست پلتفرم',
                ],
            ], 200),
        ]);

        $service = app(UpdateCheckService::class);
        $result = $service->getUpdateHistory(20, true);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['history']);
        $this->assertEquals('v1.4.0', $result['latest']['version']);
        $this->assertEquals('هدر شناور شیشه‌ای مدرن و پلتفرم Canvas', $result['latest']['title']);
    }

    public function test_customer_dashboard_renders_update_banner_and_modal(): void
    {
        config(['site.platform_url' => 'https://saas.test']);

        License::updateOrCreate(
            ['domain' => 'localhost'],
            [
                'license_key' => 'valid-license-test-123',
                'status' => 'active',
                'platform_valid' => true,
                'modules' => ['shop'],
            ]
        );

        Http::fake([
            'https://saas.test/api/updates/history*' => Http::response([
                'allowed' => true,
                'history' => [
                    [
                        'id' => 1,
                        'version' => 'v1.4.0',
                        'title' => 'هدر شناور شیشه‌ای مدرن و پلتفرم Canvas',
                        'shamsi_date' => '۱۴۰۵/۰۹/۰۸',
                        'update_type' => 'feature',
                        'type_label' => 'امکان جدید',
                        'features' => [
                            [
                                'category' => 'هدر شیشه‌ای مدرن',
                                'items' => ['پوسته جدید glass_nimbus'],
                            ],
                        ],
                        'notes' => 'توضیحات تست پلتفرم',
                    ],
                ],
                'latest' => [
                    'id' => 1,
                    'version' => 'v1.4.0',
                    'title' => 'هدر شناور شیشه‌ای مدرن و پلتفرم Canvas',
                    'shamsi_date' => '۱۴۰۵/۰۹/۰۸',
                    'update_type' => 'feature',
                    'type_label' => 'امکان جدید',
                    'features' => [
                        [
                            'category' => 'هدر شیشه‌ای مدرن',
                            'items' => ['پوسته جدید glass_nimbus'],
                        ],
                    ],
                    'notes' => 'توضیحات تست پلتفرم',
                ],
            ], 200),
        ]);

        $admin = User::firstOrCreate(
            ['mobile' => '09121112233'],
            [
                'name' => 'Admin Test',
                'password' => bcrypt('password'),
                'is_admin' => 1,
            ]
        );

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('platform-update-banner', false);
        $response->assertSee('v1.4.0');
        $response->assertSee('هدر شناور شیشه‌ای مدرن و پلتفرم Canvas');
        $response->assertSee('۱۴۰۵/۰۹/۰۸');
        $response->assertSee('platform-updates-modal', false);
        $response->assertSee('تاریخچه بروزرسانی‌ها و امکانات جدید پلتفرم');
        $response->assertSee('پوسته جدید glass_nimbus');
    }
}

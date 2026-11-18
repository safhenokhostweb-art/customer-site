<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\CustomerMedia;
use App\Models\Site;
use App\Services\ThemeCanvas\ThemeInstanceRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SolarThemeProtectiveIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_login_blade_does_not_contain_hardcoded_solar_colors(): void
    {
        $bladePath = resource_path('views/layouts/auth-login.blade.php');
        $this->assertFileExists($bladePath);
        $content = file_get_contents($bladePath);

        $this->assertStringNotContainsString('--auth-solar-primary', $content);
        $this->assertStringNotContainsString('#FF7900', $content);
        $this->assertStringNotContainsString('background: #0B0B0B', $content);
        $this->assertStringContainsString('--auth-login-primary', $content);
    }

    public function test_login_and_register_pages_render_solar_styles_when_solar_theme_active(): void
    {
        \App\Models\Setting::set('active_canvas_theme_slug', 'solar-industry');

        $loginResponse = $this->get(route('login'));
        $loginResponse->assertOk();
        $loginContent = $loginResponse->getContent();
        $this->assertStringContainsString('auth-solar-industrial-css', $loginContent);
        $this->assertStringContainsString('--auth-solar-primary: #FF7900', $loginContent);
        $this->assertStringContainsString('پلتفرم یکپارچه', $loginContent);

        $registerResponse = $this->get(route('register'));
        $registerResponse->assertOk();
        $registerContent = $registerResponse->getContent();
        $this->assertStringContainsString('auth-solar-industrial-css', $registerContent);
        $this->assertStringContainsString('--auth-solar-primary: #FF7900', $registerContent);
    }

    public function test_login_and_register_pages_do_not_leak_solar_styles_on_neutral_theme(): void
    {
        \App\Models\Setting::set('active_canvas_theme_slug', 'default');
        \App\Models\Setting::set('active_canvas_theme_name', 'قالب پیش‌فرض');
        \App\Models\Setting::set('active_canvas_theme_id', '1');

        $loginResponse = $this->get(route('login'));
        $loginResponse->assertOk();
        $loginContent = $loginResponse->getContent();
        $this->assertStringNotContainsString('auth-solar-industrial-css', $loginContent);
        $this->assertStringNotContainsString('--auth-solar-primary: #FF7900', $loginContent);
        $this->assertStringNotContainsString('پلتفرم یکپارچه', $loginContent);
        $this->assertStringContainsString('ورود امن به پنل', $loginContent);

        $registerResponse = $this->get(route('register'));
        $registerResponse->assertOk();
        $registerContent = $registerResponse->getContent();
        $this->assertStringNotContainsString('auth-solar-industrial-css', $registerContent);
        $this->assertStringNotContainsString('--auth-solar-primary: #FF7900', $registerContent);
    }

    public function test_solar_industry_home_page_json_contains_slider_block(): void
    {
        $homeJsonPath = resource_path('views/themes/solar-industry/solar-page-home.json');
        $this->assertFileExists($homeJsonPath);
        $data = json_decode(file_get_contents($homeJsonPath), true);

        $types = array_column($data['blocks'] ?? [], 'type');
        $this->assertContains('slider', $types, 'Home page must have slider block');
    }

    public function test_solar_industry_calculator_page_json_contains_calculator_module(): void
    {
        $calcJsonPath = resource_path('views/themes/solar-industry/solar-page-solar_calculator.json');
        $this->assertFileExists($calcJsonPath);
        $data = json_decode(file_get_contents($calcJsonPath), true);

        $types = array_column($data['blocks'] ?? [], 'type');
        $this->assertContains('module:solar_calculator', $types, 'Calculator page must have module:solar_calculator block');
    }

    public function test_index_theme_static_assets_indexes_assets_into_customer_media(): void
    {
        config(['site.platform_site_id' => 10]);

        $testDir = storage_path('app/public/test-solar-assets');
        File::ensureDirectoryExists($testDir);
        $dummyImage = $testDir . '/test-image.png';
        // 1x1 transparent PNG
        file_put_contents($dummyImage, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

        try {
            $service = app(ThemeInstanceRevisionService::class);
            $indexed = $service->indexThemeStaticAssets('test-solar-assets');

            $this->assertGreaterThanOrEqual(1, $indexed);
            $this->assertDatabaseHas('customer_media', [
                'site_id' => 10,
                'storage_key' => 'test-solar-assets/test-image.png',
                'category' => 'image',
                'mime_type' => 'image/png',
            ]);
        } finally {
            File::deleteDirectory($testDir);
        }
    }
}

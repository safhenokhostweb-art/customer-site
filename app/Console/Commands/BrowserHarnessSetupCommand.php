<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Services\ThemeCanvas\BlockRegistry;
use App\Services\ThemeCanvas\ThemeInstanceRevisionService;
use App\Support\Installation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

/**
 * Phase 3E-H-D0 — test-infrastructure-only command that provisions an
 * isolated, deterministic environment for the Playwright browser harness:
 * migrates the browser-test database fresh, seeds a deterministic admin
 * user + a Builder-editable page, and ensures the install lock is present
 * so the app boots straight to the login form.
 *
 * The database itself must already exist before this command runs — see
 * tests/browser/scripts/ensure-database.php, invoked before this command
 * (Laravel's own boot process queries the configured database before any
 * command's handle() method executes, so creating it here would be too
 * late).
 *
 * Deliberately NOT wired into any production/deploy flow — it is a CLI-only
 * opt-in command. It refuses to run against anything that doesn't look like
 * the dedicated browser-test database, so a misconfigured environment can
 * never point this at development or production data.
 */
class BrowserHarnessSetupCommand extends Command
{
    protected $signature = 'browser-harness:setup
        {--json : Print a single machine-readable JSON summary line at the end}
        {--universal : Install the complete multipurpose Canvas QA fixture after the baseline fixtures}
        {--source-theme-id= : Run-evidence ID of the slug-resolved SaaS Theme}
        {--source-release-id= : Run-evidence ID of the canonical SaaS ThemeRelease}
        {--source-version=1.0.0 : Canonical SaaS ThemeRelease version}';

    protected $description = 'Provision the isolated database/fixtures used by the Playwright browser test harness (tests/browser). Never run outside the dedicated browser-test database.';

    public const MOBILE = '09120000001';
    public const PASSWORD = 'browser-harness-pass';
    public const PAGE_SLUG = 'browser-harness-page';
    public const PAGE_TITLE = 'Browser Harness Page';
    public const CONTROL_PAGE_SLUG = 'browser-harness-control-page';
    public const CONTROL_PAGE_TITLE = 'Browser Harness Control Page';
    public const LIFECYCLE_PAGE_SLUG = 'browser-harness-lifecycle-page';
    public const LIFECYCLE_PAGE_TITLE = 'Browser Harness Lifecycle Page';
    public const EXTENSION_PAGE_SLUG = 'browser-harness-extension-page';
    public const EXTENSION_PAGE_TITLE = 'Browser Harness Extension Page';

    public function handle(): int
    {
        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if (app()->environment('production')) {
            $this->error('Refusing to run browser-harness:setup in the production environment.');
            return self::FAILURE;
        }

        if (!str_contains($database, 'browser')) {
            $this->error("Refusing to run: database \"{$database}\" does not look like the dedicated browser-test database (expected its name to contain \"browser\"). Set DB_DATABASE accordingly before running this command.");
            return self::FAILURE;
        }

        $this->info("Migrating fresh against isolated database \"{$database}\"...");
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->line(Artisan::output());

        Installation::writeLockFile();
        $this->info('Install lock ensured.');

        $user = User::updateOrCreate(
            ['mobile' => self::MOBILE],
            [
                'name' => 'Browser Harness Admin',
                'password' => Hash::make(self::PASSWORD),
                'is_admin' => true,
            ],
        );
        $this->info("Admin user ready: {$user->mobile}");

        $page = Page::updateOrCreate(
            ['slug' => self::PAGE_SLUG],
            [
                'title' => self::PAGE_TITLE,
                'body' => '',
                'builder_content_v2' => ['blocks' => []],
                'builder_type' => Page::BUILDER_TYPE_BUILDER,
                'is_published' => true,
                'show_in_menu' => false,
            ],
        );
        $this->info("Builder page ready: id={$page->id} slug={$page->slug}");

        // Phase 3E-H-D — a second, deliberately extension-free page, used
        // only to prove asset-emission isolation (no extension asset must
        // ever appear on a page that never used an extension block).
        $controlPage = Page::updateOrCreate(
            ['slug' => self::CONTROL_PAGE_SLUG],
            [
                'title' => self::CONTROL_PAGE_TITLE,
                'body' => '',
                'builder_content_v2' => ['blocks' => [
                    ['id' => 'control_heading', 'type' => 'heading', 'props' => ['text' => 'Control page', 'level' => 2]],
                ]],
                'builder_type' => Page::BUILDER_TYPE_BUILDER,
                'is_published' => true,
                'show_in_menu' => false,
            ],
        );
        $this->info("Control page ready: id={$controlPage->id} slug={$controlPage->slug}");

        // Phase 3E-H-D — a third, dedicated, initially-empty page used only
        // by the unavailable/re-enable extension lifecycle scenario, kept
        // separate from the other pages so its multi-server-restart
        // sequence never interferes with (or is polluted by) unrelated
        // scenarios' accumulated content.
        $lifecyclePage = Page::updateOrCreate(
            ['slug' => self::LIFECYCLE_PAGE_SLUG],
            [
                'title' => self::LIFECYCLE_PAGE_TITLE,
                'body' => '',
                'builder_content_v2' => ['blocks' => []],
                'builder_type' => Page::BUILDER_TYPE_BUILDER,
                'is_published' => true,
                'show_in_menu' => false,
            ],
        );
        $this->info("Lifecycle page ready: id={$lifecyclePage->id} slug={$lifecyclePage->slug}");

        // Phase 3E-H-D — a fourth, dedicated, initially-empty page owned
        // exclusively by tests/browser/extension-sdk.spec.js's own
        // "demo_card real path" / container / responsive scenarios, kept
        // separate from D0's own `page` (smoke.spec.js) so the two spec
        // files' block counts never interfere with each other when run
        // together in one `npm run test:browser` invocation.
        $extensionPage = Page::updateOrCreate(
            ['slug' => self::EXTENSION_PAGE_SLUG],
            [
                'title' => self::EXTENSION_PAGE_TITLE,
                'body' => '',
                'builder_content_v2' => ['blocks' => []],
                'builder_type' => Page::BUILDER_TYPE_BUILDER,
                'is_published' => true,
                'show_in_menu' => false,
            ],
        );
        $this->info("Extension page ready: id={$extensionPage->id} slug={$extensionPage->slug}");

        // Phase 3F-B — minimal Canvas Theme fixture so the harness can
        // reach /admin/theme-design/canvas/edit for real browser
        // verification of the (pre-existing, unmodified this phase) nested
        // authoring UI and the new declarative container-capability
        // metadata. One page, one built-in `section` container block, no
        // children — real child authoring is exercised live by the test.
        // Phase 3F-C — a second Canvas page, dedicated to the Extension SDK
        // → Canvas bridge scenarios (palette/nested authoring/assets/XSS),
        // kept separate from 'home' so its content never interferes with
        // 3F-B's own nested-authoring proof. Starts empty; real authoring
        // is exercised live by the browser test.
        $canvasThemeData = [
            'pages' => [
                'home' => ['title' => 'صفحه اصلی', 'slug' => 'home', 'is_home' => true, 'blocks' => [
                    ['id' => 'canvas_section_1', 'type' => 'section', 'props' => ['padding' => 'md', 'background' => 'transparent'], 'children' => []],
                ]],
                'ext-bridge' => ['title' => 'Extension Bridge', 'slug' => 'ext-bridge', 'is_home' => false, 'blocks' => []],
                'ext-lifecycle' => ['title' => 'Extension Lifecycle', 'slug' => 'ext-lifecycle', 'is_home' => false, 'blocks' => []],
                'ext-throwing' => ['title' => 'Extension Throwing', 'slug' => 'ext-throwing', 'is_home' => false, 'blocks' => []],
            ],
            'header_blocks' => [],
            'footer_blocks' => [],
            'global_styles' => [],
        ];
        if ($this->option('universal')) {
            $canvasThemeData = $this->universalThemeData();
            $this->seedUniversalCustomerData();

            app(ThemeInstanceRevisionService::class)->record(
                $canvasThemeData,
                \App\Models\ThemeInstanceRevision::TYPE_INSTALL,
                [
                    'theme_identifier' => 'universal-multipurpose-qa',
                    'theme_name' => 'قالب جامع چندمنظوره',
                    'active_theme_id' => $this->nullableIntOption('source-theme-id'),
                    'source_theme_id' => $this->nullableIntOption('source-theme-id'),
                    'source_theme_version' => (string) $this->option('source-version'),
                    'source_release_id' => $this->nullableIntOption('source-release-id'),
                    'metadata' => ['qa_fixture' => true],
                ],
            );
            $this->info('Universal multipurpose Theme installed through ThemeInstanceRevisionService.');
        } else {
            Setting::set('active_canvas_theme_data', json_encode($canvasThemeData), 'theme');
        }
        Setting::set('canvas_draft_data', '', 'theme');
        $this->info('Canvas Theme fixture ready (active_canvas_theme_data).');

        $this->info('Browser harness environment ready.');

        if ($this->option('json')) {
            $this->line('BROWSER_HARNESS_JSON:' . json_encode([
                'admin_mobile' => $user->mobile,
                'admin_password' => self::PASSWORD,
                'page_id' => $page->id,
                'page_slug' => $page->slug,
                'control_page_id' => $controlPage->id,
                'control_page_slug' => $controlPage->slug,
                'lifecycle_page_id' => $lifecyclePage->id,
                'lifecycle_page_slug' => $lifecyclePage->slug,
                'extension_page_id' => $extensionPage->id,
                'extension_page_slug' => $extensionPage->slug,
            ]));
        }

        return self::SUCCESS;
    }

    private function nullableIntOption(string $name): ?int
    {
        $value = $this->option($name);
        return $value === null || $value === '' ? null : (int) $value;
    }

    /**
     * QA-only customer instance of the portable SaaS master structure.
     * Inventory is always read from the canonical Canvas registry; the
     * explicit page groupings provide human-usable navigation, and the
     * final coverage page catches every remaining registered type.
     */
    private function universalThemeData(): array
    {
        $registry = BlockRegistry::all();
        $definitions = [
            'home' => ['خانه', '', ['hero', 'hero_split', 'features_grid', 'stats_row', 'project_cards', 'product_grid', 'blog_posts', 'testimonials', 'pricing_cards', 'faq', 'gallery', 'newsletter_form']],
            'about' => ['درباره ما', 'about', ['about_section', 'team', 'team_cards', 'skills_bars', 'client_logos', 'trust_badges']],
            'services' => ['خدمات', 'services', ['services_cards', 'service_cards', 'process_steps', 'cta', 'contact_split']],
            'portfolio' => ['نمونه‌کارها', 'portfolio', ['portfolio_grid', 'bento_grid', 'image', 'video', 'modal']],
            'blog' => ['وبلاگ', 'blog', ['module:blog_posts', 'module:blog_featured', 'module:blog_categories', 'search_bar', 'breadcrumb']],
            'shop' => ['فروشگاه', 'shop', ['products', 'product_slider', 'module:shop_product_grid', 'module:shop_product_slider', 'module:shop_categories', 'cart_icon', 'add_to_cart', 'price_box']],
            'learning' => ['آموزش', 'learning', ['module:courses_grid', 'module:course_enroll_cta', 'module:my_learning_link', 'module:edu_article_grid']],
            'contact' => ['تماس با ما', 'contact', ['contact_info', 'module:contact_quick', 'module:form_embed', 'module:support_button', 'module:whatsapp_widget']],
            'account' => ['حساب کاربری', 'account', ['login_register', 'module:account_quick_links', 'module:subscriber_stats', 'module:referral_widget', 'module:invoice_quick_pay']],
            'utilities' => ['امکانات', 'utilities', ['tabs', 'accordion', 'slider', 'countdown', 'promo_banner', 'email_capture', 'language_switcher', 'module:motion']],
        ];

        $block = function (string $type) use ($registry): array {
            $props = $registry[$type]['defaultProps'] ?? [];
            $overrides = match ($type) {
                'hero' => ['title' => 'راهکار جامع برای کسب‌وکار شما', 'subtitle' => 'یک سایت کامل برای معرفی، فروش، آموزش و ارتباط با مشتری', 'button_text' => 'مشاهده خدمات', 'button_url' => '/page/services'],
                'nav_header' => ['logo_text' => 'قالب جامع', 'nav_links' => [
                    ['label' => 'خانه', 'url' => '/'], ['label' => 'خدمات', 'url' => '/page/services'],
                    ['label' => 'فروشگاه', 'url' => '/page/shop'], ['label' => 'وبلاگ', 'url' => '/page/blog'],
                    ['label' => 'تماس', 'url' => '/page/contact'],
                ], 'sticky' => true],
                'nav_footer' => ['logo_text' => 'قالب جامع', 'tagline' => 'سطح مرجع کنترل کیفیت Builder', 'copyright' => 'QA'],
                'image' => ['url' => '/storage/theme-media/qa-existing-a.svg', 'alt' => 'تصویر نمونه قالب جامع', 'caption' => 'رسانه نمونه'],
                'gallery' => ['items' => [['url' => '/storage/theme-media/qa-existing-a.svg', 'alt' => 'نمونه نخست', 'qa_unknown' => 'preserve-gallery']]],
                'team_cards' => ['members' => json_encode([[
                    'name' => 'عضو نمونه', 'role' => 'مدیر', 'image' => '', 'bio' => 'توضیح عضو',
                    'qa_unknown' => 'preserve-team',
                ]], JSON_UNESCAPED_UNICODE)],
                default => [],
            };

            return [
                'id' => 'qa_' . str_replace([':', '-'], '_', $type),
                'type' => $type,
                'props' => array_replace($props, $overrides),
                'children' => [],
            ];
        };

        $used = [];
        $pages = [];
        foreach ($definitions as $key => [$title, $slug, $types]) {
            $blocks = [];
            foreach ($types as $type) {
                if (!isset($registry[$type])) continue;
                $blocks[] = $block($type);
                $used[$type] = true;
            }
            $pages[$key] = ['title' => $title, 'slug' => $slug, 'is_home' => $key === 'home', 'blocks' => $blocks];
        }
        $pages['builder-coverage'] = [
            'title' => 'پوشش عناصر سازنده',
            'slug' => 'builder-coverage',
            'is_home' => false,
            'blocks' => array_map($block, array_keys(array_diff_key($registry, $used))),
        ];

        return [
            'pages' => $pages,
            'header_blocks' => [$block('nav_header'), $block('nav_menu')],
            'footer_blocks' => [$block('cta'), $block('nav_footer'), $block('social_icons')],
            'global_styles' => [
                'primary_color' => '#4f46e5', 'secondary_color' => '#6366f1',
                'background_color' => '#f8fafc', 'text_color' => '#111827',
            ],
            'settings' => ['qa_fixture' => true],
        ];
    }

    private function seedUniversalCustomerData(): void
    {
        \App\Models\License::create([
            'license_key' => 'BROWSER-UNIVERSAL-QA',
            'domain' => '127.0.0.1',
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => collect(BlockRegistry::all())->pluck('requires')->filter()->unique()->values()->all(),
        ]);

        \App\Models\Post::create([
            'title' => 'خبر نمونه قالب جامع', 'slug' => 'universal-qa-post',
            'body' => 'محتوای قطعی برای کنترل کیفیت.', 'excerpt' => 'خلاصه محتوای نمونه',
            'is_published' => true, 'editorial_status' => \App\Models\Post::EDITORIAL_PUBLISHED,
            'published_at' => now(), 'user_id' => \App\Models\User::query()->value('id'),
        ]);
        \App\Models\Product::create([
            'title' => 'محصول نمونه قالب جامع', 'slug' => 'universal-qa-product',
            'description' => 'محصول قطعی کنترل کیفیت', 'sku' => 'UNIVERSAL-QA-1',
            'price' => 125000, 'stock' => 10, 'status' => \App\Models\Product::STATUS_PUBLISHED,
        ]);
        \App\Models\Course::create([
            'title' => 'دوره نمونه قالب جامع', 'slug' => 'universal-qa-course',
            'description' => 'دوره قطعی کنترل کیفیت', 'price' => 0, 'is_active' => true,
        ]);
        \App\Models\Form::create(['title' => 'فرم درخواست قالب جامع', 'slug' => 'universal-qa-form', 'is_active' => true]);

        \Illuminate\Support\Facades\Storage::disk('public')->put(
            'theme-media/qa-existing-a.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180"><rect width="320" height="180" fill="#4f46e5"/><text x="32" y="100" fill="white" font-size="24">QA A</text></svg>'
        );
        \Illuminate\Support\Facades\Storage::disk('public')->put(
            'theme-media/qa-existing-b.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180"><rect width="320" height="180" fill="#059669"/><text x="32" y="100" fill="white" font-size="24">QA B</text></svg>'
        );
    }
}

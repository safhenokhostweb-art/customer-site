<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Services\CustomerMediaService;
use App\Services\ThemeCanvas\BlockRegistry;
use App\Services\ThemeCanvas\ThemeInstanceRevisionService;
use App\Support\Installation;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
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

    // MEDIA-3 browser acceptance: this single deployment has exactly one
    // PLATFORM_SITE_ID (site isolation is enforced per-row, not per-request
    // — there is no second live site to log into within one running
    // instance). A genuinely foreign-site row is seeded here, through the
    // real CustomerMediaService, so the browser suite can prove real
    // server-side denial against real HTTP requests instead of mocking it.
    public const MEDIA_FOREIGN_SITE_ID = 999999;
    public const MEDIA_FOREIGN_TITLE = 'Cross-Site QA Media (must never be visible)';
    public const MEDIA_USED_TITLE = 'Used QA Media (delete must be rejected)';
    public const MEDIA_PAGINATION_COUNT = 30;
    public const MEDIA_PAGINATION_TITLE_PREFIX = 'Pagination QA Media ';

    /**
     * Real CustomerMedia UUIDs seeded by seedUniversalMedia() (hero/about/
     * portfolio1/portfolio2/video — each already used live throughout the
     * real Theme content), captured here so handle() can expose them in
     * --json for browser specs to target deterministically. Builder
     * integrity audit (2026-09-11): restores the permanent regression
     * surface after MEDIA-4 moved canonical media identity to real UUIDs —
     * specs must reference a real, known UUID, never a filename.
     *
     * @var array<string,string>
     */
    private array $universalMediaUuids = [];

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

        [$foreignMediaUuid, $usedMediaUuid] = $this->seedCustomerMediaFixtures();
        $this->info("Cross-site QA media ready: uuid={$foreignMediaUuid}");
        $this->info("Used QA media ready: uuid={$usedMediaUuid}");

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
                'foreign_media_uuid' => $foreignMediaUuid,
                'foreign_media_title' => self::MEDIA_FOREIGN_TITLE,
                'used_media_uuid' => $usedMediaUuid,
                'used_media_title' => self::MEDIA_USED_TITLE,
                // Real CustomerMedia UUIDs already used live throughout the
                // universal Theme content (hero/about/portfolio1/portfolio2/
                // video) — the current canonical way for a browser spec to
                // target a known library item deterministically.
                'universal_media_uuids' => $this->universalMediaUuids,
                'pagination_media_count' => self::MEDIA_PAGINATION_COUNT,
                'pagination_media_title_prefix' => self::MEDIA_PAGINATION_TITLE_PREFIX,
            ]));
        }

        return self::SUCCESS;
    }

    /**
     * MEDIA-3 browser acceptance needs three preconditions that cannot be
     * produced by clicking through the UI: a media row that genuinely
     * belongs to a different site (isolation/forged-access proof), a media
     * row with a real registered usage (delete-rejection proof), and enough
     * site-scoped rows to cross the real pagination threshold. All three go
     * through CustomerMediaService — the sole write authority — never a
     * direct DB write, exactly like every other fixture in this command.
     *
     * @return array{0:string,1:string} [foreignMediaUuid, usedMediaUuid]
     */
    private function seedCustomerMediaFixtures(): array
    {
        $service = app(CustomerMediaService::class);
        $realSiteId = config('site.platform_site_id');

        config(['site.platform_site_id' => self::MEDIA_FOREIGN_SITE_ID]);
        $foreignMedia = $service->upload(
            $this->fakeUploadedPng('foreign-site-qa.png'),
            ['title' => self::MEDIA_FOREIGN_TITLE],
        );
        config(['site.platform_site_id' => $realSiteId]);

        $usedMedia = $service->upload(
            $this->fakeUploadedPng('used-qa.png'),
            ['title' => self::MEDIA_USED_TITLE],
        );
        $service->registerUsage($usedMedia, 'theme_instance_revision', 1, '.qa_fixture.media_id');

        for ($i = 1; $i <= self::MEDIA_PAGINATION_COUNT; $i++) {
            $service->upload(
                $this->fakeUploadedPng(sprintf('pagination-qa-%02d.png', $i)),
                ['title' => self::MEDIA_PAGINATION_TITLE_PREFIX . sprintf('%02d', $i)],
            );
        }

        return [$foreignMedia->uuid, $usedMedia->uuid];
    }

    private function fakeUploadedPng(string $originalName): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'qa_media_');
        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL8WQAAAABJRU5ErkJggg=='
        ));

        return new UploadedFile($path, $originalName, 'image/png', null, true);
    }

    private function nullableIntOption(string $name): ?int
    {
        $value = $this->option($name);
        return $value === null || $value === '' ? null : (int) $value;
    }

    /**
     * QA-only customer instance of the portable SaaS master structure, AND
     * the real, installed public-render regression surface for the Builder
     * integrity audit (2026-09-11) — real Persian marketing content, real
     * CustomerMedia (image + video, resolved through the canonical
     * {media_id} path, never a raw path/URL for a new selection), and every
     * newly-implemented module renderer (§G/§P/§J of that audit) placed on
     * a real page. Inventory is always read from the canonical Canvas
     * registry; the final coverage page catches every remaining registered
     * type with its own honest schema defaults.
     *
     * 'portfolio_grid' (bare, non-'module:' prefixed) does not exist
     * anywhere in the merged registry — the previous version of this
     * fixture referenced it, which silently no-op'd via the isset() guard
     * below. Replaced with real 'gallery'/'bento_grid' content.
     */
    private function universalThemeData(): array
    {
        $registry = BlockRegistry::all();
        $media = $this->seedUniversalMedia();

        $definitions = [
            'home' => ['خانه', '', [
                'hero', 'client_logos', 'features_grid', 'stats_row', 'hero_split',
                'gallery', 'product_grid', 'module:courses_grid', 'blog_posts', 'team_cards',
                'testimonials_slider', 'pricing_cards', 'bento_grid', 'faq', 'newsletter_form', 'cta',
            ]],
            'about' => ['درباره ما', 'about', ['hero_gradient', 'team', 'team_cards', 'skills_bars', 'client_logos', 'trust_badges', 'process_steps']],
            'services' => ['خدمات', 'services', ['services_cards', 'service_cards', 'process_steps', 'cta', 'contact_split']],
            'portfolio' => ['نمونه‌کارها', 'portfolio', ['gallery', 'bento_grid', 'image', 'video']],
            'pricing' => ['تعرفه‌ها', 'pricing', ['pricing_cards', 'module:pricing_table', 'trust_badges', 'faq']],
            'team' => ['تیم ما', 'team', ['team_cards', 'team']],
            'testimonials' => ['نظرات مشتریان', 'testimonials', ['testimonials_slider', 'module:reviews_grid']],
            'blog' => ['وبلاگ', 'blog', ['module:blog_featured', 'module:blog_posts', 'module:blog_categories', 'search_bar', 'breadcrumb']],
            'shop' => ['فروشگاه', 'shop', ['module:shop_search_box', 'products', 'product_slider', 'module:shop_product_grid', 'module:shop_product_slider', 'module:shop_categories', 'module:shop_countdown', 'cart_icon', 'add_to_cart', 'price_box']],
            'learning' => ['آموزش', 'learning', ['module:courses_grid', 'module:course_enroll_cta', 'module:my_learning_link', 'module:edu_article_grid']],
            'contact' => ['تماس با ما', 'contact', ['contact_info', 'module:contact_quick', 'module:form_embed', 'module:support_button', 'module:whatsapp_widget', 'module:business_card_embed']],
            'account' => ['حساب کاربری', 'account', ['login_register', 'module:account_quick_links', 'module:subscriber_stats', 'module:referral_widget', 'module:invoice_quick_pay']],
            'content' => ['مطالب', 'content', ['module:content_cards']],
            'search' => ['جستجو', 'search', ['module:site_search']],
            'utilities' => ['امکانات', 'utilities', ['tabs', 'accordion', 'slider', 'countdown', 'promo_banner', 'email_capture', 'language_switcher', 'module:motion', 'module:notifications_bar', 'module:advertisement_zone']],
        ];

        $block = function (string $type) use ($registry, $media): array {
            $props = $registry[$type]['defaultProps'] ?? [];
            $overrides = match ($type) {
                'hero' => [
                    'title' => 'راهکار جامع برای کسب‌وکار شما', 'subtitle' => 'یک سایت کامل برای معرفی، فروش، آموزش و ارتباط با مشتری',
                    'button_text' => 'مشاهده خدمات', 'button_url' => '/page/services', 'background' => 'primary',
                    'background_image' => $media['hero'],
                ],
                'hero_split' => [
                    'badge' => 'معرفی می‌کنیم', 'title' => 'مدیریت فروشگاه، ساده و سریع',
                    'subtitle' => 'از افزودن محصول تا دریافت پرداخت، همه در یک داشبورد.',
                    'button_text' => 'شروع رایگان', 'button_url' => '/account', 'button2_text' => 'مشاهده دمو', 'button2_url' => '/page/portfolio',
                    'image_url' => $media['about'], 'image_side' => 'left',
                ],
                'hero_gradient' => [
                    'badge' => 'دربارهٔ ما', 'title' => 'ساخته‌شده برای کسب‌وکارهای ایرانی',
                    'subtitle' => 'از ۱۳۹۸ در حال کمک به هزاران کسب‌وکار برای حضور آنلاین حرفه‌ای.',
                    'button_text' => 'تماس با ما', 'button_url' => '/page/contact', 'button2_text' => '', 'button2_url' => '#',
                ],
                'features_grid' => [
                    'title' => 'چرا ما را انتخاب کنید؟', 'subtitle' => 'ابزارهایی که واقعاً به آن‌ها نیاز دارید',
                    'columns' => '3', 'style' => 'cards',
                    'features' => [
                        ['icon' => '🎨', 'title' => 'سازندهٔ بصری', 'text' => 'طراحی بدون کدنویسی'],
                        ['icon' => '🛒', 'title' => 'فروشگاه کامل', 'text' => 'مدیریت محصول و پرداخت'],
                        ['icon' => '🎓', 'title' => 'آموزش آنلاین', 'text' => 'فروش دوره و دانشجویان'],
                    ],
                ],
                'stats_row' => [
                    'title' => '', 'background' => '#f8fafc', 'dividers' => true,
                    'stats' => [
                        ['value' => 3200, 'suffix' => '+', 'label' => 'کسب‌وکار فعال'],
                        ['value' => 98, 'suffix' => '%', 'label' => 'رضایت مشتری'],
                        ['value' => 5, 'suffix' => ' سال', 'label' => 'سابقه'],
                    ],
                ],
                'services_cards', 'service_cards' => [
                    'title' => 'خدمات ما', 'subtitle' => 'هر آنچه برای رشد کسب‌وکار لازم دارید', 'columns' => '3', 'style' => 'bordered',
                    'services' => [
                        ['icon' => '🎨', 'title' => 'طراحی سایت', 'text' => 'طراحی اختصاصی مطابق برند شما', 'link' => '/page/contact', 'link_text' => 'بیشتر بدانید'],
                        ['icon' => '🛒', 'title' => 'راه‌اندازی فروشگاه', 'text' => 'از کاتالوگ تا درگاه پرداخت', 'link' => '/page/shop', 'link_text' => 'مشاهده'],
                    ],
                ],
                'process_steps' => [
                    'title' => 'چطور شروع کنیم؟', 'subtitle' => 'سه گام ساده',
                    'steps' => [
                        ['number' => '۱', 'title' => 'ثبت‌نام رایگان', 'text' => 'در چند ثانیه حساب بسازید'],
                        ['number' => '۲', 'title' => 'انتخاب قالب', 'text' => 'قالب دلخواه را انتخاب کنید'],
                        ['number' => '۳', 'title' => 'انتشار', 'text' => 'سایت خود را منتشر کنید'],
                    ],
                ],
                'skills_bars' => [
                    'title' => 'توانمندی‌های زیرساخت',
                    'skills' => [['name' => 'پایداری سرور', 'percent' => 99], ['name' => 'سرعت بارگذاری', 'percent' => 92]],
                ],
                'client_logos' => [
                    'title' => 'مورد اعتماد کسب‌وکارهای موفق',
                    'logos' => [['name' => 'دیجی‌مد'], ['name' => 'آموزشگاه رشد'], ['name' => 'کلینیک سلامت'], ['name' => 'استودیو نقش']],
                    'scroll' => true,
                ],
                'trust_badges' => [
                    'badges' => [
                        ['icon' => '↩️', 'title' => 'بازگشت وجه', 'text' => 'تا ۷ روز'],
                        ['icon' => '🔒', 'title' => 'پرداخت امن', 'text' => 'درگاه‌های معتبر'],
                        ['icon' => '☎️', 'title' => 'پشتیبانی ۲۴/۷', 'text' => 'همیشه در دسترس'],
                    ],
                    'background' => '#f8fafc',
                ],
                'pricing_cards' => [
                    'title' => 'پلن مناسب خود را انتخاب کنید', 'subtitle' => 'بدون تعهد، هر زمان قابل تغییر',
                    'plans' => [
                        ['name' => 'استارتر', 'price' => 'رایگان', 'period' => '', 'badge' => '', 'highlight' => false, 'features' => ['۱ صفحه فرود', '۵۰۰ مگابایت فضا'], 'cta_text' => 'شروع رایگان', 'cta_url' => '/account'],
                        ['name' => 'حرفه‌ای', 'price' => '۴۹۰', 'period' => 'هزار تومان / ماه', 'badge' => 'محبوب‌ترین', 'highlight' => true, 'features' => ['سایت نامحدود', 'فروشگاه کامل'], 'cta_text' => 'انتخاب پلن', 'cta_url' => '/page/pricing'],
                    ],
                ],
                'bento_grid' => [
                    'title' => 'همه‌چیز در یک پلتفرم',
                    'items' => [
                        ['title' => 'مدیریت هوشمند', 'text' => 'داشبورد جامع', 'icon' => '📊', 'size' => 'large', 'bg_color' => '#4f46e5', 'text_color' => '#ffffff'],
                        ['title' => 'امنیت پیشرفته', 'text' => 'رمزنگاری کامل', 'icon' => '🔒', 'size' => 'small', 'bg_color' => '#f0fdf4', 'text_color' => '#14532d'],
                    ],
                ],
                'faq' => [
                    'title' => 'سوالات متداول',
                    'items' => [
                        ['question' => 'آیا نیاز به دانش فنی دارم؟', 'answer' => 'خیر، سازندهٔ بصری بدون کدنویسی کار می‌کند.'],
                        ['question' => 'آیا می‌توانم پلن را تغییر دهم؟', 'answer' => 'بله، در هر زمان ممکن است.'],
                    ],
                ],
                'newsletter_form' => [
                    'title' => 'در خبرنامه عضو شوید', 'subtitle' => 'جدیدترین اخبار را دریافت کنید',
                    'placeholder' => 'ایمیل شما', 'button_text' => 'عضویت', 'background' => '#4f46e5', 'text_color' => '#ffffff',
                ],
                'cta' => [
                    'title' => 'همین امروز شروع کنید', 'subtitle' => 'بدون نیاز به کارت اعتباری',
                    'button_text' => 'ساخت سایت رایگان', 'button_url' => '/account', 'background' => 'primary',
                ],
                'contact_split' => [
                    'title' => 'با ما در ارتباط باشید', 'subtitle' => 'هر سوالی دارید، پاسخگو هستیم',
                    'phone' => '۰۲۱-۱۲۳۴۵۶۷۸', 'email' => 'info@yourbrand.ir', 'address' => 'تهران، خیابان ولیعصر', 'show_form' => true,
                ],
                'contact_info' => [
                    'title' => 'اطلاعات تماس', 'background' => '',
                    'items' => [['icon' => '📞', 'label' => 'تلفن', 'value' => '۰۲۱-۱۲۳۴۵۶۷۸'], ['icon' => '✉️', 'label' => 'ایمیل', 'value' => 'info@yourbrand.ir']],
                ],
                'nav_header' => ['logo_text' => 'قالب جامع', 'nav_links' => [
                    ['label' => 'خانه', 'url' => '/'], ['label' => 'خدمات', 'url' => '/page/services'],
                    ['label' => 'فروشگاه', 'url' => '/page/shop'], ['label' => 'وبلاگ', 'url' => '/page/blog'],
                    ['label' => 'تماس', 'url' => '/page/contact'],
                ], 'sticky' => true, 'show_search' => true, 'show_cart' => true],
                'nav_footer' => [
                    'logo_text' => 'قالب جامع', 'tagline' => 'سطح مرجع کنترل کیفیت Builder', 'copyright' => '© QA — قالب جامع چندمنظوره',
                    'columns' => [['title' => 'دسترسی سریع', 'links' => [['label' => 'درباره ما', 'url' => '/page/about'], ['label' => 'تماس', 'url' => '/page/contact']]]],
                ],
                'social_icons' => ['links' => [['platform' => 'instagram', 'url' => 'https://instagram.com/qa'], ['platform' => 'telegram', 'url' => 'https://t.me/qa']]],
                'image' => ['url' => $media['portfolio1'], 'alt' => 'تصویر نمونه قالب جامع', 'caption' => 'رسانهٔ واقعی — از مسیر CustomerMediaService'],
                'video' => ['url' => $media['video'], 'poster' => $media['portfolio2'], 'caption' => 'ویدیوی نمونه', 'controls' => true],
                // 'qa_unknown' on item 0 of each repeater below is a
                // deliberate unrecognized/legacy extra key — the browser
                // regression surface proves the structured repeater editor
                // preserves it untouched while other fields/items are
                // edited (it must never be silently dropped).
                'gallery' => ['title' => 'گالری تصاویر', 'columns' => '3', 'items' => [
                    ['url' => $media['portfolio1'], 'alt' => 'نمونه‌کار اول', 'qa_unknown' => 'preserve-gallery'],
                    ['url' => $media['portfolio2'], 'alt' => 'نمونه‌کار دوم'],
                    ['url' => $media['about'], 'alt' => 'نمونه‌کار سوم'],
                ]],
                'team_cards' => ['title' => 'با تیم ما آشنا شوید', 'columns' => '3', 'members' => [
                    ['name' => 'سارا احمدی', 'role' => 'مدیرعامل', 'image' => $media['about'], 'bio' => 'بنیان‌گذار پلتفرم', 'qa_unknown' => 'preserve-team'],
                    ['name' => 'رضا کریمی', 'role' => 'مدیر فنی', 'image' => '', 'bio' => 'معمار زیرساخت'],
                ]],
                'testimonials_slider' => [
                    'title' => 'مشتریان ما چه می‌گویند', 'columns' => '3', 'autoplay' => true, 'interval' => 5000,
                    'items' => [
                        ['name' => 'امیر رستمی', 'role' => 'صاحب فروشگاه', 'text' => 'در کمتر از یک هفته فروشگاهم آماده بود.', 'rating' => 5],
                        ['name' => 'مریم صادقی', 'role' => 'موسس آموزشگاه', 'text' => 'بخش دوره‌ها دقیقاً همان چیزی بود که نیاز داشتم.', 'rating' => 5],
                        ['name' => 'حسین یزدانی', 'role' => 'مدیر کلینیک', 'text' => 'سازندهٔ بصری فوق‌العاده ساده است.', 'rating' => 4],
                    ],
                ],
                'slider' => ['autoplay' => true, 'interval' => 4000, 'slides' => [
                    ['id' => 'qa_slide_1', 'type' => 'heading', 'props' => ['text' => 'اسلاید اول — واقعی', 'level' => '2'], 'children' => []],
                    ['id' => 'qa_slide_2', 'type' => 'heading', 'props' => ['text' => 'اسلاید دوم — واقعی', 'level' => '2'], 'children' => []],
                    ['id' => 'qa_slide_3', 'type' => 'heading', 'props' => ['text' => 'اسلاید سوم — واقعی', 'level' => '2'], 'children' => []],
                ]],
                'product_slider' => ['title' => 'محصولات منتخب', 'limit' => 6, 'autoplay' => true, 'interval' => 5000],
                'countdown' => ['end_date' => date('Y-m-d', strtotime('+14 days')), 'label' => 'پایان جشنوارهٔ فروش ویژه'],
                'module:blog_posts' => ['title' => 'آخرین مطالب', 'limit' => 6, 'layout' => 'grid', 'show_excerpt' => true, 'show_date' => true],
                'module:shop_product_grid' => ['title' => 'همهٔ محصولات', 'limit' => 12, 'columns' => '4'],
                'module:courses_grid' => ['title' => 'دوره‌های آموزشی', 'limit' => 6, 'columns' => '3', 'layout' => 'grid', 'show_price' => true],
                'module:contact_quick' => ['title' => 'تماس سریع', 'show_phone' => true, 'show_address' => true, 'show_map' => true],
                'module:faq_dynamic' => ['title' => 'سوالات بیشتر', 'limit' => 10, 'style' => 'list', 'items' => [
                    ['question' => 'آیا SSL رایگان است؟', 'answer' => 'بله، به‌صورت خودکار فعال می‌شود.'],
                    ['question' => 'محدودیت ترافیک؟', 'answer' => 'در پلن حرفه‌ای نامحدود است.'],
                ]],
                'module:social_links_bar' => ['style' => 'horizontal', 'color' => 'brand', 'size' => 'md'],
                'module:whatsapp_widget' => ['label' => 'چت واتساپ', 'phone' => '', 'position' => 'bottom-left'],
                'module:form_embed' => ['title' => 'فرم درخواست مشاوره', 'show_title' => true, 'style' => 'card'],
                'module:content_cards' => ['title' => 'آخرین مطالب', 'limit' => 3, 'style' => 'cards', 'show_excerpt' => true],
                'module:site_search' => ['placeholder' => 'جستجو در کل سایت...', 'style' => 'hero'],
                'module:shop_search_box' => ['placeholder' => 'جستجوی محصول...', 'style' => 'bar'],
                'module:shop_countdown' => ['promo_text' => 'پیشنهاد ویژه — فرصت محدود!', 'end_date' => date('Y-m-d', strtotime('+7 days')), 'bg_color' => '#ef4444'],
                'module:advertisement_zone' => ['zone_name' => 'qa-banner-main', 'position' => 'banner'],
                'module:business_card_embed' => ['card_slug' => 'qa-business-card'],
                'module:invoice_quick_pay' => ['title' => 'پرداخت فاکتور', 'placeholder' => 'شماره فاکتور را وارد کنید'],
                'module:account_quick_links' => ['style' => 'buttons', 'show_avatar' => true, 'show_notifications' => true],
                default => [],
            };

            // ElementSchemaRegistry validates type:'textarea' JSON fields as
            // a STRING (unlike type:'repeater', which accepts a real PHP
            // array) — a raw array here passes the renderer's own flexible
            // is_array()-or-json_decode() parsing but fails validation and
            // falls to the [invalid] placeholder before the renderer is
            // ever reached. Every textarea-JSON field used above is
            // encoded here, once, rather than per call site.
            $textareaJsonFields = [
                'features_grid' => ['features'], 'stats_row' => ['stats'], 'services_cards' => ['services'],
                'service_cards' => ['services'], 'process_steps' => ['steps'], 'skills_bars' => ['skills'],
                'client_logos' => ['logos'], 'trust_badges' => ['badges'], 'pricing_cards' => ['plans'],
                'bento_grid' => ['items'], 'testimonials_slider' => ['items'],
            ];
            foreach ($textareaJsonFields[$type] ?? [] as $key) {
                if (isset($overrides[$key]) && is_array($overrides[$key])) {
                    $overrides[$key] = json_encode($overrides[$key], JSON_UNESCAPED_UNICODE);
                }
            }

            return [
                'id' => 'qa_' . str_replace([':', '-'], '_', $type),
                'type' => $type,
                'props' => array_replace($props, $overrides),
                'children' => BlockRegistry::isContainerType($type) ? [] : [],
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
            'footer_blocks' => [$block('newsletter_form'), $block('nav_footer'), $block('social_icons')],
            'global_styles' => [
                'primary_color' => '#4f46e5', 'secondary_color' => '#6366f1',
                'background_color' => '#f8fafc', 'text_color' => '#111827',
            ],
            'settings' => ['qa_fixture' => true],
        ];
    }

    /**
     * Real CustomerMedia (§H) for the harness — images via GD-generated
     * JPEGs, one real ISO-BMFF-valid MP4 for the video block (§I proof).
     * Every image-capable prop above references these through the
     * canonical {media_id} shape, resolved by CustomerMediaResolver —
     * never a raw path for a new selection.
     *
     * @return array<string,array{media_id:string}>
     */
    private function seedUniversalMedia(): array
    {
        $service = app(\App\Services\CustomerMediaService::class);
        $keys = ['hero' => [1600, 900], 'about' => [1200, 900], 'portfolio1' => [1000, 750], 'portfolio2' => [1000, 750]];

        $out = [];
        foreach ($keys as $name => [$w, $h]) {
            $media = $service->upload(UploadedFile::fake()->image("qa-{$name}.jpg", $w, $h), ['title' => "QA {$name}"]);
            $out[$name] = ['media_id' => $media->uuid];
            $this->universalMediaUuids[$name] = $media->uuid;
        }

        $videoMedia = $service->upload($this->fakeUploadedMp4('qa-video.mp4'), ['title' => 'QA Video']);
        $out['video'] = ['media_id' => $videoMedia->uuid];
        $this->universalMediaUuids['video'] = $videoMedia->uuid;

        return $out;
    }

    /** Minimal but real ISO-BMFF box structure (ftyp+free+moov+mdat) — recognized as video/mp4 by real MIME sniffing, mirrors tests/browser/media-library.spec.js's own mp4() fixture. */
    private function fakeUploadedMp4(string $originalName): UploadedFile
    {
        $box = function (string $type, string $payload): string {
            return pack('N', 8 + strlen($payload)) . $type . $payload;
        };
        $ftyp = $box('ftyp', 'isom' . "\x00\x00\x02\x00" . 'isomiso2avc1mp41');
        $free = $box('free', '');
        $moov = $box('moov', $box('mvhd', str_repeat("\x00", 100)));
        $mdat = $box('mdat', str_repeat("\x00", 32));

        $path = tempnam(sys_get_temp_dir(), 'qa_media_');
        file_put_contents($path, $ftyp . $free . $moov . $mdat);

        return new UploadedFile($path, $originalName, 'video/mp4', null, true);
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

        // Builder integrity audit (2026-09-11): real backing rows for the
        // newly-implemented module:advertisement_zone,
        // module:business_card_embed, and module:content_cards renderers —
        // otherwise those blocks would only ever prove their own honest
        // "not found" state, not their real-data path.
        \App\Models\Advertisement::create([
            'type' => 'banner', 'title' => 'تبلیغ نمونه قالب جامع',
            'content' => 'محتوای تبلیغ قطعی برای کنترل کیفیت.', 'link' => '/page/shop',
            'position' => 'qa-banner-main', 'order' => 1, 'is_active' => true,
        ]);
        \App\Models\BusinessCard::create([
            'name' => 'سارا احمدی', 'title' => 'مدیرعامل', 'company' => 'برند شما',
            'phone' => '۰۲۱-۱۲۳۴۵۶۷۸', 'email' => 'sara@yourbrand.ir', 'website' => 'https://yourbrand.ir',
            'address' => 'تهران', 'slug' => 'qa-business-card', 'is_active' => true, 'order' => 1,
        ]);
        \App\Models\SiteContent::create([
            'type' => \App\Models\SiteContent::TYPE_ARTICLE, 'title' => 'مقالهٔ نمونه قالب جامع',
            'slug' => 'universal-qa-content', 'excerpt' => 'خلاصهٔ محتوای نمونه',
            'body' => 'محتوای قطعی برای کنترل کیفیت بخش کارت‌های محتوا.', 'status' => \App\Models\SiteContent::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

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

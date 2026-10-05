<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Services\CustomerMediaService;
use App\Services\MediaStorage;
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
        $foodMartFixture = $this->seedFoodMartBindingFixtures();
        $canvasThemeData['pages']['foodmart-binding'] = $foodMartFixture['page'];
        $canvasThemeData['pages']['foodmart-editing'] = $foodMartFixture['editing_page'];
        $sleepDefinitions = \SaaSPlatform\ThemeRuntime\SleepShimanBlockTypes::definitions();
        $sleepShimanStats = $sleepDefinitions['sleepshiman_stats']['defaultProps'];
        $sleepFeatured = $sleepDefinitions['sleepshiman_featured_product']['defaultProps'];
        $sleepAssetPath = 'browser-harness/sleepshiman/pillow-green.webp';
        $sleepAssetSource = base_path('../saas-platform/public/theme-assets/sleepshiman-home-blue/1.0.0/images/pillow-green.webp');
        if (is_file($sleepAssetSource)) {
            \Illuminate\Support\Facades\Storage::disk('public')->put($sleepAssetPath, file_get_contents($sleepAssetSource));
            $sleepFeatured['items'][0]['image'] = \Illuminate\Support\Facades\Storage::disk('public')->url($sleepAssetPath);
        }
        $sleepShimanPage = [
            'title' => 'SleepShiman Acceptance',
            'slug' => 'sleepshiman-acceptance',
            'is_home' => false,
            'blocks' => [
                ['id' => 'sleepshiman-featured-manual', 'type' => 'sleepshiman_featured_product', 'props' => $sleepFeatured],
                ['id' => 'sleepshiman-featured-dynamic', 'type' => 'sleepshiman_featured_product', 'props' => $sleepFeatured, 'binding' => [
                    'source' => 'products', 'query' => ['sort' => 'manual', 'selected_ids' => [$foodMartFixture['product_id']], 'limit' => 1], 'empty' => 'manual',
                ]],
                ['id' => 'sleepshiman-featured-fallback', 'type' => 'sleepshiman_featured_product', 'props' => $sleepFeatured, 'binding' => [
                    'source' => 'products', 'query' => ['sort' => 'manual', 'selected_ids' => [999999], 'limit' => 1], 'empty' => 'manual',
                ]],
                ['id' => 'sleepshiman-stats-acceptance', 'type' => 'sleepshiman_stats', 'props' => $sleepShimanStats],
            ],
        ];
        $canvasThemeData['pages']['sleepshiman-acceptance'] = $sleepShimanPage;
        if ($this->option('universal')) {
            $canvasThemeData = $this->loadCanonicalUniversalThemeExport();
            $canvasThemeData['pages']['sleepshiman-acceptance'] = $sleepShimanPage;
            // Real, seeder-produced CustomerMedia library rows for the
            // media-round-trip proof (universal-multipurpose-theme.spec.js
            // selects these by known title/uuid). The canonical export's
            // OWN images are plain saas-platform-hosted URLs on disk (a
            // portable master Theme carries no customer-media UUIDs — see
            // ADR-049 §6); record() below now localizes them through the
            // same canonical boundary a real install uses (Phase 6.5D,
            // BLD-30) — this harness no longer bypasses that contract.
            $this->seedUniversalMedia();
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
                'foodmart_page_slug' => 'foodmart-binding',
                'foodmart_product_id' => $foodMartFixture['product_id'],
                'sleepshiman_page_slug' => 'sleepshiman-acceptance',
            ]));
        }

        return self::SUCCESS;
    }

    /** @return array{page:array<string,mixed>,product_id:int} */
    private function seedFoodMartBindingFixtures(): array
    {
        $fruit = \App\Models\ProductCategory::create(['name' => 'Browser Fresh Fruit', 'slug' => 'browser-fresh-fruit', 'order' => 1]);
        $bakery = \App\Models\ProductCategory::create(['name' => 'Browser Bakery', 'slug' => 'browser-bakery', 'order' => 2]);
        \App\Models\ProductCategory::create(['name' => 'Browser Empty Category', 'slug' => 'browser-empty-category', 'order' => 3]);

        $sale = \App\Models\Product::create([
            'title' => 'Browser Sale Apple', 'slug' => 'browser-sale-apple', 'category_id' => $fruit->id,
            'price' => 120000, 'sale_price' => 90000, 'stock' => 8, 'status' => \App\Models\Product::STATUS_PUBLISHED,
            'is_new' => true, 'views_count' => 40, 'order' => 2,
            'image' => $this->storeBrandedQaImage('foodmart-binding-apple', 'foodmart-binding-fixtures', 640, 640),
        ]);
        \App\Models\ProductImage::create([
            'product_id' => $sale->id,
            'path' => $this->storeBrandedQaImage('foodmart-binding-apple-secondary', 'foodmart-binding-fixtures', 640, 640),
            'sort_order' => 0,
        ]);
        $bread = \App\Models\Product::create([
            'title' => 'Browser Bakery Bread', 'slug' => 'browser-bakery-bread', 'category_id' => $bakery->id,
            'price' => 70000, 'stock' => 12, 'status' => \App\Models\Product::STATUS_PUBLISHED,
            'views_count' => 90, 'order' => 1,
            'image' => $this->storeBrandedQaImage('foodmart-binding-bread', 'foodmart-binding-fixtures', 640, 640),
        ]);
        \App\Models\ProductVariant::create([
            'product_id' => $bread->id, 'sku' => 'BROWSER-BREAD-S', 'label' => 'Small',
            'price_override' => 65000, 'stock' => 4, 'sort_order' => 1, 'min_order_qty' => 2, 'max_order_qty' => 4,
        ]);
        \App\Models\Product::create([
            'title' => 'Browser Sold Out Juice', 'slug' => 'browser-sold-out-juice', 'category_id' => $fruit->id,
            'price' => 50000, 'stock' => 0, 'status' => \App\Models\Product::STATUS_PUBLISHED,
            'views_count' => 4, 'order' => 3,
            'image' => $this->storeBrandedQaImage('foodmart-binding-juice', 'foodmart-binding-fixtures', 640, 640),
        ]);
        \App\Models\Product::create([
            'title' => 'Browser Product With An Intentionally Long Name For Responsive Verification',
            'slug' => 'browser-long-product', 'category_id' => $fruit->id, 'price' => 88000, 'stock' => 3,
            'status' => \App\Models\Product::STATUS_PUBLISHED, 'views_count' => 2, 'order' => 4,
        ]);
        \App\Models\Product::create([
            'title' => 'Browser Hidden Draft', 'slug' => 'browser-hidden-draft', 'category_id' => $fruit->id,
            'price' => 1, 'stock' => 99, 'status' => \App\Models\Product::STATUS_DRAFT,
            'image' => $this->storeBrandedQaImage('foodmart-binding-hidden', 'foodmart-binding-fixtures', 640, 640),
        ]);

        \App\Models\License::updateOrCreate(['domain' => '127.0.0.1'], [
            'license_key' => 'BROWSER-FOODMART-BINDING', 'platform_valid' => true,
            'expires_at' => now()->addYear(), 'last_validated_at' => now(), 'modules' => ['shop'],
        ]);

        $binding = static fn (string $source, string $sort, int $limit): array => [
            'source' => $source, 'query' => ['sort' => $sort, 'limit' => $limit], 'empty' => 'empty_state',
        ];

        return [
            'product_id' => (int) $sale->id,
            'page' => [
                'title' => 'FoodMart Dynamic Binding', 'slug' => 'foodmart-binding', 'is_home' => false,
                'blocks' => [
                    ['id' => 'fm-svg', 'type' => 'foodmart_svg_sprite', 'props' => []],
                    ['id' => 'fm-cart', 'type' => 'foodmart_offcanvas_cart', 'props' => []],
                    ['id' => 'fm-search', 'type' => 'foodmart_offcanvas_search', 'props' => []],
                    ['id' => 'fm-header', 'type' => 'foodmart_header', 'props' => []],
                    ['id' => 'fm-binding-categories', 'type' => 'foodmart_categories', 'props' => [], 'binding' => $binding('product_categories', 'catalog_order', 12)],
                    ['id' => 'fm-binding-products', 'type' => 'foodmart_trending_products', 'props' => [], 'binding' => $binding('products', 'most_viewed', 10)],
                    ['id' => 'fm-binding-arrivals', 'type' => 'foodmart_product_carousel_just_arrived', 'props' => [], 'binding' => $binding('products', 'new_arrivals', 10)],
                    // Phase 4B.5: a hero block with one editable slide declared
                    // through the generic repeater schema (empty values keep
                    // the byte-exact static output; the editor exposes the
                    // controls as soon as the block carries the schema).
                    ['id' => 'fm-hero', 'type' => 'foodmart_hero', 'props' => [
                        'slides' => [['label' => '', 'title' => 'Fresh Smoothie & Summer Juice', 'description' => '', 'cta_label' => '', 'cta_url' => '', 'image' => '']],
                    ]],
                    ['id' => 'fm-footer', 'type' => 'foodmart_footer', 'props' => []],
                ],
            ],
            // Phase 4B.6 user-acceptance editing page: every remaining
            // content-bearing block, with manual items pre-populated so the
            // spec edits REAL visible cards (not empty repeaters).
            'editing_page' => [
                'title' => 'FoodMart Editing Acceptance', 'slug' => 'foodmart-editing', 'is_home' => false,
                'blocks' => [
                    ['id' => 'fme-svg', 'type' => 'foodmart_svg_sprite', 'props' => []],
                    ['id' => 'fme-header', 'type' => 'foodmart_header', 'props' => [
                        'nav_items' => [
                            ['label' => 'Home', 'url' => 'index.html'],
                            ['label' => 'Shop', 'url' => 'shop.html'],
                        ],
                    ]],
                    ['id' => 'fme-products', 'type' => 'foodmart_trending_products', 'props' => [
                        'items' => [
                            ['image' => 'images/thumb-bananas.png', 'image_alt' => '', 'url' => '#', 'badge' => '-14%', 'title' => 'Bananas', 'unit_label' => '1 Unit', 'price_text' => '$2.99', 'old_price_text' => '$3.49', 'cta_label' => 'Shop Now'],
                            ['image' => 'images/thumb-biscuits.png', 'image_alt' => '', 'url' => '#', 'badge' => '', 'title' => 'Biscuits', 'unit_label' => '1 Pack', 'price_text' => '$1.99', 'old_price_text' => '', 'cta_label' => 'Shop Now'],
                        ],
                    ]],
                    ['id' => 'fme-blog', 'type' => 'foodmart_blog', 'props' => [
                        'items' => [
                            ['image' => 'images/post-thumb-1.jpg', 'image_alt' => 'post', 'url' => '#', 'date_text' => '22 Aug 2021', 'category_label' => 'Recipes', 'title' => 'Fresh Produce Fitness', 'excerpt' => 'Balanced snacks for every training day.'],
                        ],
                    ]],
                    ['id' => 'fme-newsletter', 'type' => 'foodmart_newsletter', 'props' => []],
                    ['id' => 'fme-promo', 'type' => 'foodmart_promo_banners', 'props' => []],
                    ['id' => 'fme-trust', 'type' => 'foodmart_trust_badges', 'props' => []],
                    ['id' => 'fme-footer', 'type' => 'foodmart_footer', 'props' => []],
                ],
            ],
        ];
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

    /**
     * Real, deterministic, visually distinct branded JPEG — a vertical
     * gradient plus a seeded-PRNG abstract shape, the same technique
     * saas-platform's own UniversalMultipurposeQaThemeSeeder uses for the
     * Theme's stock imagery. Stored on the real 'uploads' disk (the same
     * one ImageHelper::url()/MediaStorage resolve product/post/course
     * images from) so the returned path is a genuine, working image the
     * public renderer can serve — never a 1×1 placeholder pixel.
     */
    private function storeBrandedQaImage(string $seed, string $directory, int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);

        $from = [0x4f, 0x46, 0xe5];
        $to   = [0x05, 0x96, 0x69];
        for ($y = 0; $y < $height; $y++) {
            $t = $y / max(1, $height - 1);
            $r = (int) round($from[0] + ($to[0] - $from[0]) * $t);
            $g = (int) round($from[1] + ($to[1] - $from[1]) * $t);
            $b = (int) round($from[2] + ($to[2] - $from[2]) * $t);
            $rowColor = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, $width, $y, $rowColor);
        }

        mt_srand(crc32($seed));
        $white = imagecolorallocatealpha($image, 255, 255, 255, 40);
        for ($i = 0; $i < 6; $i++) {
            $radius = mt_rand((int) ($width * 0.08), (int) ($width * 0.22));
            $cx = mt_rand(0, $width);
            $cy = mt_rand(0, $height);
            imagefilledellipse($image, $cx, $cy, $radius, $radius, $white);
        }
        mt_srand();

        $tmp = tempnam(sys_get_temp_dir(), 'qa_branded_') . '.jpg';
        imagejpeg($image, $tmp, 85);
        imagedestroy($image);

        $disk = app(MediaStorage::class)->diskNameFor('uploads');
        $filename = $seed . '.jpg';
        $path = trim($directory, '/') . '/' . $filename;
        \Illuminate\Support\Facades\Storage::disk($disk)->put($path, file_get_contents($tmp));
        @unlink($tmp);

        return $path;
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
     * TASK 3/3 Phase 5 — canonical product convergence. This used to be a
     * ~260-line hand-authored second definition of the Universal
     * Multipurpose Theme (`universalThemeData()`, now removed), independent
     * of saas-platform's real `UniversalMultipurposeQaThemeSeeder` — the
     * exact "duplicate product definition" problem the Phase 5 harness
     * audit was asked to find and close. It is replaced by loading a real,
     * versioned export of the actual canonical product (24 pages, 6 header
     * variants, 6 footer variants — the same shape saas-platform's
     * `ThemeInstallGateway` sends over the wire, verified byte-for-byte
     * against `ThemeCanvasInstallToSiteHttpTest` on that side), then
     * installing it through the exact same `ThemeInstanceRevisionService::
     * record()` call this command already used — no new install authority,
     * no live network dependency on a running saas-platform instance (the
     * export is a static, committed fixture, not a live API call — see
     * `tests/Feature/ThemeCanvas/RealUniversalThemeInstallAcceptanceTest.php`,
     * built from the same file, for the PHPUnit-side proof of this exact
     * payload surviving the real install endpoint).
     *
     * To regenerate this fixture after a real change to the canonical
     * seeder, re-run (from saas-platform):
     *   php artisan tinker --execute="... Theme::where('slug','universal-multipurpose-qa')->sole() -> {pages, header_blocks, footer_blocks, getHeaderVariants(), getFooterVariants(), global_styles} -> json_encode(...)"
     * and overwrite this file's twin at
     * tests/Feature/ThemeCanvas/fixtures/universal-multipurpose-qa-export.json
     * (both copies must stay byte-identical — see the note in that file's
     * directory).
     */
    private function loadCanonicalUniversalThemeExport(): array
    {
        $path = base_path('tests/Feature/ThemeCanvas/fixtures/universal-multipurpose-qa-export.json');
        if (!is_file($path)) {
            throw new \RuntimeException(
                "Canonical Universal Theme export fixture not found at {$path}. This is a real export of ".
                "saas-platform's UniversalMultipurposeQaThemeSeeder, not something to hand-author here again — ".
                'regenerate it from a real saas-platform run rather than reintroducing a duplicate fixture.'
            );
        }
        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded) || empty($decoded['pages']) || empty($decoded['header_variants']) || empty($decoded['footer_variants'])) {
            throw new \RuntimeException("Canonical Universal Theme export fixture is invalid or incomplete: {$path}");
        }

        return $decoded;
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

        // Builder integrity audit (2026-09-20): these three records used to
        // carry no image field at all, so every product/blog/course card
        // rendering them fell back to the theme's generic placeholder icon
        // (📦/🎓) — visually indistinguishable from "broken", and exactly
        // the class of defect this QA fixture exists to catch, not
        // reproduce. Real, deterministic, locally-generated branded JPEGs
        // (same GD-gradient technique saas-platform's own
        // UniversalMultipurposeQaThemeSeeder::generateBrandedImage() uses
        // for the Theme's own stock imagery) close that gap — this fixture
        // must clear the theme's own "no placeholder, no broken image" bar
        // like every other seeded asset here.
        \App\Models\Post::create([
            'title' => 'خبر نمونه قالب جامع', 'slug' => 'universal-qa-post',
            'body' => 'محتوای قطعی برای کنترل کیفیت.', 'excerpt' => 'خلاصه محتوای نمونه',
            'is_published' => true, 'editorial_status' => \App\Models\Post::EDITORIAL_PUBLISHED,
            'published_at' => now(), 'user_id' => \App\Models\User::query()->value('id'),
            'featured_image' => $this->storeBrandedQaImage('qa-post', 'blog', 1200, 675),
        ]);
        \App\Models\Product::create([
            'title' => 'محصول نمونه قالب جامع', 'slug' => 'universal-qa-product',
            'description' => 'محصول قطعی کنترل کیفیت', 'sku' => 'UNIVERSAL-QA-1',
            'price' => 125000, 'stock' => 10, 'status' => \App\Models\Product::STATUS_PUBLISHED,
            'image' => $this->storeBrandedQaImage('qa-product', 'products', 1000, 1000),
        ]);
        \App\Models\Course::create([
            'title' => 'دوره نمونه قالب جامع', 'slug' => 'universal-qa-course',
            'description' => 'دوره قطعی کنترل کیفیت', 'price' => 0, 'is_active' => true,
            'image' => $this->storeBrandedQaImage('qa-course', 'courses', 800, 600),
        ]);
        \App\Models\Form::create(['title' => 'فرم درخواست قالب جامع', 'slug' => 'universal-qa-form', 'is_active' => true]);

        // Use the canonical commercial demo-data seeder as the browser
        // product source too. The previous one-product/one-post fixture
        // could only ever render a sparse strip, so it was structurally
        // valid but incapable of proving the real commercial sliders.
        app(\Database\Seeders\UniversalMultipurposeQaDemoCommerceContentSeeder::class)->run();

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

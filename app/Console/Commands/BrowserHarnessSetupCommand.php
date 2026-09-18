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
            $canvasThemeData = $this->loadCanonicalUniversalThemeExport();
            // Real, seeder-produced CustomerMedia library rows for the
            // media-round-trip proof (universal-multipurpose-theme.spec.js
            // selects these by known title/uuid). The canonical export's
            // OWN images stay plain saas-platform-hosted URLs by design
            // (a portable master Theme carries no customer-media UUIDs —
            // see ADR-049 §6) — see MASTER_THEME_MEDIA_LOCALIZATION_GAP.
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
     * TASK 3/3 Phase 5 — canonical product convergence. This used to be a
     * ~260-line hand-authored second definition of the Universal
     * Multipurpose Theme (`universalThemeData()`, now removed), independent
     * of saas-platform's real `UniversalMultipurposeQaThemeSeeder` — the
     * exact "duplicate product definition" problem the Phase 5 harness
     * audit was asked to find and close. It is replaced by loading a real,
     * versioned export of the actual canonical product (21 pages, 6 header
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

<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
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
    protected $signature = 'browser-harness:setup {--json : Print a single machine-readable JSON summary line at the end}';

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
        $canvasThemeData = [
            'pages' => [
                'home' => ['title' => 'صفحه اصلی', 'slug' => 'home', 'is_home' => true, 'blocks' => [
                    ['id' => 'canvas_section_1', 'type' => 'section', 'props' => ['padding' => 'md', 'background' => 'transparent'], 'children' => []],
                ]],
            ],
            'header_blocks' => [],
            'footer_blocks' => [],
            'global_styles' => [],
        ];
        Setting::set('active_canvas_theme_data', json_encode($canvasThemeData), 'theme');
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
}

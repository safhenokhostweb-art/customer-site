<?php

namespace App\Console\Commands;

use App\Models\Page;
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

        $this->info('Browser harness environment ready.');

        if ($this->option('json')) {
            $this->line('BROWSER_HARNESS_JSON:' . json_encode([
                'admin_mobile' => $user->mobile,
                'admin_password' => self::PASSWORD,
                'page_id' => $page->id,
                'page_slug' => $page->slug,
            ]));
        }

        return self::SUCCESS;
    }
}

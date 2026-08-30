<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use SaaSPlatform\ThemeRuntime\Browser\PlaywrightExtractionService;

/**
 * Phase 2h (ADR-029) — real browser verification of customer-site's actual
 * TENANT RUNTIME, not the SaaS Builder preview. Uses the same shared
 * Chromium quality-check engine Phase 2g wired into saas-platform's
 * Commercial Quality Gate.
 *
 * customer-site is a single-tenant deployment: `active_canvas_theme_data`
 * is a Setting row, not a multi-tenant DB table. To exercise the real
 * `/` rendering path with a canonical theme's real content, this command
 * temporarily installs the given theme snapshot (a JSON export of a real
 * saas-platform canonical Theme's header_blocks/footer_blocks/pages/
 * global_styles — never fabricated sample data), runs the real browser
 * check against this actual local instance's real homepage, then restores
 * whatever was installed before. No customer database is touched — only
 * this instance's own `settings` table, and only for the duration of one
 * verification run.
 *
 * This result answers "does customer-site's OWN runtime render this theme
 * correctly" — a genuinely different question from saas-platform's
 * Quality Gate (which verifies its own Builder preview only). Label
 * results accordingly; never present one as proof of the other.
 */
class ThemeRuntimeQualityCheckCommand extends Command
{
    protected $signature = 'theme-runtime:verify {snapshot : Path to a JSON file with header_blocks/footer_blocks/pages/global_styles} {--name=} {--url=}';
    protected $description = 'Real browser (Chromium) verification of customer-site\'s actual tenant runtime rendering a given canonical Theme snapshot';

    public function handle(PlaywrightExtractionService $playwright): int
    {
        $path = $this->argument('snapshot');
        if (!is_file($path)) {
            $this->error("Snapshot file not found: {$path}");
            return 1;
        }

        $snapshot = json_decode(file_get_contents($path), true);
        if (!is_array($snapshot)) {
            $this->error('Snapshot file is not valid JSON.');
            return 1;
        }

        if (!$playwright->isAvailable()) {
            $this->warn('Browser infrastructure UNVERIFIED — ' . json_encode($playwright->availabilityReport(), JSON_UNESCAPED_UNICODE));
            return 2;
        }

        $originalData = DB::table('settings')->where('key', 'active_canvas_theme_data')->value('value');
        $originalName = DB::table('settings')->where('key', 'active_canvas_theme_name')->value('value');

        try {
            Setting::set('active_canvas_theme_data', $snapshot);
            Setting::set('active_canvas_theme_name', $this->option('name') ?? ($path));
            Cache::forget('canvas_theme_data');

            $url = $this->option('url') ?? config('app.url');
            $this->info("CUSTOMER RUNTIME VERIFICATION (real browser, real tenant render path): {$url}");

            $overallOk = true;
            foreach (['desktop', 'tablet', 'mobile'] as $viewport) {
                try {
                    $evidence = $playwright->runQualityCheck($url, $viewport, 30000);
                } catch (\Throwable $e) {
                    $this->warn("  {$viewport}: UNVERIFIED (infrastructure failure) — {$e->getMessage()}");
                    continue;
                }

                $navOk = !empty($evidence['navigation']['ok']);
                $overflowPx = (int) ($evidence['overflow']['overflowPx'] ?? 0);
                $vw = (int) ($evidence['viewport_dimensions']['width'] ?? 1);
                $catastrophic = $vw > 0 && ($overflowPx / $vw) > 0.2;
                $pageErrors = (int) ($evidence['page_errors']['total'] ?? 0);
                $localResourceFailures = count(array_filter($evidence['resource_failures']['entries'] ?? [], fn ($r) => ($r['sameOriginAsTarget'] ?? false) === true));
                $contrastFailed = (int) ($evidence['contrast']['failed'] ?? 0);

                $blocking = !$navOk || $catastrophic || $pageErrors > 0 || $localResourceFailures > 0 || $contrastFailed > 0;
                $overallOk = $overallOk && !$blocking;

                $status = $blocking ? '<fg=red>BLOCKING</>' : '<fg=green>OK</>';
                $this->line("  {$viewport}: {$status} nav_ok=" . ($navOk ? 'yes' : 'no')
                    . " overflow_px={$overflowPx} page_errors={$pageErrors}"
                    . " local_resource_failures={$localResourceFailures} contrast_failed={$contrastFailed}");
            }

            $this->info($overallOk ? 'CUSTOMER RUNTIME VERIFIED: no blocking real-browser findings.' : 'CUSTOMER RUNTIME VERIFICATION FOUND BLOCKING ISSUES.');

            return $overallOk ? 0 : 1;
        } finally {
            // Always restore — never leave this instance's real settings mutated.
            if ($originalData !== null) {
                Setting::set('active_canvas_theme_data', json_decode($originalData, true) ?? $originalData);
            } else {
                DB::table('settings')->where('key', 'active_canvas_theme_data')->delete();
            }
            if ($originalName !== null) {
                Setting::set('active_canvas_theme_name', $originalName);
            } else {
                DB::table('settings')->where('key', 'active_canvas_theme_name')->delete();
            }
            Cache::forget('canvas_theme_data');
            Cache::forget('settings');
        }
    }
}

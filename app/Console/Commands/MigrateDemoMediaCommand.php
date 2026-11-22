<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use App\Services\CustomerMediaService;
use App\Services\ThemeCanvas\ThemeInstanceRevisionService;
use App\Services\ThemeCanvas\ThemeMediaLocalizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MigrateDemoMediaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme:migrate-demo-media
                            {--site-id= : Explicit site ID (defaults to PLATFORM_SITE_ID)}
                            {--dry-run : Only preview what would be migrated without saving changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate demo images in active theme to CustomerMedia library and rewrite block props to media_id';

    /**
     * Execute the console command.
     */
    public function handle(
        ThemeMediaLocalizationService $localizer,
        CustomerMediaService $mediaService,
        ThemeInstanceRevisionService $revisionService
    ): int {
        if ($siteId = $this->option('site-id')) {
            config(['site.platform_site_id' => (int) $siteId]);
        }

        $siteId = (int) config('site.platform_site_id', 0);
        if ($siteId <= 0) {
            $this->error('Error: PLATFORM_SITE_ID is required (specify in config or pass --site-id).');
            return 1;
        }

        $rawThemeData = Setting::get('active_canvas_theme_data');
        if (empty($rawThemeData)) {
            $this->warn('No active canvas theme data found in settings.');
            return 0;
        }

        $themeData = json_decode($rawThemeData, true);
        if (!is_array($themeData)) {
            $this->error('Active canvas theme data is not valid JSON.');
            return 1;
        }

        $slug = Setting::get('active_canvas_theme_slug') ?: 'default';
        $isDryRun = (bool) $this->option('dry-run');

        $this->info("Migrating demo media for site {$siteId}, active theme: '{$slug}'" . ($isDryRun ? ' [DRY-RUN]' : '') . '...');

        try {
            $localized = $localizer->localize($themeData, [
                'theme_identifier' => $slug,
            ]);

            if ($isDryRun) {
                $this->info('[DRY-RUN] Localization completed in memory. No changes written.');
                return 0;
            }

            Setting::set('active_canvas_theme_data', json_encode($localized), 'theme');

            $activeRevisionId = Setting::get('active_theme_revision_id');
            if ($activeRevisionId) {
                $revision = ThemeInstanceRevision::find($activeRevisionId);
                if ($revision) {
                    $revision->theme_data = $localized;
                    $revision->save();
                    $mediaService->registerThemeRevisionUsages($revision->id, $localized);
                    $this->info("Updated theme revision #{$revision->id} with localized media references.");
                }
            }

            $this->info("Theme demo media migration completed successfully for '{$slug}'.");
            return 0;
        } catch (\Throwable $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            Log::error('theme:migrate-demo-media failed', ['exception' => $e]);
            return 1;
        }
    }
}

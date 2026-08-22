<?php

namespace App\Console\Commands;

use App\Models\AdminActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class PruneAdminActivityLogs extends Command
{
    protected $signature = 'admin-activity-log:prune {--days= : Retention window in days (defaults to config audit.retention_days)}';

    protected $description = 'Remove admin_activity_log rows older than the retention window';

    public function handle(): int
    {
        if (! Schema::hasTable('admin_activity_log')) {
            $this->warn('Table admin_activity_log does not exist.');

            return self::SUCCESS;
        }

        $daysOption = $this->option('days');
        $days = $daysOption !== null && $daysOption !== ''
            ? max(1, (int) $daysOption)
            : max(1, (int) config('audit.retention_days', 365));

        $cutoff = now()->subDays($days);
        $dry = (bool) config('audit.prune_dry_run', false);

        $count = AdminActivityLog::query()->where('created_at', '<', $cutoff)->count();

        if ($dry) {
            $this->info("DRY RUN (AUDIT_PRUNE_DRY_RUN=true): would delete {$count} row(s) older than {$cutoff->toDateTimeString()}.");

            return self::SUCCESS;
        }

        $deletedTotal = 0;
        do {
            $deleted = AdminActivityLog::query()
                ->where('created_at', '<', $cutoff)
                ->limit(1000)
                ->delete();
            $deletedTotal += $deleted;
        } while ($deleted > 0);

        $this->info("Deleted {$deletedTotal} admin activity log row(s).");

        return self::SUCCESS;
    }
}

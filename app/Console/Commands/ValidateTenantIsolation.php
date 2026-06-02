<?php

namespace App\Console\Commands;

use App\Services\TenantSupportRuntime\TenantIsolationValidator;
use Illuminate\Console\Command;

/**
 * PHASE M3.2 — DOMAIN A validation command.
 *
 * Usage:
 *   php artisan support:validate-tenant-isolation
 *   php artisan support:validate-tenant-isolation --json
 *   php artisan support:validate-tenant-isolation --fail-below=95
 */
class ValidateTenantIsolation extends Command
{
    protected $signature = 'support:validate-tenant-isolation
                            {--json : Output results as JSON}
                            {--fail-below=95 : Exit with code 1 if score is below this threshold}';

    protected $description = 'Validates DOMAIN A — Tenant Support Runtime isolation (score 0–100)';

    public function handle(TenantIsolationValidator $validator): int
    {
        $result    = $validator->validate();
        $score     = (int) round($result['score']);
        $threshold = (int) $this->option('fail-below');

        if ($this->option('json')) {
            $this->line(json_encode([
                'score'   => $score,
                'passed'  => $result['passed'],
                'results' => $result['results'],
            ], JSON_PRETTY_PRINT));
            return $score >= $threshold ? self::SUCCESS : self::FAILURE;
        }

        $this->newLine();
        $this->line('  <fg=cyan;options=bold>DOMAIN A — Tenant Support Runtime Isolation</> ');
        $this->line('  ─────────────────────────────────────────── ');
        $this->newLine();

        foreach ($result['results'] as $key => $check) {
            $icon  = match ($check['status']) {
                'pass' => '<fg=green>✓</>',
                'fail' => '<fg=red>✗</>',
                'warn' => '<fg=yellow>⚠</>',
                default => '?',
            };
            $label = str_pad(str_replace('_', ' ', $key), 30);
            $this->line("  {$icon}  <fg=white>{$label}</> {$check['message']}");
        }

        $this->newLine();

        $color = $score >= 95 ? 'green' : ($score >= 80 ? 'yellow' : 'red');
        $grade = $score >= 95 ? 'A+' : ($score >= 85 ? 'A' : ($score >= 75 ? 'B' : 'C'));

        $this->line("  Score: <fg={$color};options=bold>{$score}/100 (Grade {$grade})</>");
        $this->newLine();

        if ($score < $threshold) {
            $this->error("  ✗ Score {$score} is below threshold {$threshold}. Tenant isolation is at risk.");
            return self::FAILURE;
        }

        $this->info("  ✓ Tenant isolation validated. Score: {$score}/100.");
        return self::SUCCESS;
    }
}

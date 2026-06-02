<?php

namespace App\Console\Commands;

use App\Services\SupportModuleLicenseGate;
use Illuminate\Console\Command;

/**
 * PHASE M3.2 — License Gate validation command.
 *
 * Usage:
 *   php artisan support:validate-license-gate
 *   php artisan support:validate-license-gate --json
 */
class ValidateLicenseGate extends Command
{
    protected $signature = 'support:validate-license-gate
                            {--json : Output results as JSON}';

    protected $description = 'Validates DOMAIN A — SupportModuleLicenseGate configuration';

    public function handle(SupportModuleLicenseGate $gate): int
    {
        $result = $gate->validate();
        $status = $gate->getStatus();
        $score  = $result['score'];

        if ($this->option('json')) {
            $this->line(json_encode([
                'score'   => $score,
                'passed'  => $result['passed'],
                'status'  => $status,
                'results' => $result['results'],
            ], JSON_PRETTY_PRINT));
            return $result['passed'] ? self::SUCCESS : self::FAILURE;
        }

        $this->newLine();
        $this->line('  <fg=cyan;options=bold>DOMAIN A — Support Module License Gate</> ');
        $this->line('  ─────────────────────────────────────── ');
        $this->newLine();

        foreach ($result['results'] as $key => $check) {
            $icon  = $check['status'] === 'pass' ? '<fg=green>✓</>' : '<fg=red>✗</>';
            $label = str_pad(str_replace('_', ' ', $key), 28);
            $this->line("  {$icon}  <fg=white>{$label}</> {$check['message']}");
        }

        $this->newLine();
        $this->line('  Runtime Status:');
        foreach ($status as $key => $val) {
            $display = is_bool($val) ? ($val ? 'YES' : 'NO') : (string) $val;
            $color   = $val === true ? 'green' : ($val === false ? 'red' : 'white');
            $this->line("    <fg=white>" . str_pad($key, 30) . "</> <fg={$color}>{$display}</>");
        }

        $this->newLine();
        $color = $score >= 100 ? 'green' : 'yellow';
        $this->line("  Score: <fg={$color};options=bold>{$score}/100</>");
        $this->newLine();

        if (!$result['passed']) {
            $this->error('  ✗ License gate validation failed.');
            return self::FAILURE;
        }

        $this->info('  ✓ License gate validated.');
        return self::SUCCESS;
    }
}

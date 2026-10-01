<?php

namespace App\Console\Commands;

use App\Services\ThemeCanvas\LayoutSlotBindingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Layout Slot Binding validation — Phase 11 follow-up.
 *
 * Renders the 'Test-Layout-Validation' fixture (header slot = Header Minimal,
 * footer slot = Footer Simple, hero = inline content) and PROVES via JSON
 * output that the slot blocks are rendered as Layout Elements — injected into
 * the <header>/<footer> chrome tags with explicit data-layout-slot markers —
 * while the hero stays in the inline content stream and never leaks into the
 * chrome.
 *
 * Usage:
 *   php artisan canvas:validate-layout-slots
 *   php artisan canvas:validate-layout-slots --file=storage/app/layout-slot-validation/report.json
 */
class ValidateLayoutSlotBindingCommand extends Command
{
    protected $signature = 'canvas:validate-layout-slots
        {--file= : Relative path (from storage/app) where the JSON report is written}';

    protected $description = 'Prove that header/footer slot bindings render as Layout Elements (not inline blocks) and emit the JSON proof.';

    private const FIXTURE = 'tests/Feature/ThemeCanvas/fixtures/layout-slot-validation.json';

    public function handle(LayoutSlotBindingService $slots): int
    {
        $fixturePath = base_path(self::FIXTURE);
        if (!is_file($fixturePath)) {
            $this->error("Fixture not found: {$fixturePath}");

            return self::FAILURE;
        }

        $fixture = json_decode((string) file_get_contents($fixturePath), true);
        if (!is_array($fixture)) {
            $this->error("Fixture is not valid JSON: {$fixturePath}");

            return self::FAILURE;
        }

        $report = $slots->validate($fixture);

        $relative = (string) ($this->option('file') ?: 'layout-slot-validation/report.json');
        Storage::disk('local')->put($relative, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->table(
            ['Slot', 'Chrome Tag', 'Preset', 'Block Type', 'Marker', 'Bound'],
            [
                [
                    'header',
                    $report['slots']['header']['tag'],
                    $report['slots']['header']['catalog_preset'],
                    $report['slots']['header']['block_type'],
                    $report['slots']['header']['marker_present'] ? 'yes' : 'NO',
                    $report['slots']['header']['rendered_into'] ?? '—',
                ],
                [
                    'footer',
                    $report['slots']['footer']['tag'],
                    $report['slots']['footer']['catalog_preset'],
                    $report['slots']['footer']['block_type'],
                    $report['slots']['footer']['marker_present'] ? 'yes' : 'NO',
                    $report['slots']['footer']['rendered_into'] ?? '—',
                ],
                [
                    'inline_content',
                    'main',
                    '—',
                    'hero',
                    '—',
                    $report['inline_content']['contains_hero'] ? 'inline (correct)' : 'MISSING',
                ],
            ]
        );

        if ($report['binding_ok']) {
            $this->info('✔ Slot binding OK: header/footer rendered as Layout Elements; hero stayed inline.');
        } else {
            foreach ($report['failures'] as $failure) {
                $this->error("✘ {$failure}");
            }
        }

        $full = storage_path("app/{$relative}");
        $this->line("JSON report: {$full}");

        return $report['binding_ok'] ? self::SUCCESS : self::FAILURE;
    }
}

<?php

namespace App\Console\Commands;

use App\Builder\ElementRegistry;
use App\Builder\Testing\ElementTestSuite;
use Illuminate\Console\Command;

class BuilderTestElementsCommand extends Command
{
    protected $signature = 'builder:test-elements';

    protected $description = 'Run automated tests for all registered builder elements';

    public function handle(): int
    {
        ElementRegistry::clearCache();
        $suite = new ElementTestSuite();
        $report = $suite->run();
        $results = $suite->getResults();

        $passed = $results['passed'] ?? [];
        $failed = $results['failed'] ?? [];
        $warnings = $results['warnings'] ?? [];

        $this->info('Builder Element Test Report');
        $this->newLine();
        $this->info('Passed: ' . count($passed));
        foreach ($passed as $type) {
            $this->line('  - ' . $type);
        }
        $this->newLine();

        if (!empty($failed)) {
            $this->error('Failed: ' . count($failed));
            foreach ($failed as $type => $reasons) {
                $this->line('  - ' . $type . ': ' . implode(', ', (array) $reasons));
            }
            $this->newLine();
        }

        if (!empty($warnings)) {
            $this->warn('Warnings: ' . count($warnings));
            foreach ($warnings as $msg) {
                $this->line('  - ' . $msg);
            }
        }

        return empty($failed) ? self::SUCCESS : self::FAILURE;
    }
}

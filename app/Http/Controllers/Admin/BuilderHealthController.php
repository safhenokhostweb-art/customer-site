<?php

namespace App\Http\Controllers\Admin;

use App\Builder\ElementRegistry;
use App\Builder\Testing\ElementTestSuite;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

/**
 * Phase 7 — Element health dashboard.
 * Shows total elements, passed/failed, last tested time.
 */
class BuilderHealthController extends Controller
{
    public function index(Request $request)
    {
        ElementRegistry::clearCache();
        $suite = new ElementTestSuite();
        $report = $suite->run();
        $results = $suite->getResults();

        $passed = $results['passed'] ?? [];
        $failed = $results['failed'] ?? [];
        $total = count($passed) + count($failed);
        $passedCount = count($passed);
        $failedCount = count($failed);

        Cache::put('builder_health_last_run', [
            'passed' => $passedCount,
            'failed' => $failedCount,
            'total' => $total,
            'failed_items' => $failed,
            'at' => now()->toIso8601String(),
        ], now()->addHours(1));

        return view('admin.builder-health.index', [
            'passed' => $passed,
            'failed' => $failed,
            'total' => $total,
            'passedCount' => $passedCount,
            'failedCount' => $failedCount,
            'allElements' => ElementRegistry::all(),
        ]);
    }
}

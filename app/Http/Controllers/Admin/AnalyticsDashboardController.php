<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\View\View;

class AnalyticsDashboardController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    public function index(): View
    {
        $kpis         = $this->analytics->kpis();
        $revenueChart = $this->analytics->revenueChart(30);
        $usersChart   = $this->analytics->userRegistrationsChart(30);
        $pvChart      = $this->analytics->pageViewsChart(14);
        $topPages     = $this->analytics->topPages(10);
        $deviceSplit  = $this->analytics->deviceSplit();
        $topProducts  = $this->analytics->topProducts(5);
        $tickets      = $this->analytics->ticketSummary();

        return view('admin.analytics.dashboard', compact(
            'kpis', 'revenueChart', 'usersChart', 'pvChart',
            'topPages', 'deviceSplit', 'topProducts', 'tickets'
        ));
    }
}

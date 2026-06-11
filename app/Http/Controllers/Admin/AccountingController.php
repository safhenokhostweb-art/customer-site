<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\License;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    public function index()
    {
        $license = License::current();
        $enabled = SiteHelper::moduleEnabled('accounting');

        $ordersCount      = Order::count();
        $paidOrdersCount  = Order::whereIn('status', ['paid', 'completed', 'shipped', 'processing'])->count();
        $totalRevenue     = Order::whereIn('status', ['paid', 'completed', 'shipped', 'processing'])->sum('total');
        $cancelledCount   = Order::where('status', 'cancelled')->count();
        $pendingCount     = Order::where('status', 'pending')->count();

        $invoicesCount      = Invoice::count();
        $paidInvoicesCount  = Invoice::where('status', 'paid')->count();
        $invoiceTotal       = Invoice::where('status', 'paid')->sum('total');

        // Monthly revenue — last 6 months
        $monthlyRevenue = Order::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as count')
            )
            ->whereIn('status', ['paid', 'completed', 'shipped', 'processing'])
            ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('admin.accounting.index', compact(
            'license', 'enabled',
            'ordersCount', 'paidOrdersCount', 'totalRevenue',
            'cancelledCount', 'pendingCount',
            'invoicesCount', 'paidInvoicesCount', 'invoiceTotal',
            'monthlyRevenue'
        ));
    }
}

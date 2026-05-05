<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\SiteHelper;
use App\Models\License;
use App\Models\Order;
use App\Models\Invoice;

/**
 * صفحهٔ اختصاصی ماژول حسابداری در پنل ادمین سایت مشتری.
 * فقط زمانی در دسترس است که ماژول accounting برای این سایت از طرف پلتفرم فعال شده باشد.
 */
class AccountingController extends Controller
{
    public function index()
    {
        $license = License::current();
        $enabled = SiteHelper::moduleEnabled('accounting');

        // آمار ساده برای داشبورد حسابداری (بدون تغییر در داده)
        $ordersCount = Order::count();
        $paidOrdersCount = Order::where('status', 'paid')->count();
        // در مدل Order فیلد total داریم (نه total_price)
        $totalRevenue = Order::where('status', 'paid')->sum('total');

        $invoicesCount = Invoice::count();
        $paidInvoicesCount = Invoice::where('status', 'paid')->count();
        // در مدل Invoice هم فیلد total داریم (نه amount)
        $invoiceTotal = Invoice::where('status', 'paid')->sum('total');

        return view('admin.accounting.index', compact(
            'license',
            'enabled',
            'ordersCount',
            'paidOrdersCount',
            'totalRevenue',
            'invoicesCount',
            'paidInvoicesCount',
            'invoiceTotal'
        ));
    }
}


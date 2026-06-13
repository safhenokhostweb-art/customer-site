<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\InstallmentOrder;
use Illuminate\Support\Facades\Auth;

class InstallmentAccountController extends Controller
{
    public function index()
    {
        $orders = InstallmentOrder::where('user_id', Auth::id())
            ->with(['plan', 'payments'])
            ->latest()
            ->paginate(10);

        return view('account.installments.index', compact('orders'));
    }

    public function show(int $order)
    {
        $installmentOrder = InstallmentOrder::where('id', $order)
            ->where('user_id', Auth::id())
            ->with(['plan', 'payments'])
            ->firstOrFail();

        return view('account.installments.show', ['order' => $installmentOrder]);
    }
}

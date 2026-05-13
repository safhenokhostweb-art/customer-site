<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Helpers\SiteHelper;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        if (!SiteHelper::shopEnabled()) {
            abort(404);
        }

        $user = auth()->user();
        // Wave 6.1 / P1-S5 — only return orders bound to this user_id. The
        // legacy "match by customer_email" branch is intentionally removed:
        // it allowed access to any guest order whose email a malicious user
        // could set on their own profile. Guest orders remain reachable only
        // via the order number flow (already gated elsewhere).
        $orders = Order::with('items')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('account.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        if (!SiteHelper::shopEnabled()) {
            abort(404);
        }

        $user = auth()->user();
        // Wave 6.1 / P1-S5 — strict ownership only. Email-match removed.
        if ($order->user_id !== $user->id) {
            abort(404);
        }

        $order->load('items');

        return view('account.orders.show', compact('order'));
    }
}

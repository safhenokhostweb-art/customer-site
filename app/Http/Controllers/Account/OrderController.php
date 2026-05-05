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
        $orders = Order::with('items')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('customer_email', $user->email);
            })
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
        if ($order->user_id !== $user->id && $order->customer_email !== $user->email) {
            abort(404);
        }

        $order->load('items');

        return view('account.orders.show', compact('order'));
    }
}

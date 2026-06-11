<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function __construct(protected OrderStatusService $statusService) {}

    public function index(Request $request)
    {
        $query = Order::with(['user', 'items'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, fn ($q) => $q->where(function ($sub) use ($request) {
                $sub->where('number', 'like', '%' . $request->q . '%')
                    ->orWhere('customer_name', 'like', '%' . $request->q . '%')
                    ->orWhere('customer_mobile', 'like', '%' . $request->q . '%');
            }))
            ->when($request->date_from, fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to,   fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest();

        $orders = $query->paginate(20)->withQueryString();

        $stats = [
            'total'     => Order::count(),
            'pending'   => Order::where('status', Order::STATUS_PENDING)->count(),
            'paid'      => Order::where('status', Order::STATUS_PAID)->count(),
            'processing'=> Order::where('status', Order::STATUS_PROCESSING)->count(),
            'shipped'   => Order::where('status', Order::STATUS_SHIPPED)->count(),
            'completed' => Order::where('status', Order::STATUS_COMPLETED)->count(),
            'cancelled' => Order::where('status', Order::STATUS_CANCELLED)->count(),
            'revenue'   => Order::whereIn('status', [Order::STATUS_PAID, Order::STATUS_PROCESSING, Order::STATUS_SHIPPED, Order::STATUS_COMPLETED])->sum('total'),
        ];

        return view('admin.orders.index', compact('orders', 'stats'));
    }

    public function show(Order $order)
    {
        $order->load(['items.product', 'items.variant', 'coupon', 'statusHistory.changedBy', 'user']);
        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => 'required|in:' . implode(',', Order::STATUSES),
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $this->statusService->transition(
                $order,
                $data['status'],
                changedByUserId: $request->user()?->id,
                reason: $data['reason'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'وضعیت سفارش به‌روزرسانی شد.');
    }
}

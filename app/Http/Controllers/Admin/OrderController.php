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

    public function index()
    {
        $orders = Order::latest()->paginate(20);
        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['items', 'coupon', 'statusHistory.changedBy']);
        return view('admin.orders.show', compact('order'));
    }

    /**
     * Wave 6.1 — Status transitions go through OrderStatusService, which
     * enforces the FSM, records history, writes the admin audit log, and
     * applies cancellation side effects (restock + coupon decrement)
     * idempotently. Backward-compatible UI: still a single status select +
     * optional reason field.
     */
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

        return back()->with('success', 'وضعیت به‌روزرسانی شد.');
    }
}

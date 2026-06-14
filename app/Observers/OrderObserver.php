<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\AdminNotificationService;

class OrderObserver
{
    public function created(Order $order): void
    {
        $amount  = number_format((int) ($order->total ?? 0));
        $name    = $order->customer_name ?? 'مشهری';
        $number  = $order->number ?? '#' . $order->id;

        AdminNotificationService::send(
            type:  'order',
            title: "سفارش جدید {$number}",
            body:  "{$name} — {$amount} تومان",
            link:  route('admin.orders.show', $order->id),
        );
    }
}

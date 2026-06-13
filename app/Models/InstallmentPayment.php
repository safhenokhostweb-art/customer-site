<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallmentPayment extends Model
{
    protected $fillable = [
        'installment_order_id', 'installment_number',
        'due_date', 'amount', 'status',
        'paid_at', 'gateway', 'gateway_ref',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at'  => 'datetime',
    ];

    public const STATUS_PENDING   = 'pending';
    public const STATUS_PAID      = 'paid';
    public const STATUS_OVERDUE   = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';

    public function order()
    {
        return $this->belongsTo(InstallmentOrder::class, 'installment_order_id');
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->due_date->isPast();
    }
}

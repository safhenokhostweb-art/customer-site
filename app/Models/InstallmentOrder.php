<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallmentOrder extends Model
{
    protected $fillable = [
        'order_id', 'user_id', 'plan_id',
        'total_amount', 'down_payment', 'remaining_amount', 'monthly_installment',
        'installments_total', 'installments_paid',
        'status', 'gateway_ref', 'start_date', 'next_due_date', 'notes',
    ];

    protected $casts = [
        'start_date'    => 'date',
        'next_due_date' => 'date',
    ];

    public const STATUS_PENDING   = 'pending';
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DEFAULTED = 'defaulted';
    public const STATUS_CANCELLED = 'cancelled';

    public function plan()
    {
        return $this->belongsTo(InstallmentPlan::class, 'plan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(InstallmentPayment::class, 'installment_order_id')
            ->orderBy('installment_number');
    }

    public function pendingPayments()
    {
        return $this->payments()->where('status', InstallmentPayment::STATUS_PENDING);
    }

    public function nextPayment()
    {
        return $this->payments()
            ->where('status', InstallmentPayment::STATUS_PENDING)
            ->orderBy('due_date')
            ->first();
    }

    /**
     * Create individual payment rows after activation.
     */
    public function createPaymentSchedule(): void
    {
        $start = $this->start_date ?? now()->toDateString();
        for ($i = 1; $i <= $this->installments_total; $i++) {
            InstallmentPayment::create([
                'installment_order_id' => $this->id,
                'installment_number'   => $i,
                'due_date'             => \Carbon\Carbon::parse($start)->addMonths($i)->toDateString(),
                'amount'               => $this->monthly_installment,
                'status'               => InstallmentPayment::STATUS_PENDING,
            ]);
        }
        $this->update(['next_due_date' => \Carbon\Carbon::parse($start)->addMonth()->toDateString()]);
    }

    public function markPaymentPaid(InstallmentPayment $payment, string $gateway, string $ref): void
    {
        $payment->update([
            'status'      => InstallmentPayment::STATUS_PAID,
            'paid_at'     => now(),
            'gateway'     => $gateway,
            'gateway_ref' => $ref,
        ]);

        $this->increment('installments_paid');

        if ($this->installments_paid >= $this->installments_total) {
            $this->update(['status' => self::STATUS_COMPLETED, 'next_due_date' => null]);
        } else {
            $next = $this->nextPayment();
            $this->update(['next_due_date' => $next?->due_date]);
        }
    }
}

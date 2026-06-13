<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallmentPlan extends Model
{
    protected $fillable = [
        'name', 'period_months', 'down_payment_percent',
        'monthly_interest_rate', 'min_amount', 'max_amount', 'is_active',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'monthly_interest_rate'=> 'decimal:2',
        'min_amount'           => 'integer',
        'max_amount'           => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('period_months');
    }

    public function isEligible(int $amount): bool
    {
        if ($this->min_amount > 0 && $amount < $this->min_amount) return false;
        if ($this->max_amount > 0 && $amount > $this->max_amount) return false;
        return true;
    }

    /**
     * Calculate installment breakdown for a given total amount.
     */
    public function calculate(int $totalAmount): array
    {
        $downPayment     = (int) round($totalAmount * $this->down_payment_percent / 100);
        $remaining       = $totalAmount - $downPayment;
        $months          = $this->period_months;
        $monthlyInterest = $this->monthly_interest_rate / 100;

        if ($monthlyInterest > 0) {
            // اقساط با بهره: فرمول پرداخت ثابت ماهانه
            $factor          = pow(1 + $monthlyInterest, $months);
            $monthlyPayment  = (int) ceil($remaining * ($monthlyInterest * $factor) / ($factor - 1));
        } else {
            $monthlyPayment = (int) ceil($remaining / $months);
        }

        $totalPayable = $downPayment + ($monthlyPayment * $months);

        return [
            'down_payment'      => $downPayment,
            'remaining'         => $remaining,
            'monthly_payment'   => $monthlyPayment,
            'months'            => $months,
            'total_payable'     => $totalPayable,
            'total_interest'    => $totalPayable - $totalAmount,
        ];
    }

    public function orders()
    {
        return $this->hasMany(InstallmentOrder::class, 'plan_id');
    }
}

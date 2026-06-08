<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Subscription extends Model
{
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED   = 'expired';
    public const STATUS_TRIAL     = 'trial';
    public const STATUS_PENDING   = 'pending';

    protected $fillable = [
        'user_id', 'subscription_plan_id', 'status',
        'starts_at', 'ends_at', 'invoice_id',
        'trial_ends_at', 'cancelled_at', 'cancellation_reason',
        'renewed_at', 'auto_renew', 'notes', 'pending_reason',
        'reminder_7d_sent_at', 'reminder_1d_sent_at',
        'upgrade_from_subscription_id', 'credit_amount',
    ];

    protected $casts = [
        'starts_at'            => 'datetime',
        'ends_at'              => 'datetime',
        'trial_ends_at'        => 'datetime',
        'cancelled_at'         => 'datetime',
        'renewed_at'           => 'datetime',
        'auto_renew'           => 'boolean',
        'reminder_7d_sent_at'  => 'datetime',
        'reminder_1d_sent_at'  => 'datetime',
        'credit_amount'        => 'decimal:0',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SubscriptionLog::class)->latest();
    }

    public function members(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SubscriptionMember::class);
    }

    public function activeMembers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SubscriptionMember::class)->where('status', SubscriptionMember::STATUS_ACTIVE);
    }

    public function upgradedFrom(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'upgrade_from_subscription_id');
    }

    public function upgradedTo(): HasOne
    {
        return $this->hasOne(Subscription::class, 'upgrade_from_subscription_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->ends_at?->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->ends_at && $this->ends_at->isPast() && $this->status !== self::STATUS_CANCELLED);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isInTrial(): bool
    {
        return $this->status === self::STATUS_TRIAL
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    public function daysRemaining(): int
    {
        if (! $this->ends_at || $this->ends_at->isPast()) {
            return 0;
        }
        return (int) now()->diffInDays($this->ends_at, false);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE    => 'فعال',
            self::STATUS_CANCELLED => 'لغو شده',
            self::STATUS_EXPIRED   => 'منقضی',
            self::STATUS_TRIAL     => 'آزمایشی',
            self::STATUS_PENDING   => 'در انتظار تأیید',
            default                => $this->status ?? 'نامشخص',
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE    => 'green',
            self::STATUS_TRIAL     => 'blue',
            self::STATUS_CANCELLED => 'red',
            self::STATUS_EXPIRED   => 'gray',
            self::STATUS_PENDING   => 'orange',
            default                => 'gray',
        };
    }
}

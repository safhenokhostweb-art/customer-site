<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionMember extends Model
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_REMOVED  = 'removed';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'subscription_id', 'user_id', 'invited_by',
        'status', 'invited_at', 'joined_at', 'removed_at',
    ];

    protected $casts = [
        'invited_at'  => 'datetime',
        'joined_at'   => 'datetime',
        'removed_at'  => 'datetime',
    ];

    // ─── Relations ────────────────────────────────────────────────────────

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING  => 'در انتظار تأیید',
            self::STATUS_ACTIVE   => 'فعال',
            self::STATUS_REMOVED  => 'حذف شده',
            self::STATUS_REJECTED => 'رد شده',
            default               => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING  => 'orange',
            self::STATUS_ACTIVE   => 'green',
            self::STATUS_REMOVED  => 'gray',
            self::STATUS_REJECTED => 'red',
            default               => 'gray',
        };
    }
}

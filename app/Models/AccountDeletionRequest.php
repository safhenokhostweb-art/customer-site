<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountDeletionRequest extends Model
{
    protected $fillable = [
        'user_id',
        'reason',
        'status',
        'requested_at',
        'expires_at',
        'confirmed_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function canBeConfirmed(): bool
    {
        return $this->isPending() && !$this->isExpired();
    }
}

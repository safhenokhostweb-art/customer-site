<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    protected $fillable = [
        'referral_code_id',
        'referred_user_id',
        'status',
        'rewarded_at',
        'points_awarded',
    ];

    protected $casts = [
        'rewarded_at' => 'datetime',
    ];

    const STATUS_PENDING  = 'pending';
    const STATUS_REWARDED = 'rewarded';

    public function referralCode(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class);
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function isRewarded(): bool
    {
        return $this->status === self::STATUS_REWARDED && $this->rewarded_at !== null;
    }
}

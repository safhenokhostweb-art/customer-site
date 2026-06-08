<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MembershipPoint extends Model
{
    // ── نوع تراکنش ─────────────────────────────────────────────────────────
    public const TYPE_EARN         = 'earn';
    public const TYPE_SPEND        = 'spend';
    public const TYPE_EXPIRE       = 'expire';
    public const TYPE_ADMIN_BONUS  = 'admin_bonus';
    public const TYPE_ADMIN_DEDUCT = 'admin_deduct';

    // ── منبع کسب / مصرف ────────────────────────────────────────────────────
    public const SOURCE_PURCHASE       = 'purchase';
    public const SOURCE_SUBSCRIBE      = 'subscribe';
    public const SOURCE_RENEW          = 'renew';
    public const SOURCE_BIRTHDAY       = 'birthday';
    public const SOURCE_REFERRAL       = 'referral';
    public const SOURCE_CHECKOUT_SPEND = 'checkout_spend';
    public const SOURCE_ADMIN          = 'admin';
    public const SOURCE_PLAN_CHANGE    = 'plan_change';

    protected $fillable = [
        'user_id', 'amount', 'balance_after', 'type', 'source',
        'reference_id', 'reference_type', 'description', 'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'amount'     => 'integer',
        'balance_after' => 'integer',
    ];

    // ─── Relations ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // ─── Helpers ───────────────────────────────────────────────────────────

    public function isEarning(): bool
    {
        return $this->amount > 0;
    }

    public function isSpending(): bool
    {
        return $this->amount < 0;
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_EARN         => 'کسب امتیاز',
            self::TYPE_SPEND        => 'مصرف امتیاز',
            self::TYPE_EXPIRE       => 'انقضای امتیاز',
            self::TYPE_ADMIN_BONUS  => 'هدیه مدیر',
            self::TYPE_ADMIN_DEDUCT => 'کسر مدیر',
            default                 => $this->type,
        };
    }

    public function getSourceLabel(): string
    {
        return match ($this->source) {
            self::SOURCE_PURCHASE       => 'خرید محصول',
            self::SOURCE_SUBSCRIBE      => 'فعال‌سازی اشتراک',
            self::SOURCE_RENEW          => 'تمدید اشتراک',
            self::SOURCE_BIRTHDAY       => 'هدیه تولد',
            self::SOURCE_REFERRAL       => 'دعوت دوست',
            self::SOURCE_CHECKOUT_SPEND => 'استفاده در خرید',
            self::SOURCE_ADMIN          => 'تنظیم دستی',
            self::SOURCE_PLAN_CHANGE    => 'تغییر پلن اشتراک',
            default                     => $this->source,
        };
    }
}

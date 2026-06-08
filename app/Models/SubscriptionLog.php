<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionLog extends Model
{
    public const ACTION_SUBSCRIBED    = 'subscribed';
    public const ACTION_RENEWED       = 'renewed';
    public const ACTION_CANCELLED     = 'cancelled';
    public const ACTION_EXPIRED       = 'expired';
    public const ACTION_TRIAL         = 'trial_started';
    public const ACTION_REACTIVATED   = 'reactivated';
    public const ACTION_PLAN_CHANGED  = 'plan_changed';
    public const ACTION_REMINDER_7D    = 'reminder_7d_sent';
    public const ACTION_REMINDER_1D    = 'reminder_1d_sent';
    public const ACTION_RENEWAL_COUPON = 'renewal_coupon_generated';
    public const ACTION_AUTO_RENEWED        = 'auto_renewed';
    public const ACTION_AUTO_RENEW_INVOICE  = 'auto_renew_invoice_created';
    public const ACTION_UPGRADED            = 'upgraded';
    public const ACTION_DOWNGRADED          = 'downgraded';

    protected $fillable = [
        'subscription_id', 'user_id', 'action', 'details', 'performed_by',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActionLabel(): string
    {
        return match ($this->action) {
            self::ACTION_SUBSCRIBED   => 'عضویت',
            self::ACTION_RENEWED      => 'تمدید',
            self::ACTION_CANCELLED    => 'لغو',
            self::ACTION_EXPIRED      => 'انقضاء',
            self::ACTION_TRIAL        => 'شروع آزمایشی',
            self::ACTION_REACTIVATED  => 'فعال‌سازی مجدد',
            self::ACTION_PLAN_CHANGED => 'تغییر پلن',
            self::ACTION_UPGRADED     => 'ارتقاء پلن',
            self::ACTION_DOWNGRADED   => 'تنزل پلن',
            default                   => $this->action,
        };
    }
}

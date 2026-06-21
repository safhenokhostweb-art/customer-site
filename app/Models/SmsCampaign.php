<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsCampaign extends Model
{
    public const STATUS_DRAFT      = 'draft';
    public const STATUS_SCHEDULED  = 'scheduled';
    public const STATUS_RUNNING    = 'running';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_FAILED     = 'failed';
    public const STATUS_CANCELLED  = 'cancelled';

    public const AUDIENCE_ALL     = 'all';
    public const AUDIENCE_CRM     = 'crm';
    public const AUDIENCE_USERS   = 'users';
    public const AUDIENCE_MANUAL  = 'manual';

    protected $fillable = [
        'site_id', 'name', 'message', 'audience_type', 'audience_filter',
        'status', 'scheduled_at', 'started_at', 'completed_at',
        'total_recipients', 'sent_count', 'failed_count', 'created_by_id',
    ];

    protected $casts = [
        'audience_filter' => 'array',
        'scheduled_at'    => 'datetime',
        'started_at'      => 'datetime',
        'completed_at'    => 'datetime',
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(SmsCampaignRecipient::class, 'campaign_id');
    }

    public function pendingRecipients(): HasMany
    {
        return $this->hasMany(SmsCampaignRecipient::class, 'campaign_id')
                    ->where('status', 'pending');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED]);
    }

    public function deliveryRate(): int
    {
        if ($this->total_recipients === 0) return 0;
        return (int) round(($this->sent_count / $this->total_recipients) * 100);
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT      => 'پیش‌نویس',
            self::STATUS_SCHEDULED  => 'زمان‌بندی شده',
            self::STATUS_RUNNING    => 'در حال ارسال',
            self::STATUS_COMPLETED  => 'ارسال شد',
            self::STATUS_FAILED     => 'ناموفق',
            self::STATUS_CANCELLED  => 'لغو شده',
            default                 => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT      => 'slate',
            self::STATUS_SCHEDULED  => 'blue',
            self::STATUS_RUNNING    => 'amber',
            self::STATUS_COMPLETED  => 'emerald',
            self::STATUS_FAILED     => 'red',
            self::STATUS_CANCELLED  => 'gray',
            default                 => 'slate',
        };
    }

    public function audienceLabel(): string
    {
        return match($this->audience_type) {
            self::AUDIENCE_ALL    => 'همه مشتریان',
            self::AUDIENCE_CRM    => 'مخاطبین CRM',
            self::AUDIENCE_USERS  => 'کاربران ثبت‌نام‌شده',
            self::AUDIENCE_MANUAL => 'شماره‌های دستی',
            default               => $this->audience_type,
        };
    }

    public function smsCount(): int
    {
        return (int) ceil(mb_strlen($this->message) / 70);
    }
}

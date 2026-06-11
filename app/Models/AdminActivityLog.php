<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 5 / Wave 3 / P1-2.
 *
 * مدل لاگ فعالیت پنل ادمین سایت مشتری.
 *
 * تفاوت با UserActivityLog: این مدل **فقط برای عملیات tenant admin** است
 * (مثل ویرایش تنظیمات ماژول، تغییر تنظیمات پلتفرم سایت، …) و scope آن
 * در UI فقط برای نقش admin قابل دیدن است. UserActivityLog برای فعالیت
 * end-user (login/profile/address) باقی می‌ماند.
 */
class AdminActivityLog extends Model
{
    protected $table = 'admin_activity_log';

    protected $fillable = [
        'admin_user_id',
        'causer_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    /** برچسب فارسی عملیات برای نمایش در UI. */
    public static function actionLabel(string $action): string
    {
        $labels = [
            'module.settings_updated' => 'ویرایش تنظیمات ماژول',
            'module.settings_reset' => 'بازنشانی تنظیمات ماژول',
            'license.activated' => 'فعال‌سازی لایسنس',
            'license.refreshed' => 'بروزرسانی لایسنس',
            'license.refreshed_config' => 'بروزرسانی تنظیمات از پلتفرم',
            'license.refreshed_modules' => 'بروزرسانی فهرست ماژول‌ها',
            'setting.updated' => 'ویرایش تنظیمات',
            'order.status_changed' => 'تغییر وضعیت سفارش',
            'blog.post_published'           => 'انتشار مطلب وبلاگ',
            'blog.post_scheduled'           => 'زمان‌بندی انتشار مطلب وبلاگ',
            'platform_agent.login'          => 'ورود ادمین پلتفرم',
            'platform_agent.provisioned'    => 'ثبت ادمین پلتفرم',
            'platform_agent.reprovisioned'  => 'بازنشانی رمز ادمین پلتفرم',
            'platform_agent.revoked'        => 'لغو دسترسی ادمین پلتفرم',
            'platform_agent.access_recorded'=> 'ثبت دسترسی ادمین پلتفرم',
        ];

        return $labels[$action] ?? $action;
    }
}

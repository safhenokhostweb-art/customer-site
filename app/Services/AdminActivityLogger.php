<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 / Wave 3 / P1-2.
 *
 * سرویس ثبت لاگ فعالیت برای پنل ادمین سایت مشتری.
 *
 * طراحی بر اساس الگوی App\Services\ActivityLogger در saas-platform:
 *  - Schema::hasTable guard برای محیط‌هایی که هنوز migration اجرا نشده.
 *  - capture خودکار user/ip/userAgent از request جاری.
 *  - properties به‌صورت array قابل cast.
 *
 * در صورت نبود admin authenticated یا نبود جدول، خاموش fail می‌کند تا
 * کنترلرها نیاز به guard اضافه نداشته باشند.
 */
class AdminActivityLogger
{
    public static function log(
        string $action,
        ?string $description = null,
        ?Model $subject = null,
        array $properties = []
    ): ?AdminActivityLog {
        if (!Schema::hasTable('admin_activity_log')) {
            return null;
        }

        $description = $description ?? AdminActivityLog::actionLabel($action);
        $request = request();

        return AdminActivityLog::create([
            'admin_user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}

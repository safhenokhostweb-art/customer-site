<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Jalali;
use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 / Wave 3 / P1-2.
 *
 * نمایش لاگ فعالیت پنل ادمین سایت مشتری با فیلترهای admin/action/date.
 * تاریخ‌ها فقط شمسی پذیرفته می‌شوند (قانون persian-date-only).
 */
class AdminActivityLogController extends Controller
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('admin_activity_log')) {
            return view('admin.activity-log.index', [
                'logs' => new LengthAwarePaginator([], 0, 25),
                'admins' => collect(),
                'actionOptions' => $this->actionOptions(),
            ])->with('error', 'جدول admin_activity_log وجود ندارد. php artisan migrate را اجرا کنید.');
        }

        $query = AdminActivityLog::with('adminUser')->orderByDesc('created_at');

        if ($request->filled('admin_user_id')) {
            $query->where('admin_user_id', (int) $request->input('admin_user_id'));
        }
        if ($request->filled('action')) {
            $query->where('action', (string) $request->input('action'));
        }
        if ($request->filled('from')) {
            $from = Jalali::toCarbon((string) $request->input('from'));
            if ($from) {
                $query->whereDate('created_at', '>=', $from->format('Y-m-d'));
            }
        }
        if ($request->filled('to')) {
            $to = Jalali::toCarbon((string) $request->input('to'));
            if ($to) {
                $query->whereDate('created_at', '<=', $to->format('Y-m-d'));
            }
        }

        $logs = $query->paginate(25)->withQueryString();

        $admins = User::where('is_admin', true)
            ->orderBy('id')
            ->get(['id', 'mobile', 'email', 'name']);

        return view('admin.activity-log.index', [
            'logs' => $logs,
            'admins' => $admins,
            'actionOptions' => $this->actionOptions(),
        ]);
    }

    private function actionOptions(): array
    {
        return [
            'module.settings_updated' => 'ویرایش تنظیمات ماژول',
            'module.settings_reset' => 'بازنشانی تنظیمات ماژول',
            'license.activated' => 'فعال‌سازی لایسنس',
            'license.refreshed' => 'بروزرسانی لایسنس',
            'license.refreshed_config' => 'بروزرسانی تنظیمات از پلتفرم',
            'license.refreshed_modules' => 'بروزرسانی فهرست ماژول‌ها',
            'setting.updated' => 'ویرایش تنظیمات',
            'order.status_changed' => 'تغییر وضعیت سفارش',
            'blog.post_published' => 'انتشار مطلب وبلاگ',
            'blog.post_scheduled' => 'زمان‌بندی انتشار مطلب وبلاگ',
        ];
    }
}

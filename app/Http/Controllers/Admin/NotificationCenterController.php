<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Jalali;
use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\NotificationBatch;
use App\Models\SiteNotification;
use App\Models\UserNotification;
use App\Models\User;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class NotificationCenterController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'alerts');

        // --- System Alerts (Tab 1) ---
        $alertsQuery = AdminNotification::latest();
        if ($request->filled('alert_type')) {
            $alertsQuery->where('type', $request->alert_type);
        }
        if ($request->filled('alert_status')) {
            $alertsQuery->where('is_read', $request->alert_status === 'read');
        }
        $alerts = $alertsQuery->paginate(20, ['*'], 'alert_page')->withQueryString()->fragment('tab-alerts');
        $unreadAlertsCount = AdminNotificationService::unreadCount();

        // --- Site Banners (Tab 2) ---
        $banners = SiteNotification::orderByDesc('order')->orderByDesc('id')->get();
        $activeBannersCount = SiteNotification::active()->count();

        // --- User Messages (Tab 3) ---
        $batches = NotificationBatch::with('createdByUser')
            ->latest('sent_at')
            ->paginate(10, ['*'], 'batch_page')
            ->withQueryString()
            ->fragment('tab-send');
        $sentThisMonth = NotificationBatch::where('sent_at', '>=', now()->startOfMonth())->count();

        // Users with unread notifications
        $usersWithUnread = Schema::hasTable('user_notifications')
            ? UserNotification::whereNull('read_at')->distinct('user_id')->count('user_id')
            : 0;

        return view('admin.notification-center.index', compact(
            'activeTab',
            'alerts', 'unreadAlertsCount',
            'banners', 'activeBannersCount',
            'batches', 'sentThisMonth', 'usersWithUnread'
        ));
    }

    public function markAlertRead(AdminNotification $alert): JsonResponse
    {
        $alert->markRead();
        return response()->json(['success' => true]);
    }

    public function markAllAlertsRead(): JsonResponse
    {
        AdminNotification::unread()->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['success' => true, 'message' => 'همه اعلان‌ها خوانده شدند.']);
    }

    public function toggleBanner(SiteNotification $banner)
    {
        $banner->is_active = ! $banner->is_active;
        $banner->save();
        return back()->with('success', 'وضعیت اعلان به‌روزرسانی شد.');
    }

    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'count' => AdminNotificationService::unreadCount(),
            'items' => AdminNotification::unread()
                ->latest()->limit(8)
                ->get(['id', 'type', 'title', 'body', 'link', 'created_at'])
                ->map(fn($n) => [
                    'id'    => $n->id,
                    'type'  => $n->type,
                    'title' => $n->title,
                    'body'  => $n->body,
                    'link'  => $n->link,
                    'ago'   => Jalali::diffForHumansFa($n->created_at),
                ]),
        ]);
    }
}

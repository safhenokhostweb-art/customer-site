<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class AdminNotificationController extends Controller
{
    public function index()
    {
        $notifications = AdminNotification::latest()->paginate(30);
        $unreadCount   = AdminNotificationService::unreadCount();

        return view('admin.notifications.index', compact('notifications', 'unreadCount'));
    }

    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'count' => AdminNotificationService::unreadCount(),
            'items' => AdminNotification::unread()
                ->latest()
                ->limit(8)
                ->get(['id', 'type', 'title', 'body', 'link', 'created_at'])
                ->map(fn($n) => [
                    'id'    => $n->id,
                    'type'  => $n->type,
                    'title' => $n->title,
                    'body'  => $n->body,
                    'link'  => $n->link,
                    'ago'   => $n->created_at->diffForHumans(),
                ]),
        ]);
    }

    public function markRead(AdminNotification $notification): JsonResponse
    {
        $notification->markRead();
        return response()->json(['success' => true]);
    }

    public function markAllRead(): JsonResponse
    {
        AdminNotification::unread()->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['success' => true]);
    }
}

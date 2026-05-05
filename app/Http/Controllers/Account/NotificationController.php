<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $notifications = UserNotification::where('user_id', $user->id)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('account.notifications.index', compact('notifications'));
    }

    public function markAsRead(UserNotification $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            abort(404);
        }

        $notification->markAsRead();

        return back()->with('success', 'اعلان به‌عنوان خوانده‌شده علامت‌گذاری شد.');
    }

    public function markAllAsRead()
    {
        UserNotification::where('user_id', auth()->id())->unread()->update(['read_at' => now()]);

        return back()->with('success', 'همهٔ اعلان‌ها خوانده شدند.');
    }
}

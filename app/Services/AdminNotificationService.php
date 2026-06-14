<?php

namespace App\Services;

use App\Models\AdminNotification;
use Illuminate\Support\Facades\Schema;

class AdminNotificationService
{
    public static function send(string $type, string $title, string $body = '', string $link = ''): void
    {
        if (! Schema::hasTable('admin_notifications')) {
            return;
        }

        AdminNotification::create([
            'type'  => $type,
            'title' => $title,
            'body'  => $body ?: null,
            'link'  => $link ?: null,
        ]);

        // Auto-prune: keep only last 500 notifications
        $count = AdminNotification::count();
        if ($count > 500) {
            AdminNotification::orderBy('id')
                ->limit($count - 500)
                ->delete();
        }
    }

    public static function unreadCount(): int
    {
        if (! Schema::hasTable('admin_notifications')) {
            return 0;
        }
        return AdminNotification::unread()->count();
    }
}

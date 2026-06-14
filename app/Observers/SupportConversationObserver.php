<?php

namespace App\Observers;

use App\Models\SupportConversation;
use App\Services\AdminNotificationService;

class SupportConversationObserver
{
    public function created(SupportConversation $conversation): void
    {
        $subject  = $conversation->subject ?? 'تیکت جدید';
        $priority = match ($conversation->priority ?? 'normal') {
            'high', 'urgent' => '🔴 ',
            default          => '',
        };

        AdminNotificationService::send(
            type:  'ticket',
            title: "{$priority}تیکت جدید: {$subject}",
            body:  'کانال: ' . ($conversation->channel ?? 'web'),
        );
    }
}

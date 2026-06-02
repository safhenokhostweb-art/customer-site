<?php

namespace App\Services\TenantSupportRuntime;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\SupportReadReceipt;
use Illuminate\Support\Str;

class TenantMessageService
{
    public function __construct(
        private TenantChannelRegistry $channels
    ) {}

    public function send(
        string $conversationId,
        string $senderId,
        string $senderType,
        string $content,
        string $type = SupportMessage::TYPE_TEXT,
        ?string $replyToId = null,
        bool   $isNote = false,
        array  $metadata = []
    ): SupportMessage {
        $message = SupportMessage::create([
            'id'                  => Str::uuid()->toString(),
            'conversation_id'     => $conversationId,
            'sender_id'           => $senderId,
            'sender_type'         => $senderType,
            'message_type'        => $type,
            'content'             => $content,
            'reply_to_message_id' => $replyToId,
            'is_internal_note'    => $isNote,
            'metadata'            => $metadata,
        ]);

        SupportConversation::where('id', $conversationId)
            ->update(['last_message_at' => now()]);

        // Mark as first response if this is an agent/admin replying to an open user conversation
        if (in_array($senderType, ['admin', 'support_agent'])) {
            SupportConversation::where('id', $conversationId)
                ->whereNull('first_response_at')
                ->update(['first_response_at' => now()]);
        }

        return $message;
    }

    public function markRead(string $conversationId, string $readerId, string $readerType): void
    {
        $unread = SupportMessage::where('conversation_id', $conversationId)
            ->visible()
            ->whereNotExists(function ($q) use ($readerId, $readerType) {
                $q->from('support_read_receipts')
                  ->whereColumn('support_read_receipts.message_id', 'support_messages.id')
                  ->where('reader_id', $readerId)
                  ->where('reader_type', $readerType);
            })
            ->get(['id']);

        foreach ($unread as $msg) {
            SupportReadReceipt::firstOrCreate([
                'message_id'      => $msg->id,
                'conversation_id' => $conversationId,
                'reader_id'       => $readerId,
                'reader_type'     => $readerType,
            ], [
                'read_at' => now(),
            ]);
        }

        // Reset unread count for this participant
        \App\Models\SupportConversationParticipant::where('conversation_id', $conversationId)
            ->where('participant_id', $readerId)
            ->where('participant_type', $readerType)
            ->update(['last_read_at' => now(), 'unread_count' => 0]);
    }

    public function paginate(string $conversationId, int $limit = 50, ?string $before = null): array
    {
        $query = SupportMessage::where('conversation_id', $conversationId)
            ->visible()
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($before) {
            $query->where('created_at', '<', $before);
        }

        $messages    = $query->get();
        $nextCursor  = $messages->count() === $limit ? $messages->last()?->created_at?->toIso8601String() : null;

        return [
            'data'        => $messages->reverse()->values(),
            'next_cursor' => $nextCursor,
            'has_more'    => $nextCursor !== null,
        ];
    }

    public function softDelete(string $messageId, string $requesterId, string $requesterType): bool
    {
        $message = SupportMessage::find($messageId);
        if (!$message) return false;

        // Only sender can delete their own message
        if ($message->sender_id !== $requesterId || $message->sender_type !== $requesterType) {
            return false;
        }

        $message->update(['is_deleted' => true]);
        return true;
    }
}

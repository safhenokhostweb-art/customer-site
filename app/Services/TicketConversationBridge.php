<?php

namespace App\Services;

use App\Models\SupportConversation;
use App\Models\SupportConversationParticipant;
use App\Models\SupportMessage;
use App\Models\SupportMessageAttachment;
use App\Services\TenantSupportRuntime\TenantConversationService;
use App\Services\TenantSupportRuntime\TenantMessageService;
use Illuminate\Support\Facades\Log;
use SaaSPlatform\Modules\Tickets\Models\Ticket;
use SaaSPlatform\Modules\Tickets\Models\TicketReply;

/**
 * Dual-write sync layer: after every Ticket or TicketReply write via the
 * web UI, this bridge mirrors the same data into support_conversations and
 * support_messages so the new conversation schema stays populated while the
 * legacy web UI continues to work without any Blade changes.
 *
 * All methods are wrapped in try/catch — a bridge failure NEVER breaks the
 * primary ticket flow. The web UI always succeeds; sync errors are logged.
 */
class TicketConversationBridge
{
    public function __construct(
        private TenantConversationService $conversations,
        private TenantMessageService      $messages,
    ) {}

    /**
     * Mirror a newly created Ticket into support_conversations.
     * Also sends the ticket description as the first SupportMessage.
     *
     * @param  string       $creatorId    ID of the user or admin who created the ticket
     * @param  string       $creatorType  'user' | 'admin'
     * @param  string|null  $targetUserId When admin creates a ticket for a specific user,
     *                                    pass that user's ID so they become a participant.
     */
    public function syncNewTicket(
        Ticket  $ticket,
        string  $creatorId,
        string  $creatorType,
        ?string $targetUserId = null
    ): void {
        try {
            $conversation = $this->conversations->create(
                subject: $ticket->subject,
                createdById: $creatorId,
                createdByType: $creatorType,
                priority: $this->mapPriority($ticket->priority),
                channel: 'web',
                metadata: [
                    'ticket_id'     => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                ],
            );

            // Initial message is the ticket description
            $this->messages->send(
                conversationId: $conversation->id,
                senderId: $creatorId,
                senderType: $creatorType,
                content: $ticket->description ?? $ticket->subject,
                type: SupportMessage::TYPE_TEXT,
            );

            // When admin creates a ticket targeting a specific user, add that user
            // as a participant so they can see the conversation via the API.
            if ($targetUserId !== null && $targetUserId !== $creatorId) {
                $this->conversations->addParticipant(
                    conversationId: $conversation->id,
                    participantId: $targetUserId,
                    participantType: SupportConversationParticipant::TYPE_USER,
                    role: SupportConversationParticipant::ROLE_PARTICIPANT,
                );
            }
        } catch (\Throwable $e) {
            Log::error('TicketConversationBridge::syncNewTicket failed', [
                'ticket_id' => $ticket->id,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Mirror a newly created TicketReply into support_messages.
     * Also writes any already-saved file attachments into support_message_attachments.
     *
     * If the SupportConversation for this ticket doesn't exist yet (backfill gap),
     * it is created on-the-fly before the message is inserted.
     *
     * @param  string  $senderId    ID of the reply author
     * @param  string  $senderType  'user' | 'admin'
     * @param  array   $attachments Array of attachment metadata already saved by the controller:
     *                              [['name'=>..., 'path'=>..., 'url'=>..., 'size'=>..., 'mime'=>...], ...]
     */
    public function syncReply(
        TicketReply $reply,
        Ticket      $ticket,
        string      $senderId,
        string      $senderType,
        array       $attachments = []
    ): void {
        try {
            $conversation = $this->findConversation($ticket->id);

            // Lazy backfill: if the conversation was never created, make it now.
            if (!$conversation) {
                $this->syncNewTicket($ticket, $senderId, $senderType);
                $conversation = $this->findConversation($ticket->id);
                if (!$conversation) {
                    return;
                }
            }

            // Ensure the reply author is registered as a participant.
            $this->conversations->addParticipant(
                conversationId: $conversation->id,
                participantId: $senderId,
                participantType: $senderType,
                role: SupportConversationParticipant::ROLE_PARTICIPANT,
            );

            $message = $this->messages->send(
                conversationId: $conversation->id,
                senderId: $senderId,
                senderType: $senderType,
                content: $reply->message,
                type: SupportMessage::TYPE_TEXT,
                isNote: (bool) $reply->is_internal,
            );

            // Mirror attachments that were already saved to public/uploads by the controller.
            foreach ($attachments as $att) {
                SupportMessageAttachment::create([
                    'message_id'      => $message->id,
                    'conversation_id' => $conversation->id,
                    'original_name'   => $att['name'] ?? 'attachment',
                    'stored_path'     => $att['path'] ?? '',
                    'disk'            => 'public_uploads',  // sentinel: file lives in public/
                    'mime_type'       => $att['mime'] ?? 'application/octet-stream',
                    'file_size'       => $att['size'] ?? 0,
                    'file_hash'       => null,
                    'uploader_id'     => $senderId,
                    'uploader_type'   => $senderType,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('TicketConversationBridge::syncReply failed', [
                'reply_id'  => $reply->id,
                'ticket_id' => $ticket->id,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function findConversation(int $ticketId): ?SupportConversation
    {
        return SupportConversation::whereRaw(
            "JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.ticket_id')) = ?",
            [$ticketId]
        )->whereNull('deleted_at')->first();
    }

    private function mapPriority(string $legacy): string
    {
        return match ($legacy) {
            'high'   => SupportConversation::PRIORITY_HIGH,
            'low'    => SupportConversation::PRIORITY_LOW,
            'urgent' => SupportConversation::PRIORITY_URGENT,
            default  => SupportConversation::PRIORITY_NORMAL,
        };
    }
}

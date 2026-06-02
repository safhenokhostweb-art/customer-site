<?php

namespace App\Services\TenantSupportRuntime;

use App\Models\SupportConversation;
use App\Models\SupportConversationParticipant;
use App\Models\SupportMessage;
use Illuminate\Support\Str;

class TenantConversationService
{
    public function __construct(
        private TenantChannelRegistry $channels
    ) {}

    public function create(
        string $subject,
        string $createdById,
        string $createdByType,
        string $priority = SupportConversation::PRIORITY_NORMAL,
        string $channel = 'web',
        array  $metadata = []
    ): SupportConversation {
        $conversation = SupportConversation::create([
            'id'              => Str::uuid()->toString(),
            'subject'         => $subject,
            'priority'        => $priority,
            'channel'         => $channel,
            'created_by_user_id' => $createdById,
            'created_by_type' => $createdByType,
            'metadata'        => $metadata,
            'status'          => SupportConversation::STATUS_OPEN,
        ]);

        // Auto-add creator as owner participant
        $this->addParticipant(
            $conversation->id,
            $createdById,
            $createdByType,
            SupportConversationParticipant::ROLE_OWNER,
            null
        );

        return $conversation;
    }

    public function addParticipant(
        string $conversationId,
        string $participantId,
        string $participantType,
        string $role = SupportConversationParticipant::ROLE_PARTICIPANT,
        ?string $displayName = null
    ): void {
        SupportConversationParticipant::firstOrCreate(
            [
                'conversation_id'  => $conversationId,
                'participant_id'   => $participantId,
                'participant_type' => $participantType,
            ],
            [
                'role'         => $role,
                'display_name' => $displayName,
                'joined_at'    => now(),
            ]
        );
    }

    public function assign(SupportConversation $conversation, int $agentId): void
    {
        $conversation->update([
            'assigned_agent_id' => $agentId,
            'status'            => SupportConversation::STATUS_ASSIGNED,
        ]);

        $this->addParticipant(
            $conversation->id,
            (string) $agentId,
            SupportConversationParticipant::TYPE_SUPPORT_AGENT,
            SupportConversationParticipant::ROLE_PARTICIPANT
        );
    }

    public function close(SupportConversation $conversation): void
    {
        $conversation->update([
            'status'    => SupportConversation::STATUS_CLOSED,
            'closed_at' => now(),
        ]);
    }

    public function archive(SupportConversation $conversation): void
    {
        $conversation->update(['status' => SupportConversation::STATUS_ARCHIVED]);
    }

    public function listForUser(string $userId, string $userType, array $statuses = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = SupportConversation::whereHas('participants', function ($q) use ($userId, $userType) {
            $q->where('participant_id', $userId)
              ->where('participant_type', $userType)
              ->whereNull('left_at');
        });

        if (!empty($statuses)) {
            $query->whereIn('status', $statuses);
        }

        return $query->orderByDesc('last_message_at')->get();
    }

    public function getWithMessages(string $id): ?SupportConversation
    {
        return SupportConversation::with([
            'messages' => fn ($q) => $q->visible()->orderBy('created_at'),
            'participants',
        ])->find($id);
    }
}

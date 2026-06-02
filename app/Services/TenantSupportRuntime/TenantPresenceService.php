<?php

namespace App\Services\TenantSupportRuntime;

use App\Models\SupportPresence;

class TenantPresenceService
{
    public function setOnline(string $conversationId, string $participantId, string $participantType, ?string $socketId = null): void
    {
        SupportPresence::updateOrCreate(
            [
                'conversation_id'  => $conversationId,
                'participant_id'   => $participantId,
                'participant_type' => $participantType,
            ],
            [
                'status'       => SupportPresence::STATUS_ONLINE,
                'last_seen_at' => now(),
                'socket_id'    => $socketId,
            ]
        );
    }

    public function setOffline(string $conversationId, string $participantId, string $participantType): void
    {
        SupportPresence::where('conversation_id', $conversationId)
            ->where('participant_id', $participantId)
            ->where('participant_type', $participantType)
            ->update([
                'status'    => SupportPresence::STATUS_OFFLINE,
                'is_typing' => false,
            ]);
    }

    public function startTyping(string $conversationId, string $participantId, string $participantType): void
    {
        SupportPresence::updateOrCreate(
            [
                'conversation_id'  => $conversationId,
                'participant_id'   => $participantId,
                'participant_type' => $participantType,
            ],
            [
                'is_typing'         => true,
                'typing_started_at' => now(),
                'last_seen_at'      => now(),
            ]
        );
    }

    public function stopTyping(string $conversationId, string $participantId, string $participantType): void
    {
        SupportPresence::where('conversation_id', $conversationId)
            ->where('participant_id', $participantId)
            ->where('participant_type', $participantType)
            ->update(['is_typing' => false, 'typing_started_at' => null]);
    }

    public function getOnlineParticipants(string $conversationId): \Illuminate\Database\Eloquent\Collection
    {
        return SupportPresence::where('conversation_id', $conversationId)->online()->get();
    }

    public function getTypingParticipants(string $conversationId): \Illuminate\Database\Eloquent\Collection
    {
        return SupportPresence::where('conversation_id', $conversationId)->typing()->get();
    }

    public function pruneStaleTyping(int $secondsThreshold = 10): int
    {
        return SupportPresence::where('is_typing', true)
            ->where('typing_started_at', '<', now()->subSeconds($secondsThreshold))
            ->update(['is_typing' => false, 'typing_started_at' => null]);
    }
}

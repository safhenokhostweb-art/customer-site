<?php

use App\Models\SupportConversation;
use App\Models\SupportConversationParticipant;
use App\Services\TenantSupportRuntime\TenantChannelRegistry;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| PHASE M3.2 — DOMAIN A: Tenant Support Runtime Channels
|--------------------------------------------------------------------------
|
| ALL channels here are scoped to this tenant only.
| Channel names follow the pattern:
|   private-tenant.{slug}.support.{resource}.{id}
|   presence-tenant.{slug}.support.presence.{id}
|
| No platform channels are registered here.
| No cross-tenant channel subscriptions are possible.
|
*/

/** @var TenantChannelRegistry $registry */
$registry = app(TenantChannelRegistry::class);
$slug     = $registry->getTenantSlug();

// ── Conversation private channel ───────────────────────────────────────────
// Only participants of a conversation can subscribe.
Broadcast::channel("private-tenant.{$slug}.support.conversation.{conversationId}", function ($user, string $conversationId) {
    $conversation = SupportConversation::find($conversationId);
    if (!$conversation) return false;

    // Admins and support agents have global access
    if (method_exists($user, 'isAdmin') && $user->isAdmin()) return true;

    return $conversation->participants()
        ->where('participant_id', (string) $user->id)
        ->whereIn('participant_type', [
            SupportConversationParticipant::TYPE_USER,
            SupportConversationParticipant::TYPE_SUPPORT_AGENT,
        ])
        ->whereNull('left_at')
        ->exists();
});

// ── Presence channel for conversation ─────────────────────────────────────
Broadcast::channel("presence-tenant.{$slug}.support.presence.{conversationId}", function ($user, string $conversationId) {
    $conversation = SupportConversation::find($conversationId);
    if (!$conversation) return false;

    if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
        return ['id' => $user->id, 'name' => $user->name, 'type' => 'admin'];
    }

    $participant = $conversation->participants()
        ->where('participant_id', (string) $user->id)
        ->whereNull('left_at')
        ->first();

    if (!$participant) return false;

    return [
        'id'   => $user->id,
        'name' => $user->name ?? 'User',
        'type' => $participant->participant_type,
    ];
});

// ── Agent inbox channel ────────────────────────────────────────────────────
Broadcast::channel("private-tenant.{$slug}.support.agent.{agentId}", function ($user, int $agentId) {
    // Only the exact agent or an admin can listen to an agent inbox
    if (method_exists($user, 'isAdmin') && $user->isAdmin()) return true;
    return (int) $user->id === $agentId;
});

// ── Admin inbox channel ────────────────────────────────────────────────────
Broadcast::channel("private-tenant.{$slug}.support.admin", function ($user) {
    return method_exists($user, 'isAdmin') && $user->isAdmin();
});

// ── User personal channel ──────────────────────────────────────────────────
Broadcast::channel("private-tenant.{$slug}.support.user.{userId}", function ($user, string $userId) {
    return (string) $user->id === $userId;
});

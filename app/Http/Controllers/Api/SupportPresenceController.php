<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportConversationParticipant;
use App\Services\TenantSupportRuntime\TenantPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportPresenceController extends Controller
{
    public function __construct(
        private TenantPresenceService $presence
    ) {}

    public function online(Request $request, string $conversationId): JsonResponse
    {
        $user = $request->user();
        $this->presence->setOnline(
            $conversationId,
            (string) $user->id,
            $this->resolveUserType($user),
            $request->input('socket_id')
        );

        return response()->json(['status' => 'online']);
    }

    public function offline(Request $request, string $conversationId): JsonResponse
    {
        $user = $request->user();
        $this->presence->setOffline(
            $conversationId,
            (string) $user->id,
            $this->resolveUserType($user)
        );

        return response()->json(['status' => 'offline']);
    }

    public function startTyping(Request $request, string $conversationId): JsonResponse
    {
        $user = $request->user();
        $this->presence->startTyping(
            $conversationId,
            (string) $user->id,
            $this->resolveUserType($user)
        );

        return response()->json(['typing' => true]);
    }

    public function stopTyping(Request $request, string $conversationId): JsonResponse
    {
        $user = $request->user();
        $this->presence->stopTyping(
            $conversationId,
            (string) $user->id,
            $this->resolveUserType($user)
        );

        return response()->json(['typing' => false]);
    }

    public function getPresence(string $conversationId): JsonResponse
    {
        return response()->json([
            'online'  => $this->presence->getOnlineParticipants($conversationId),
            'typing'  => $this->presence->getTypingParticipants($conversationId),
        ]);
    }

    private function resolveUserType($user): string
    {
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return SupportConversationParticipant::TYPE_ADMIN;
        }
        return SupportConversationParticipant::TYPE_USER;
    }
}

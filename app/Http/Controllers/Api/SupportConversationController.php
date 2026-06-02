<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportConversationParticipant;
use App\Services\SupportModuleLicenseGate;
use App\Services\TenantSupportRuntime\TenantConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * DOMAIN A — Tenant Support Runtime API.
 *
 * STORAGE GUARANTEE: All data written here stays in this tenant's database.
 * No data is ever sent to saas-platform.
 */
class SupportConversationController extends Controller
{
    public function __construct(
        private TenantConversationService $conversations,
        private SupportModuleLicenseGate  $licenseGate
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user     = $request->user();
        $userId   = (string) $user->id;
        $userType = $this->resolveUserType($user);

        $statuses = $request->query('statuses')
            ? explode(',', $request->query('statuses'))
            : [];

        $list = $this->conversations->listForUser($userId, $userType, $statuses);

        return response()->json(['data' => $list]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'subject'   => 'required|string|max:255',
            'message'   => 'required|string|max:5000',
            'priority'  => 'sometimes|in:low,normal,high,urgent',
            'channel'   => 'sometimes|in:web,mobile,api',
        ]);

        $user     = $request->user();
        $userId   = (string) $user->id;
        $userType = $this->resolveUserType($user);

        $conversation = $this->conversations->create(
            subject:       $request->input('subject'),
            createdById:   $userId,
            createdByType: $userType,
            priority:      $request->input('priority', 'normal'),
            channel:       $request->input('channel', 'api'),
        );

        return response()->json(['data' => $conversation], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user         = $request->user();
        $conversation = $this->conversations->getWithMessages($id);

        if (!$conversation) {
            return response()->json(['error' => 'not_found'], 404);
        }

        if (!$this->userCanAccess($conversation, (string) $user->id, $this->resolveUserType($user))) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        return response()->json(['data' => $conversation]);
    }

    public function assign(Request $request, string $id): JsonResponse
    {
        $request->validate(['agent_id' => 'required|integer']);

        $conversation = \App\Models\SupportConversation::findOrFail($id);
        $this->conversations->assign($conversation, (int) $request->input('agent_id'));

        return response()->json(['data' => $conversation->fresh()]);
    }

    public function close(Request $request, string $id): JsonResponse
    {
        $conversation = \App\Models\SupportConversation::findOrFail($id);
        $this->conversations->close($conversation);

        return response()->json(['data' => $conversation->fresh()]);
    }

    private function resolveUserType($user): string
    {
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return SupportConversationParticipant::TYPE_ADMIN;
        }
        if (method_exists($user, 'hasRole') && $user->hasRole('support_agent')) {
            return SupportConversationParticipant::TYPE_SUPPORT_AGENT;
        }
        return SupportConversationParticipant::TYPE_USER;
    }

    private function userCanAccess($conversation, string $userId, string $userType): bool
    {
        // Admins can always access
        if ($userType === SupportConversationParticipant::TYPE_ADMIN ||
            $userType === SupportConversationParticipant::TYPE_SUPPORT_AGENT) {
            return true;
        }

        // Users can only access their own conversations
        return $conversation->participants()
            ->where('participant_id', $userId)
            ->where('participant_type', $userType)
            ->whereNull('left_at')
            ->exists();
    }
}

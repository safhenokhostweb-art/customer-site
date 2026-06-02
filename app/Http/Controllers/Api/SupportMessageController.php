<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use App\Models\SupportConversationParticipant;
use App\Services\TenantSupportRuntime\TenantAttachmentService;
use App\Services\TenantSupportRuntime\TenantMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * DOMAIN A — Message API for Tenant Support Runtime.
 * All messages stored locally — never forwarded to saas-platform.
 */
class SupportMessageController extends Controller
{
    public function __construct(
        private TenantMessageService    $messages,
        private TenantAttachmentService $attachments
    ) {}

    public function index(Request $request, string $conversationId): JsonResponse
    {
        $this->abortIfNoAccess($request, $conversationId);

        $result = $this->messages->paginate(
            $conversationId,
            (int) $request->query('limit', 50),
            $request->query('before')
        );

        return response()->json($result);
    }

    public function store(Request $request, string $conversationId): JsonResponse
    {
        $this->abortIfNoAccess($request, $conversationId);

        $request->validate([
            'content'      => 'required|string|max:10000',
            'type'         => 'sometimes|in:text,image,file,voice,note',
            'reply_to_id'  => 'sometimes|nullable|uuid',
            'attachments'  => 'sometimes|array|max:5',
            'attachments.*'=> 'file|max:20480',
        ]);

        $user     = $request->user();
        $userId   = (string) $user->id;
        $userType = $this->resolveUserType($user);
        $isNote   = $request->input('type') === 'note';

        $message = $this->messages->send(
            conversationId: $conversationId,
            senderId:       $userId,
            senderType:     $userType,
            content:        $request->input('content'),
            type:           $isNote ? 'note' : $request->input('type', 'text'),
            replyToId:      $request->input('reply_to_id'),
            isNote:         $isNote,
        );

        // Handle file attachments — stored on local tenant filesystem
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $this->attachments->store($file, $message->id, $conversationId, $userId, $userType);
            }
        }

        return response()->json(['data' => $message->load('attachments')], 201);
    }

    public function markRead(Request $request, string $conversationId): JsonResponse
    {
        $user     = $request->user();
        $userId   = (string) $user->id;
        $userType = $this->resolveUserType($user);

        $this->messages->markRead($conversationId, $userId, $userType);

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, string $conversationId, string $messageId): JsonResponse
    {
        $user     = $request->user();
        $userId   = (string) $user->id;
        $userType = $this->resolveUserType($user);

        $deleted = $this->messages->softDelete($messageId, $userId, $userType);

        if (!$deleted) {
            return response()->json(['error' => 'Cannot delete this message.'], 403);
        }

        return response()->json(['success' => true]);
    }

    private function abortIfNoAccess(Request $request, string $conversationId): void
    {
        $conversation = SupportConversation::findOrFail($conversationId);
        $user         = $request->user();
        $userId       = (string) $user->id;
        $userType     = $this->resolveUserType($user);

        if (in_array($userType, [SupportConversationParticipant::TYPE_ADMIN, SupportConversationParticipant::TYPE_SUPPORT_AGENT], true)) {
            return;
        }

        $isParticipant = $conversation->participants()
            ->where('participant_id', $userId)
            ->where('participant_type', $userType)
            ->whereNull('left_at')
            ->exists();

        if (!$isParticipant) {
            abort(403, 'Not a participant of this conversation.');
        }
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
}

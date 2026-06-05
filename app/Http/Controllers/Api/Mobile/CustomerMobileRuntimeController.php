<?php

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Handles all mobile runtime endpoints for end-user (customer-site) context.
 * Returns minimal/empty responses for features not available to end users.
 */
class CustomerMobileRuntimeController extends Controller
{
    // GET /api/mobile/runtime/manifest  (بدون auth middleware — از token می‌خواند)
    public function manifest(Request $request): JsonResponse
    {
        $user = $this->userFromToken($request);

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user_id'       => (string) $user->id,
                'user_name'     => $user->name ?? $user->mobile,
                'mobile'        => $user->mobile,
                'is_admin'      => false,
                'is_site_admin' => false,
                'site_id'       => null,
                'roles'         => ['end_user'],
                'role'          => 'end_user',
                'features' => [
                    'tickets'       => true,
                    'conversations' => false,
                    'notifications' => false,
                ],
            ],
        ]);
    }

    private function userFromToken(Request $request): ?\App\Models\User
    {
        $header = $request->header('Authorization', '');
        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }
        $token = substr($header, 7);
        return \App\Models\User::where('api_token', hash('sha256', $token))->first();
    }

    // GET /api/mobile/conversations → لیست خالی (کاربر نهایی conversations ندارد)
    public function conversations(): JsonResponse
    {
        return response()->json(['data' => [], 'meta' => ['total' => 0]]);
    }

    // GET /api/mobile/conversations/{id}
    public function conversationShow(): JsonResponse
    {
        return response()->json(['message' => 'Not available.'], 404);
    }

    // GET /api/mobile/notifications
    public function notifications(): JsonResponse
    {
        return response()->json(['data' => [], 'meta' => ['unread' => 0, 'total' => 0]]);
    }

    // POST /api/mobile/notifications/read-all
    public function notificationsReadAll(): JsonResponse
    {
        return response()->json(['success' => true]);
    }

    // GET /api/mobile/notifications/preferences
    public function notificationPreferences(): JsonResponse
    {
        return response()->json(['data' => ['enabled' => true]]);
    }

    // POST /api/mobile/notifications/push-token
    public function registerPushToken(): JsonResponse
    {
        return response()->json(['success' => true]);
    }

    // GET /api/mobile/inbox
    public function inbox(): JsonResponse
    {
        return response()->json(['data' => [], 'meta' => ['total' => 0]]);
    }

    // GET /api/mobile/hub
    public function hub(): JsonResponse
    {
        return response()->json(['data' => [], 'unread_count' => 0]);
    }

    // GET /api/mobile/realtime/config
    public function realtimeConfig(): JsonResponse
    {
        return response()->json(['data' => ['enabled' => false]]);
    }

    // GET /api/mobile/theme/tokens
    public function themeTokens(): JsonResponse
    {
        return response()->json(['data' => []]);
    }

    // POST /api/mobile/presence/heartbeat
    public function heartbeat(): JsonResponse
    {
        return response()->json(['success' => true]);
    }

    // POST /api/mobile/devices
    public function registerDevice(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => []]);
    }

    // GET /api/mobile/ota/manifest
    public function otaManifest(): JsonResponse
    {
        return response()->json(['data' => ['has_update' => false]]);
    }

    // POST /api/mobile/auth/refresh
    private function extractToken(Request $request): string
    {
        $header = $request->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return '';
    }
}

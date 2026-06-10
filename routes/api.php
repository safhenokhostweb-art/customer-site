<?php

use App\Http\Controllers\Api\SupportConversationController;
use App\Http\Controllers\Api\SupportMessageController;
use App\Http\Controllers\Api\SupportPresenceController;
use App\Http\Controllers\Api\Mobile\CustomerMobileAuthController;
use App\Http\Controllers\Api\Mobile\CustomerMobileTicketController;
use App\Http\Controllers\Api\Mobile\CustomerMobileRuntimeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PHASE M3.2 — DOMAIN A: Tenant Support Runtime API Routes
|--------------------------------------------------------------------------
|
| All routes here are protected by:
| 1. auth:sanctum — authenticated user
| 2. support.license — SupportModuleLicenseCheck middleware
|
| No support data ever leaves this tenant's server.
| No platform routes are registered here.
|
*/

use App\Http\Controllers\Api\AiQueueController;
use App\Http\Controllers\Api\PushSubscriptionController;

// ─── AI Queue API — worker لوکال و frontend polling ───────────────────────────
Route::prefix('ai-queue')->group(function () {
    Route::get('/pending',           [AiQueueController::class, 'pending']);
    Route::post('/{id}/complete',    [AiQueueController::class, 'complete']);
    Route::get('/{id}/status',       [AiQueueController::class, 'status']);
});

// ─── Web Push Subscription API ────────────────────────────────────────────────
Route::prefix('push')->group(function () {
    Route::get('vapid-key',   [PushSubscriptionController::class, 'vapidPublicKey']);
    Route::middleware('auth')->group(function () {
        Route::post('subscribe',   [PushSubscriptionController::class, 'store']);
        Route::post('unsubscribe', [PushSubscriptionController::class, 'destroy']);
    });
});

// ─── Mobile API for end users ────────────────────────────────────────────────
// کاربران نهایی با آدرس http://customer-site.test وارد اپ می‌شوند

Route::prefix('mobile')->group(function () {
    // لاگین + refresh بدون احراز هویت (از loop بی‌نهایت جلوگیری می‌شود)
    Route::post('auth/token',   [CustomerMobileAuthController::class, 'token']);
    Route::post('auth/refresh', [CustomerMobileAuthController::class, 'refreshToken']);

    // manifest بدون middleware (برای _restore در app startup)
    Route::get('runtime/manifest', [CustomerMobileRuntimeController::class, 'manifest']);

    // روت‌های نیازمند توکن
    Route::middleware('mobile.auth')->group(function () {
        // ── Auth ────────────────────────────────────────────────────────────
        Route::post('auth/logout',  [CustomerMobileAuthController::class, 'logout']);

        // ── تیکت‌های پشتیبانی کاربر نهایی ───────────────────────────────────
        Route::get('tickets',             [CustomerMobileTicketController::class, 'index']);
        Route::post('tickets',            [CustomerMobileTicketController::class, 'store']);
        Route::get('tickets/{id}',        [CustomerMobileTicketController::class, 'show'])->where('id', '[0-9]+');
        Route::post('tickets/{id}/reply', [CustomerMobileTicketController::class, 'reply'])->where('id', '[0-9]+');

        // ── Stubs — اپ این endpointها را صدا می‌زند اما برای کاربر نهایی لازم نیستند
        Route::get('conversations',                 [CustomerMobileRuntimeController::class, 'conversations']);
        Route::get('conversations/{id}',            [CustomerMobileRuntimeController::class, 'conversationShow']);
        Route::post('conversations',                fn() => response()->json(['data' => [], 'meta' => []]));
        Route::get('notifications',                 [CustomerMobileRuntimeController::class, 'notifications']);
        Route::post('notifications/read-all',       [CustomerMobileRuntimeController::class, 'notificationsReadAll']);
        Route::get('notifications/preferences',     [CustomerMobileRuntimeController::class, 'notificationPreferences']);
        Route::post('notifications/push-token',     [CustomerMobileRuntimeController::class, 'registerPushToken']);
        Route::delete('notifications/push-token',   fn() => response()->json(['success' => true]));
        Route::get('inbox',                         [CustomerMobileRuntimeController::class, 'inbox']);
        Route::get('hub',                           [CustomerMobileRuntimeController::class, 'hub']);
        Route::post('hub/bulk-read',                fn() => response()->json(['success' => true]));
        Route::get('realtime/config',               [CustomerMobileRuntimeController::class, 'realtimeConfig']);
        Route::post('realtime/auth',                fn() => response()->json(['success' => false]));
        Route::get('realtime/stats',                fn() => response()->json(['data' => []]));
        Route::get('theme/tokens',                  [CustomerMobileRuntimeController::class, 'themeTokens']);
        Route::get('theme/css-vars',                fn() => response()->json(['data' => []]));
        Route::post('presence/heartbeat',           [CustomerMobileRuntimeController::class, 'heartbeat']);
        Route::post('devices',                      [CustomerMobileRuntimeController::class, 'registerDevice']);
        Route::put('devices/{id}',                  fn() => response()->json(['success' => true]));
        Route::delete('devices/{id}',               fn() => response()->json(['success' => true]));
        Route::get('ota/manifest',                  [CustomerMobileRuntimeController::class, 'otaManifest']);
        Route::post('ota/check',                    fn() => response()->json(['has_update' => false]));
        Route::get('dashboard', function (\Illuminate\Http\Request $request) {
            $user  = $request->user();
            $query = \SaaSPlatform\Modules\Tickets\Models\Ticket::withoutTenantIsolation()->whereNull('site_id');
            if (!($user->is_admin ?? false)) {
                $query->where(function ($q) use ($user) {
                    $q->where('customer_id', $user->id);
                    if ($user->mobile) $q->orWhere('customer_mobile', $user->mobile);
                });
            }
            $total      = (clone $query)->count();
            $open       = (clone $query)->where('status', 'open')->count();
            $inProgress = (clone $query)->where('status', 'in_progress')->count();
            $closed     = (clone $query)->where('status', 'closed')->count();
            return response()->json(['data' => ['tickets' => [
                'total' => $total, 'open' => $open, 'in_progress' => $inProgress, 'closed' => $closed,
            ]]]);
        });
    });
});

// ─────────────────────────────────────────────────────────────────────────────

Route::middleware(['auth:sanctum', 'support.license'])->group(function () {

    // ── Conversations ──────────────────────────────────────────────────────
    Route::prefix('support/conversations')->group(function () {
        Route::get('/', [SupportConversationController::class, 'index']);
        Route::post('/', [SupportConversationController::class, 'store']);
        Route::get('/{id}', [SupportConversationController::class, 'show']);
        Route::post('/{id}/assign', [SupportConversationController::class, 'assign']);
        Route::post('/{id}/close', [SupportConversationController::class, 'close']);

        // ── Messages ───────────────────────────────────────────────────────
        Route::get('/{conversationId}/messages', [SupportMessageController::class, 'index']);
        Route::post('/{conversationId}/messages', [SupportMessageController::class, 'store']);
        Route::post('/{conversationId}/messages/read', [SupportMessageController::class, 'markRead']);
        Route::delete('/{conversationId}/messages/{messageId}', [SupportMessageController::class, 'destroy']);

        // ── Presence ───────────────────────────────────────────────────────
        Route::get('/{conversationId}/presence', [SupportPresenceController::class, 'getPresence']);
        Route::post('/{conversationId}/presence/online', [SupportPresenceController::class, 'online']);
        Route::post('/{conversationId}/presence/offline', [SupportPresenceController::class, 'offline']);
        Route::post('/{conversationId}/presence/typing/start', [SupportPresenceController::class, 'startTyping']);
        Route::post('/{conversationId}/presence/typing/stop', [SupportPresenceController::class, 'stopTyping']);
    });

});

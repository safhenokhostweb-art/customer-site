<?php

use App\Http\Controllers\Api\SupportConversationController;
use App\Http\Controllers\Api\SupportMessageController;
use App\Http\Controllers\Api\SupportPresenceController;
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

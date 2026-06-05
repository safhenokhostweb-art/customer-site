<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint'         => 'required|string|max:500',
            'public_key'       => 'required|string|max:200',
            'auth_token'       => 'required|string|max:100',
            'content_encoding' => 'nullable|string|max:20',
            'panel_context'    => 'nullable|string|max:30',
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id'          => auth()->id(),
                'public_key'       => $data['public_key'],
                'auth_token'       => $data['auth_token'],
                'content_encoding' => $data['content_encoding'] ?? 'aesgcm',
                'panel_context'    => $data['panel_context'] ?? 'customer',
            ]
        );

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $endpoint = $request->input('endpoint');

        if ($endpoint) {
            PushSubscription::where('endpoint', $endpoint)
                ->where('user_id', auth()->id())
                ->delete();
        }

        return response()->json(['success' => true]);
    }

    public function vapidPublicKey(): JsonResponse
    {
        return response()->json([
            'public_key' => config('push.vapid_public_key'),
        ]);
    }
}

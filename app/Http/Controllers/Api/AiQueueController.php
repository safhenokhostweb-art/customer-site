<?php

namespace App\Http\Controllers\Api;

use App\Models\AiGenerationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * API برای worker لوکال — دریافت job، ارسال نتیجه، بررسی وضعیت
 * احراز هویت: Bearer token از AI_QUEUE_TOKEN در .env
 */
class AiQueueController extends Controller
{
    private function authorize(Request $request): bool
    {
        $token = config('ai_assistant.queue_token');
        if (empty($token)) {
            return false;
        }
        return $request->bearerToken() === $token;
    }

    /** GET /api/ai-queue/pending — worker لوکال این را poll می‌کند */
    public function pending(Request $request): JsonResponse
    {
        if (! $this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $jobs = AiGenerationJob::where('status', 'pending')
            ->orderBy('created_at')
            ->limit(5)
            ->get(['id', 'type', 'mode', 'prompt', 'extra_context', 'step_count']);

        // علامت‌گذاری به عنوان در حال پردازش
        if ($jobs->isNotEmpty()) {
            AiGenerationJob::whereIn('id', $jobs->pluck('id'))
                ->update(['status' => 'processing', 'picked_at' => now()]);
        }

        return response()->json(['jobs' => $jobs]);
    }

    /** POST /api/ai-queue/{id}/complete — worker نتیجه را push می‌کند */
    public function complete(Request $request, int $id): JsonResponse
    {
        if (! $this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $job = AiGenerationJob::findOrFail($id);

        $data = $request->validate([
            'success'  => 'required|boolean',
            'result'   => 'required_if:success,true',
            'error'    => 'required_if:success,false|nullable|string',
            'provider' => 'nullable|string',
        ]);

        $job->update([
            'status'   => $data['success'] ? 'done' : 'failed',
            'result'   => $data['success'] ? json_encode($data['result']) : null,
            'error'    => $data['error'] ?? null,
            'provider' => $data['provider'] ?? null,
        ]);

        return response()->json(['ok' => true]);
    }

    /** GET /api/ai-queue/{id}/status — frontend polling */
    public function status(int $id): JsonResponse
    {
        $job = AiGenerationJob::findOrFail($id);

        $response = [
            'id'     => $job->id,
            'status' => $job->status,
        ];

        if ($job->isDone()) {
            $response['data'] = $job->resultData();
        } elseif ($job->isFailed()) {
            $response['error'] = $job->error;
        }

        return response()->json($response);
    }
}

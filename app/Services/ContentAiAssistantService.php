<?php

namespace App\Services;

use App\Models\AiGenerationJob;
use App\Models\SiteContent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * دستیار هوشمند تولید محتوا
 * اولویت: GapGPT (ایران، مستقیم) → DeepSeek (اینترنت بین‌الملل) → Claude → Windows Worker queue
 */
class ContentAiAssistantService
{
    private static array $SYSTEM_PROMPT_MAP = [
        SiteContent::TYPE_ARTICLE      => 'یک نویسنده حرفه‌ای فارسی هستی که مقالات جذاب و مفید می‌نویسی.',
        SiteContent::TYPE_TRAINING     => 'یک مدرس حرفه‌ای هستی که آموزش‌های گام‌به‌گام واضح و کاربردی می‌نویسی.',
        SiteContent::TYPE_WORKFLOW     => 'یک متخصص مدیریت فرآیند هستی که فرآیندهای کاری را به صورت شفاف و قابل اجرا مستند می‌کنی.',
        SiteContent::TYPE_PROCEDURE    => 'یک متخصص مستندسازی هستی که روال‌ها و مراحل کاری را به صورت دقیق و گام‌به‌گام می‌نویسی.',
        SiteContent::TYPE_PRODUCT      => 'یک کپی‌رایتر حرفه‌ای هستی که توضیحات محصول جذاب، متقاعدکننده و مزیت‌محور می‌نویسی.',
        SiteContent::TYPE_HELP         => 'یک متخصص پشتیبانی فنی هستی که راهنماهای کاربری ساده و قابل فهم می‌نویسی.',
    ];

    public function generate(string $type, string $prompt, ?string $extraContext = null): array
    {
        $systemPrompt = $this->buildSystemPrompt($type, $extraContext);
        $userPrompt   = $this->buildUserPrompt($type, $prompt);

        $direct = $this->tryServerDirect($systemPrompt, $userPrompt);
        if ($direct['success']) {
            return $direct;
        }

        $job = AiGenerationJob::create([
            'type'          => $type,
            'mode'          => 'full',
            'prompt'        => $prompt,
            'extra_context' => $extraContext,
            'status'        => 'pending',
        ]);

        return [
            'success' => false,
            'queued'  => true,
            'job_id'  => $job->id,
            'error'   => 'درخواست در صف قرار گرفت — لطفاً چند لحظه صبر کنید.',
        ];
    }

    public function generateWithSteps(string $type, string $prompt, int $stepCount = 5): array
    {
        $systemPrompt = self::$SYSTEM_PROMPT_MAP[$type] ?? self::$SYSTEM_PROMPT_MAP[SiteContent::TYPE_TRAINING];
        $userPrompt   = $this->buildStepsPrompt($type, $prompt, $stepCount);

        $direct = $this->tryServerDirect($systemPrompt, $userPrompt, true);
        if ($direct['success']) {
            return $this->parseStepResponse($direct['content']);
        }

        $job = AiGenerationJob::create([
            'type'       => $type,
            'mode'       => 'steps',
            'prompt'     => $prompt,
            'step_count' => $stepCount,
            'status'     => 'pending',
        ]);

        return [
            'success' => false,
            'queued'  => true,
            'job_id'  => $job->id,
            'error'   => 'درخواست در صف قرار گرفت — لطفاً چند لحظه صبر کنید.',
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // اولویت: GapGPT → DeepSeek → Claude
    // ──────────────────────────────────────────────────────────────────────────

    private function tryServerDirect(string $system, string $user, bool $jsonMode = false): array
    {
        // ── ۱. GapGPT — مستقیم از VPS ایران ─────────────────────────────────
        $gapKey = config('ai_assistant.gapgpt.api_key');
        if (!empty($gapKey)) {
            $result = $this->callOpenAiCompatible(
                $gapKey,
                config('ai_assistant.gapgpt.base_url', 'https://api.gapgpt.app/v1'),
                config('ai_assistant.gapgpt.model', 'gpt-4o-mini'),
                config('ai_assistant.gapgpt.timeout', 60),
                $system, $user, $jsonMode,
                'gapgpt'
            );
            if ($result['success']) {
                return $result;
            }
            Log::info('GapGPT failed: ' . ($result['error'] ?? ''));
        }

        // ── ۲. DeepSeek — اگر اینترنت بین‌الملل وصل باشد ────────────────────
        $dsKey = config('ai_assistant.deepseek.api_key');
        if (!empty($dsKey)) {
            $result = $this->callOpenAiCompatible(
                $dsKey,
                config('ai_assistant.deepseek.base_url', 'https://api.deepseek.com/v1'),
                config('ai_assistant.deepseek.model', 'deepseek-chat'),
                config('ai_assistant.deepseek.timeout', 60),
                $system, $user, $jsonMode,
                'deepseek'
            );
            if ($result['success']) {
                return $result;
            }
            Log::info('DeepSeek failed: ' . ($result['error'] ?? ''));
        }

        // ── ۳. Claude fallback ────────────────────────────────────────────────
        $claudeKey = config('ai_assistant.claude.api_key');
        if (!empty($claudeKey)) {
            $result = $this->callClaude($claudeKey, $system, $user, $jsonMode);
            if ($result['success']) {
                return $result;
            }
            Log::info('Claude failed: ' . ($result['error'] ?? ''));
        }

        return ['success' => false, 'error' => 'no_provider'];
    }

    // OpenAI-compatible (GapGPT, DeepSeek, هر سرویس سازگار)
    private function callOpenAiCompatible(
        string $key,
        string $baseUrl,
        string $model,
        int    $timeout,
        string $system,
        string $user,
        bool   $jsonMode,
        string $providerName
    ): array {
        try {
            $payload = [
                'model'       => $model,
                'messages'    => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user',   'content' => $user],
                ],
                'max_tokens'  => config('ai_assistant.max_tokens', 4096),
                'temperature' => config('ai_assistant.temperature', 0.7),
            ];
            if ($jsonMode) {
                $payload['response_format'] = ['type' => 'json_object'];
            }

            $response = Http::withToken($key)
                ->timeout($timeout)
                ->post(rtrim($baseUrl, '/') . '/chat/completions', $payload);

            if ($response->successful()) {
                return [
                    'success'  => true,
                    'content'  => $response->json('choices.0.message.content', ''),
                    'provider' => $providerName,
                ];
            }

            return ['success' => false, 'error' => $providerName . ' HTTP ' . $response->status()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function callClaude(string $key, string $system, string $user, bool $jsonMode): array
    {
        try {
            $payload = [
                'model'      => config('ai_assistant.claude.model', 'claude-sonnet-4-6'),
                'max_tokens' => config('ai_assistant.max_tokens', 4096),
                'system'     => $system,
                'messages'   => [['role' => 'user', 'content' => $user]],
            ];

            $response = Http::withHeaders([
                    'x-api-key'         => $key,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ])
                ->timeout(config('ai_assistant.claude.timeout', 60))
                ->post(config('ai_assistant.claude.base_url') . '/messages', $payload);

            if ($response->successful()) {
                $content = $response->json('content.0.text', '');
                if ($jsonMode) {
                    $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
                    $content = preg_replace('/\s*```$/m', '', $content);
                    $content = trim($content);
                }
                return ['success' => true, 'content' => $content, 'provider' => 'claude'];
            }

            return ['success' => false, 'error' => 'Claude HTTP ' . $response->status()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function buildSystemPrompt(string $type, ?string $context): string
    {
        $base = self::$SYSTEM_PROMPT_MAP[$type] ?? 'یک نویسنده حرفه‌ای فارسی هستی.';
        $base .= ' محتوا را به زبان فارسی روان، حرفه‌ای و کاربردی بنویس.';
        if ($context) {
            $base .= " زمینه کسب‌وکار: {$context}";
        }
        return $base;
    }

    private function buildUserPrompt(string $type, string $prompt): string
    {
        $typeName = SiteContent::$TYPES[$type] ?? $type;
        return "یک {$typeName} کامل و حرفه‌ای درباره موضوع زیر بنویس:\n\n{$prompt}\n\n"
             . "محتوا باید شامل مقدمه، بدنه اصلی با جزئیات کافی، و نتیجه‌گیری باشد. "
             . "از تیترهای واضح و پاراگراف‌بندی مناسب استفاده کن.";
    }

    private function buildStepsPrompt(string $type, string $prompt, int $stepCount): string
    {
        return "موضوع: {$prompt}\n\n"
            . "لطفاً یک " . (SiteContent::$TYPES[$type] ?? $type) . " کامل با {$stepCount} مرحله بنویس.\n"
            . "خروجی را به صورت JSON با این ساختار برگردان:\n"
            . "{\n"
            . "  \"title\": \"عنوان اصلی\",\n"
            . "  \"excerpt\": \"خلاصه یک پاراگراف\",\n"
            . "  \"steps\": [\n"
            . "    {\"title\": \"عنوان مرحله\", \"body\": \"توضیح کامل مرحله\"}\n"
            . "  ]\n"
            . "}";
    }

    private function parseStepResponse(string $json): array
    {
        // حذف ```json ... ``` اگر مدل آن را برگرداند
        $json = preg_replace('/^```(?:json)?\s*/m', '', $json);
        $json = preg_replace('/\s*```$/m', '', $json);
        $data = json_decode(trim($json), true);
        if (!$data || !isset($data['title'])) {
            return ['success' => false, 'error' => 'خروجی AI قابل پردازش نبود.'];
        }
        return ['success' => true, 'data' => $data];
    }
}

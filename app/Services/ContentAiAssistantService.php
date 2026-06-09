<?php

namespace App\Services;

use App\Models\SiteContent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * دستیار هوشمند تولید محتوا
 * اول DeepSeek، fallback به Claude
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

        $provider = config('ai_assistant.primary', 'deepseek');

        try {
            $result = $this->callProvider($provider, $systemPrompt, $userPrompt);
            if ($result['success']) {
                return $result;
            }
            throw new \RuntimeException($result['error'] ?? 'provider failed');
        } catch (\Throwable $e) {
            Log::warning("AI primary provider ({$provider}) failed: " . $e->getMessage());
        }

        $fallback = config('ai_assistant.fallback', 'claude');
        if ($fallback && $fallback !== $provider) {
            try {
                return $this->callProvider($fallback, $systemPrompt, $userPrompt);
            } catch (\Throwable $e) {
                Log::error("AI fallback provider ({$fallback}) failed: " . $e->getMessage());
            }
        }

        return ['success' => false, 'error' => 'هیچ سرویس هوش مصنوعی‌ای در دسترس نیست.', 'content' => null];
    }

    public function generateWithSteps(string $type, string $prompt, int $stepCount = 5): array
    {
        $systemPrompt = self::$SYSTEM_PROMPT_MAP[$type] ?? self::$SYSTEM_PROMPT_MAP[SiteContent::TYPE_TRAINING];
        $userPrompt   = "موضوع: {$prompt}\n\n"
            . "لطفاً یک {$this->typeName($type)} کامل با {$stepCount} مرحله بنویس.\n"
            . "خروجی را به صورت JSON با این ساختار برگردان:\n"
            . "{\n"
            . "  \"title\": \"عنوان اصلی\",\n"
            . "  \"excerpt\": \"خلاصه یک پاراگراف\",\n"
            . "  \"steps\": [\n"
            . "    {\"title\": \"عنوان مرحله\", \"body\": \"توضیح کامل مرحله\"}\n"
            . "  ]\n"
            . "}";

        $provider = config('ai_assistant.primary', 'deepseek');
        try {
            $result = $this->callProvider($provider, $systemPrompt, $userPrompt, true);
            if ($result['success']) {
                return $this->parseStepResponse($result['content']);
            }
        } catch (\Throwable $e) {
            Log::warning("AI steps generation failed: " . $e->getMessage());
        }

        $fallback = config('ai_assistant.fallback', 'claude');
        if ($fallback && $fallback !== $provider) {
            try {
                $result = $this->callProvider($fallback, $systemPrompt, $userPrompt, true);
                if ($result['success']) {
                    return $this->parseStepResponse($result['content']);
                }
            } catch (\Throwable $e) {
                Log::error("AI fallback steps failed: " . $e->getMessage());
            }
        }

        return ['success' => false, 'error' => 'هیچ سرویس هوش مصنوعی‌ای در دسترس نیست.'];
    }

    private function callProvider(string $provider, string $system, string $user, bool $jsonMode = false): array
    {
        return match ($provider) {
            'deepseek' => $this->callDeepSeek($system, $user, $jsonMode),
            'claude'   => $this->callClaude($system, $user),
            default    => ['success' => false, 'error' => "Unknown provider: {$provider}"],
        };
    }

    private function callDeepSeek(string $system, string $user, bool $jsonMode = false): array
    {
        $key = config('ai_assistant.deepseek.api_key');
        if (empty($key)) {
            return ['success' => false, 'error' => 'DEEPSEEK_API_KEY تنظیم نشده'];
        }

        $payload = [
            'model'    => config('ai_assistant.deepseek.model', 'deepseek-chat'),
            'messages' => [
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
            ->timeout(config('ai_assistant.deepseek.timeout', 60))
            ->post(config('ai_assistant.deepseek.base_url') . '/chat/completions', $payload);

        if (!$response->successful()) {
            return ['success' => false, 'error' => 'DeepSeek HTTP ' . $response->status()];
        }

        $content = $response->json('choices.0.message.content', '');
        return ['success' => true, 'content' => $content, 'provider' => 'deepseek'];
    }

    private function callClaude(string $system, string $user): array
    {
        $key = config('ai_assistant.claude.api_key');
        if (empty($key)) {
            return ['success' => false, 'error' => 'CLAUDE_API_KEY تنظیم نشده'];
        }

        $response = Http::withHeaders([
            'x-api-key'         => $key,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])
        ->timeout(config('ai_assistant.claude.timeout', 60))
        ->post(config('ai_assistant.claude.base_url') . '/messages', [
            'model'      => config('ai_assistant.claude.model', 'claude-sonnet-4-6'),
            'max_tokens' => config('ai_assistant.max_tokens', 4096),
            'system'     => $system,
            'messages'   => [['role' => 'user', 'content' => $user]],
        ]);

        if (!$response->successful()) {
            return ['success' => false, 'error' => 'Claude HTTP ' . $response->status()];
        }

        $content = $response->json('content.0.text', '');
        return ['success' => true, 'content' => $content, 'provider' => 'claude'];
    }

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
        $typeName = $this->typeName($type);
        return "یک {$typeName} کامل و حرفه‌ای درباره موضوع زیر بنویس:\n\n{$prompt}\n\n"
             . "محتوا باید شامل مقدمه، بدنه اصلی با جزئیات کافی، و نتیجه‌گیری باشد. "
             . "از تیترهای واضح و پاراگراف‌بندی مناسب استفاده کن.";
    }

    private function parseStepResponse(string $json): array
    {
        $data = json_decode($json, true);
        if (!$data || !isset($data['title'])) {
            return ['success' => false, 'error' => 'خروجی AI قابل پردازش نبود.'];
        }
        return ['success' => true, 'data' => $data];
    }

    private function typeName(string $type): string
    {
        return SiteContent::$TYPES[$type] ?? $type;
    }
}

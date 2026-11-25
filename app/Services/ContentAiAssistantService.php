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

    // ──────────────────────────────────────────────────────────────────────────
    // SEO-optimized content generation (returns JSON with title, html_body,
    // excerpt, meta_description, focus_keyword, tags, image_keywords)
    // ──────────────────────────────────────────────────────────────────────────

    public function generateSeoContent(string $type, string $prompt, ?string $extraContext = null): array
    {
        $systemPrompt = $this->buildSeoSystemPrompt($type, $extraContext);
        $userPrompt   = $this->buildSeoUserPrompt($type, $prompt);

        $direct = $this->tryServerDirect($systemPrompt, $userPrompt, true);
        if ($direct['success']) {
            return $this->parseSeoResponse($direct['content'], $direct['provider'] ?? 'ai');
        }

        $job = AiGenerationJob::create([
            'type'          => $type,
            'mode'          => 'seo',
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

    private function buildSeoSystemPrompt(string $type, ?string $context): string
    {
        $roleMap = [
            SiteContent::TYPE_ARTICLE  => 'متخصص SEO و نویسنده محتوای فارسی',
            SiteContent::TYPE_PRODUCT  => 'کپی‌رایتر و متخصص SEO برای فروشگاه‌های آنلاین فارسی',
            'blog'                     => 'متخصص SEO و نویسنده وبلاگ حرفه‌ای فارسی',
            'product'                  => 'کپی‌رایتر و متخصص SEO برای فروشگاه‌های آنلاین فارسی',
        ];
        $role = $roleMap[$type] ?? 'متخصص SEO و نویسنده محتوای حرفه‌ای فارسی';

        $prompt = "تو یک {$role} هستی. همیشه خروجی JSON معتبر با این فیلدها برمی‌گردانی:\n"
                . "- \"title\": عنوان اصلی (50-60 کاراکتر، SEO-friendly، شامل کلمه کلیدی)\n"
                . "- \"html_body\": محتوای HTML کامل — تگ‌های h2، h3، p، ul، li، strong — بدون h1 — حداقل ۴ بخش h2 هر کدام با ۲-۳ پاراگراف\n"
                . "- \"excerpt\": خلاصه ۱-۲ جمله (150-160 کاراکتر)\n"
                . "- \"meta_description\": توضیحات متا 150-160 کاراکتر برای گوگل (شامل call-to-action)\n"
                . "- \"focus_keyword\": کلمه کلیدی اصلی (1-3 کلمه فارسی)\n"
                . "- \"tags\": آرایه JSON از 5-8 برچسب فارسی\n"
                . "- \"image_keywords\": آرایه JSON از 3 کلمه انگلیسی برای جستجوی تصویر (مثلاً [\"product photography\", \"shopping\"])\n"
                . "فقط JSON خالص برگردان، بدون توضیح، بدون markdown.";

        if ($context) {
            $prompt .= "\n\nزمینه کسب‌وکار: {$context}";
        }
        return $prompt;
    }

    private function buildSeoUserPrompt(string $type, string $prompt): string
    {
        $typeLabels = [
            SiteContent::TYPE_ARTICLE => 'مقاله',
            SiteContent::TYPE_PRODUCT => 'صفحه محصول',
            'blog'                    => 'مطلب وبلاگ',
            'product'                 => 'صفحه محصول',
        ];
        $typeName = $typeLabels[$type] ?? 'محتوا';

        return "یک {$typeName} کامل، حرفه‌ای و SEO-optimized درباره موضوع زیر بنویس:\n\n«{$prompt}»\n\n"
             . "ساختار html_body الزامی:\n"
             . "- پاراگراف مقدمه جذاب که کلمه کلیدی در آن هست\n"
             . "- حداقل ۴ بخش با تیتر h2\n"
             . "- هر بخش ۲-۳ پاراگراف توضیح کامل\n"
             . "- در جاهای مناسب از h3 و ul/li استفاده کن\n"
             . "- یک نتیجه‌گیری قوی با call-to-action\n"
             . "- از h1 استفاده نکن\n"
             . "خروجی فقط JSON.";
    }

    private function parseSeoResponse(string $json, string $provider): array
    {
        $json = preg_replace('/^```(?:json)?\s*/m', '', $json);
        $json = preg_replace('/\s*```$/m', '', $json);
        $data = json_decode(trim($json), true);

        if (!$data || !isset($data['title'])) {
            return ['success' => false, 'error' => 'خروجی AI قابل پردازش نبود'];
        }

        return [
            'success'  => true,
            'provider' => $provider,
            'data'     => [
                'title'            => $data['title'] ?? '',
                'html_body'        => $data['html_body'] ?? '',
                'excerpt'          => $data['excerpt'] ?? '',
                'meta_description' => $data['meta_description'] ?? '',
                'focus_keyword'    => $data['focus_keyword'] ?? '',
                'tags'             => is_array($data['tags'])
                    ? implode('، ', $data['tags'])
                    : ($data['tags'] ?? ''),
                'image_keywords'   => is_array($data['image_keywords'])
                    ? $data['image_keywords']
                    : [],
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────

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
    // اتصال انحصاری به GapGPT (پیکربندی همگام از پلتفرم ساس یا .env محلی)
    // ──────────────────────────────────────────────────────────────────────────

    private function tryServerDirect(string $system, string $user, bool $jsonMode = false): array
    {
        $cfg = app(PlatformUxService::class)->getGapGptConfig();
        $gapKey = (string) ($cfg['api_key'] ?? '');

        if ($gapKey !== '') {
            $result = $this->callOpenAiCompatible(
                $gapKey,
                $cfg['base_url'] ?: 'https://api.gapgpt.app/v1',
                $cfg['model'] ?: 'gpt-4o-mini',
                (int) ($cfg['timeout'] ?? 60),
                $system, $user, $jsonMode,
                'gapgpt'
            );
            if ($result['success']) {
                return $result;
            }
            Log::info('GapGPT failed: ' . ($result['error'] ?? ''));
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
             . "ساختار HTML الزامی:\n"
             . "- خروجی باید HTML معتبر باشد\n"
             . "- با یک پاراگراف مقدمه <p> شروع کن\n"
             . "- از <h2> برای بخش‌های اصلی و <h3> برای زیربخش‌ها استفاده کن\n"
             . "- هر بخش حداقل ۲ پاراگراف <p> داشته باشد\n"
             . "- در جاهای مناسب از <ul><li> استفاده کن\n"
             . "- از <h1> استفاده نکن\n"
             . "- با نتیجه‌گیری ختم کن\n"
             . "فقط HTML خالص بدون توضیح اضافه.";
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

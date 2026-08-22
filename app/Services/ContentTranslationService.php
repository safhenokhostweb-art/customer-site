<?php

namespace App\Services;

use App\Models\ContentTranslation;
use App\Models\TranslationQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContentTranslationService
{
    private const LOCALES = ['ar', 'en'];

    private const LOCALE_NAMES = [
        'ar' => 'Arabic',
        'en' => 'English',
    ];

    private const MAX_ATTEMPTS = 3;

    public function translateAndStore(
        string $modelClass,
        int    $modelId,
        string $field,
        string $sourceText,
        string $locale
    ): bool {
        if (!in_array($locale, self::LOCALES)) {
            return false;
        }

        $hash = md5($sourceText);

        // Check if already translated and up-to-date
        $existing = ContentTranslation::findFor($modelClass, $modelId, $locale, $field);
        if ($existing && $existing->source_hash === $hash) {
            return true;
        }

        $translated = $this->callAi($sourceText, $locale, $field);
        if ($translated === null) {
            return false;
        }

        ContentTranslation::updateOrCreate(
            [
                'translatable_type' => $modelClass,
                'translatable_id'   => $modelId,
                'locale'            => $locale,
                'field_name'        => $field,
            ],
            [
                'translated_text' => $translated,
                'source_hash'     => $hash,
                'ai_model'        => $this->modelName(),
                'generated_at'    => now(),
            ]
        );

        return true;
    }

    public function processQueue(int $batchSize = 20): array
    {
        $items = TranslationQueue::where('status', TranslationQueue::STATUS_PENDING)
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->orderBy('id')
            ->limit($batchSize)
            ->get();

        $stats = ['processed' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($items as $item) {
            $item->update(['status' => TranslationQueue::STATUS_PROCESSING, 'attempts' => $item->attempts + 1]);

            try {
                $model = $item->translatable_type::find($item->translatable_id);
                if (!$model) {
                    $item->update(['status' => TranslationQueue::STATUS_FAILED, 'last_error' => 'Model not found']);
                    $stats['failed']++;
                    continue;
                }

                $sourceText = (string) ($model->{$item->field_name} ?? '');
                if (empty(trim($sourceText))) {
                    $item->update(['status' => TranslationQueue::STATUS_DONE, 'processed_at' => now()]);
                    $stats['skipped']++;
                    continue;
                }

                $ok = $this->translateAndStore(
                    $item->translatable_type,
                    $item->translatable_id,
                    $item->field_name,
                    $sourceText,
                    $item->locale
                );

                if ($ok) {
                    $item->update(['status' => TranslationQueue::STATUS_DONE, 'processed_at' => now()]);
                    $stats['processed']++;
                } else {
                    $item->update(['status' => TranslationQueue::STATUS_FAILED, 'last_error' => 'AI returned null']);
                    $stats['failed']++;
                }
            } catch (\Throwable $e) {
                $item->update([
                    'status'     => $item->attempts >= self::MAX_ATTEMPTS
                        ? TranslationQueue::STATUS_FAILED
                        : TranslationQueue::STATUS_PENDING,
                    'last_error' => substr($e->getMessage(), 0, 500),
                ]);
                $stats['failed']++;
                Log::error('TranslationService error', ['item' => $item->id, 'error' => $e->getMessage()]);
            }
        }

        return $stats;
    }

    // ─── Private ────────────────────────────────────────────────────────────

    private function callAi(string $text, string $locale, string $field): ?string
    {
        $apiKey = config('services.anthropic.key', '');
        if (!$apiKey) {
            return null;
        }

        $targetLang = self::LOCALE_NAMES[$locale] ?? $locale;
        $fieldLabel = match ($field) {
            'title'             => 'a title',
            'excerpt'           => 'a short excerpt',
            'body', 'content'   => 'article body content',
            'description'       => 'a description',
            'short_description' => 'a short description',
            default             => 'text content',
        };

        $systemPrompt = "You are a professional translator specializing in Persian to {$targetLang} translation for e-commerce and educational websites. Translate accurately while preserving tone, formatting (HTML if present), and special terms. Return ONLY the translated text — no explanations, no quotes.";

        $userPrompt = "Translate this {$fieldLabel} from Persian to {$targetLang}:\n\n{$text}";

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 4096,
                'system'     => $systemPrompt,
                'messages'   => [
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);

            if (!$response->successful()) {
                Log::warning('TranslationService: AI API error', ['status' => $response->status()]);
                return null;
            }

            $data = $response->json();
            $translated = $data['content'][0]['text'] ?? null;
            return $translated ? trim($translated) : null;
        } catch (\Throwable $e) {
            Log::error('TranslationService: HTTP error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function modelName(): string
    {
        return 'claude-haiku-4-5-20251001';
    }

    public static function supportedLocales(): array
    {
        return self::LOCALES;
    }
}

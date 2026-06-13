<?php

namespace App\Traits;

use App\Models\ContentTranslation;
use App\Models\TranslationQueue;

/**
 * Attach to any Eloquent model to make it translatable.
 *
 * Usage:
 *   class Post extends Model {
 *       use HasTranslations;
 *       protected array $translatable = ['title', 'excerpt', 'body'];
 *   }
 *
 * Then in views: $post->translated('title', 'ar')
 */
trait HasTranslations
{
    /** Locales we translate into (source is always 'fa'). */
    public static array $supportedLocales = ['ar', 'en'];

    public static function bootHasTranslations(): void
    {
        static::saved(function ($model) {
            $model->queueTranslations();
        });
    }

    /**
     * Get a translated value for a field.
     * Falls back to the original value if no translation exists.
     */
    public function translated(string $field, string $locale): string
    {
        if ($locale === 'fa' || !in_array($locale, self::$supportedLocales)) {
            return (string) ($this->{$field} ?? '');
        }

        $hash = md5((string) ($this->{$field} ?? ''));
        $cached = ContentTranslation::findFor(
            static::class,
            (int) $this->getKey(),
            $locale,
            $field
        );

        if ($cached && $cached->source_hash === $hash) {
            return $cached->translated_text;
        }

        // Stale or missing — return original while translation queues
        return (string) ($this->{$field} ?? '');
    }

    /**
     * Queue all dirty translatable fields for translation.
     */
    public function queueTranslations(): void
    {
        $fields = $this->translatable ?? [];
        if (empty($fields)) return;

        foreach ($fields as $field) {
            $value = $this->{$field} ?? '';
            if (empty($value)) continue;

            $hash = md5((string) $value);
            foreach (self::$supportedLocales as $locale) {
                $existing = ContentTranslation::findFor(
                    static::class,
                    (int) $this->getKey(),
                    $locale,
                    $field
                );

                // Only queue if missing or stale
                if (!$existing || $existing->source_hash !== $hash) {
                    TranslationQueue::enqueue(
                        static::class,
                        (int) $this->getKey(),
                        $locale,
                        $field
                    );
                }
            }
        }
    }

    /**
     * Queue all fields for all locales (force re-translate).
     */
    public function forceQueueTranslations(): void
    {
        $fields = $this->translatable ?? [];
        foreach ($fields as $field) {
            foreach (self::$supportedLocales as $locale) {
                TranslationQueue::enqueue(
                    static::class,
                    (int) $this->getKey(),
                    $locale,
                    $field
                );
            }
        }
    }

    public function translations()
    {
        return $this->morphMany(ContentTranslation::class, 'translatable');
    }
}

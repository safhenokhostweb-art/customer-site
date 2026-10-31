<?php

namespace App\Services\ThemeBuilder;

use Throwable;

/**
 * Canvas overhaul Unit 2e: the reason a block rendered as a placeholder,
 * shown in the editor only. Before, the editor showed a bare "[invalid]
 * bento_grid" and the cause ("Array to string conversion" on an unresolved
 * {media_id}) was visible only in the server log.
 *
 * The text is for the site's own admins: absolute paths are cut to file
 * names and the length is bounded. Public pages never receive it.
 */
final class PlaceholderDetail
{
    private const MAX_LENGTH = 300;

    public const REASON_LABELS = [
        'invalid_schema' => 'داده این بلوک با تعریف آن سازگار نیست',
        'render_error' => 'نمایش این بلوک با خطا متوقف شد',
    ];

    public static function fromThrowable(Throwable $e): string
    {
        $where = basename($e->getFile()) . ':' . $e->getLine();

        return self::clean($e->getMessage() . ' (' . $where . ')');
    }

    /** @param list<string> $errors */
    public static function fromErrors(array $errors): string
    {
        return self::clean(implode(' · ', array_map('strval', $errors)));
    }

    /** Editor-only markup: the Persian reason, then the technical detail. */
    public static function html(string $reason, string $detail): string
    {
        if ($detail === '') {
            return '';
        }
        $label = self::REASON_LABELS[$reason] ?? self::REASON_LABELS['render_error'];

        return '<span class="tb-placeholder-detail" dir="rtl">' . e($label) . '<code dir="ltr">' . e($detail) . '</code></span>';
    }

    private static function clean(string $text): string
    {
        // C:\…\vendor\…\BlockRenderer.php or /var/www/…/BlockRenderer.php → BlockRenderer.php
        $text = preg_replace('~(?:[A-Za-z]:)?[\\\\/](?:[^\\\\/\s:]+[\\\\/])+([^\\\\/\s:]+\.php)~', '$1', $text) ?? $text;
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

        return mb_strlen($text) > self::MAX_LENGTH ? mb_substr($text, 0, self::MAX_LENGTH - 1) . '…' : $text;
    }
}

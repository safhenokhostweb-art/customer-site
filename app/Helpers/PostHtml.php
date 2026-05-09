<?php

namespace App\Helpers;

/**
 * خروجی امن HTML برای محتوای ویرایشگر غنی (بدون script/style/on*).
 */
class PostHtml
{
    public static function safe(?string $html): string
    {
        $html = (string) $html;
        if ($html === '') {
            return '';
        }

        $allowed = '<p><br><br/><strong><b><em><i><u><s><strike><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><img><figure><figcaption><div><span><hr>';

        $clean = strip_tags($html, $allowed);

        $clean = preg_replace_callback(
            '/<img\b[^>]*>/iu',
            function (array $m) {
                if (! preg_match('/src\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', $m[0], $sm)) {
                    return '';
                }
                $src = html_entity_decode(trim($sm[1], '"\''), ENT_QUOTES, 'UTF-8');
                if (! self::isSafeHref($src) || str_starts_with(strtolower($src), 'mailto:')) {
                    return '';
                }

                return '<img src="'.htmlspecialchars($src, ENT_QUOTES, 'UTF-8').'" alt="" loading="lazy">';
            },
            $clean
        ) ?? $clean;

        return preg_replace_callback(
            '/<a\s+([^>]*?)>/iu',
            function (array $m) {
                $attrs = $m[1];
                if (! preg_match('/href\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', $attrs, $hrefMatch)) {
                    return '<a>';
                }
                $href = html_entity_decode(trim($hrefMatch[1], '"\''), ENT_QUOTES, 'UTF-8');
                if (! self::isSafeHref($href)) {
                    return '<a>';
                }
                $escaped = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');

                return '<a href="'.$escaped.'" rel="noopener noreferrer">';
            },
            $clean
        ) ?? $clean;
    }

    private static function isSafeHref(string $href): bool
    {
        $h = strtolower(trim($href));
        if ($h === '' || str_starts_with($h, 'javascript:') || str_starts_with($h, 'data:')) {
            return false;
        }
        if (str_starts_with($h, 'http://') || str_starts_with($h, 'https://') || str_starts_with($h, '/') || str_starts_with($h, '#') || str_starts_with($h, 'mailto:')) {
            return true;
        }

        return false;
    }
}

<?php

namespace App\Helpers;

use SaaSPlatform\ThemeRuntime\Security\HtmlSanitizer;

/**
 * خروجی امن HTML برای محتوای ویرایشگر غنی (بدون script/style/on*).
 *
 * Phase 3H-S1 (security closure) — this is a genuinely different rich-text
 * product domain from Theme "custom_html" blocks (blog posts / static pages
 * authored via a WYSIWYG editor: images, headings 1-6, blockquote, figure/
 * figcaption), so its own, wider tag allowlist is deliberately preserved —
 * converging it onto canonical HtmlSanitizer::sanitizeRichHtml()'s narrower
 * allowlist would silently delete real stored content (images, most
 * headings). What was NOT domain-specific and has been fixed:
 *
 * 1. URL-scheme safety (`isSafeHref()`) now delegates to the canonical
 *    `HtmlSanitizer::isValidUrl()` authority instead of maintaining an
 *    independent scheme allowlist/denylist that could drift from it.
 * 2. Every allowed tag other than the two this class already rebuilt from
 *    scratch (`<img>`, `<a>`) now also has ALL attributes stripped — pre
 *    -fix, `strip_tags()` alone never touched attributes on tags it kept,
 *    so an `on*` event-handler attribute survived verbatim on `<p>`,
 *    `<div>`, `<span>`, every heading, etc. (the same class of gap the
 *    3H-A audit found in Theme "custom_html" rendering).
 */
class PostHtml
{
    /** Tags this class already reconstructs from scratch — never touched by the generic attribute strip below. */
    private const REBUILT_TAGS = ['img', 'a'];

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

        $clean = preg_replace_callback(
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

        // Every remaining allowed tag (anything but the two rebuilt above)
        // is reduced to its bare name — closes the on*-attribute-survival
        // gap without touching the domain-specific tag allowlist itself.
        return preg_replace_callback(
            '/<([a-z][a-z0-9]*)\b[^>]*>/i',
            function (array $m) {
                $tag = strtolower($m[1]);
                if (in_array($tag, self::REBUILT_TAGS, true)) {
                    return $m[0];
                }

                return "<{$tag}>";
            },
            $clean
        ) ?? $clean;
    }

    private static function isSafeHref(string $href): bool
    {
        $trimmed = trim($href);
        if ($trimmed === '') {
            return false;
        }

        return HtmlSanitizer::isValidUrl($trimmed);
    }

    /**
     * Real table-of-contents extraction for the article-detail template
     * (Commercial Productization fase-2: "فهرست مطالب در صورت امکان" was a
     * real, confirmed gap). Takes HTML already passed through `safe()`
     * (never raw editor input — DOMDocument here does no sanitization of
     * its own) and returns [htmlWithAnchoredHeadings, tocItems]. An article
     * with no h2/h3 headings returns the html unchanged and an empty TOC —
     * the caller simply omits the TOC block rather than showing an empty
     * shell.
     *
     * @return array{0: string, 1: list<array{id: string, text: string, level: int}>}
     */
    public static function withHeadingIds(string $safeHtml): array
    {
        if ($safeHtml === '' || !str_contains($safeHtml, '<h2') && !str_contains($safeHtml, '<h3')) {
            return [$safeHtml, []];
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><div>'.$safeHtml.'</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $toc = [];
        $usedIds = [];
        foreach (['h2', 'h3'] as $tagName) {
            foreach (iterator_to_array($dom->getElementsByTagName($tagName)) as $node) {
                $text = trim($node->textContent);
                if ($text === '') {
                    continue;
                }
                $slug = self::slugify($text);
                $id = $slug;
                $i = 2;
                while (isset($usedIds[$id])) {
                    $id = $slug.'-'.$i++;
                }
                $usedIds[$id] = true;
                $node->setAttribute('id', $id);
                $toc[] = ['id' => $id, 'text' => $text, 'level' => (int) substr($tagName, 1), '_pos' => $node->getLineNo()];
            }
        }

        if (empty($toc)) {
            return [$safeHtml, []];
        }

        // Headings were collected h2-then-h3, not document order — restore
        // reading order via each node's line number before returning.
        usort($toc, fn ($a, $b) => $a['_pos'] <=> $b['_pos']);
        $toc = array_map(fn ($t) => ['id' => $t['id'], 'text' => $t['text'], 'level' => $t['level']], $toc);

        $wrapper = $dom->getElementsByTagName('div')->item(0);
        $html = '';
        foreach (iterator_to_array($wrapper->childNodes) as $child) {
            $html .= $dom->saveHTML($child);
        }

        return [$html, $toc];
    }

    private static function slugify(string $text): string
    {
        $slug = trim(preg_replace('/[^\p{L}\p{N}]+/u', '-', $text), '-');

        return $slug !== '' ? mb_strtolower($slug) : 'heading';
    }
}

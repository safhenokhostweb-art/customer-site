<?php

namespace App\Services\Seo;

/**
 * Phase 4.1 — H1 integrity detection for Canvas-rendered pages.
 *
 * READ-ONLY. This service never rewrites, injects or removes markup. It
 * reports; the caller decides what to do. The Canvas layout already has its
 * own hidden-`<h1>` fallback (layouts/canvas-theme.blade.php), and this phase
 * deliberately does not touch it — changing markup is a later decision, not a
 * detection one.
 *
 * Why a real parser instead of a regex: a substring count miscounts every
 * `<h1` that is not an element — inside a <script>, a comment, a text node,
 * or an attribute value. An authoring tool that cries wolf on those is worse
 * than no tool at all, so the count comes from the parsed DOM.
 */
class HeaderIntegrityService
{
    public const VIOLATION_NONE     = 'none';
    public const VIOLATION_MISSING  = 'missing_h1';
    public const VIOLATION_MULTIPLE = 'multiple_h1';

    /**
     * Inspect rendered Canvas content and report its H1 integrity.
     *
     * @return array{has_h1: bool, h1_count: int, is_valid: bool, violation: string}
     */
    public function inspect(string $html): array
    {
        $count = $this->countH1($html);

        return [
            'has_h1'    => $count > 0,
            'h1_count'  => $count,
            // Exactly one H1 is the semantic-SEO expectation. Zero and two-or-more
            // are both reported as violations, but with distinct `violation`
            // values so the log says which mistake was actually made.
            'is_valid'  => $count === 1,
            'violation' => match (true) {
                $count === 0  => self::VIOLATION_MISSING,
                $count > 1    => self::VIOLATION_MULTIPLE,
                default       => self::VIOLATION_NONE,
            },
        ];
    }

    /**
     * Count real <h1> elements in a fragment of HTML.
     */
    private function countH1(string $html): int
    {
        if (trim($html) === '') {
            return 0;
        }

        // Sound fast path: an <h1> ELEMENT cannot exist without the literal
        // "<h1" appearing somewhere (any casing, any attributes). This is what
        // keeps TTFB flat for the overwhelmingly common "no H1 at all" case —
        // the full DOM parse is then never paid for. It is a substring check
        // only as a negative filter; the authoritative count below is still
        // the parsed DOM, so a false positive here costs a parse, not a wrong
        // answer.
        if (stripos($html, '<h1') === false) {
            return 0;
        }

        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            // The XML declaration is libxml's standard hint for treating the
            // bytes as UTF-8; without it a Persian page is parsed as ISO-8859-1
            // and every tag is mangled. LIBXML_NONET keeps a stray
            // external-reference-looking string from attempting any fetch.
            $loaded = $document->loadHTML(
                '<?xml encoding="UTF-8">' . $html,
                LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
            );
        } catch (\Throwable) {
            // A malformed fragment must never break a page render.
            return 0;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($loaded === false || $document->documentElement === null) {
            return 0;
        }

        // HTML parsing lowercases element names, so this also matches <H1>.
        return $document->getElementsByTagName('h1')->length;
    }
}

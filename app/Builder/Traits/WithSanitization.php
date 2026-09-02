<?php

namespace App\Builder\Traits;

use SaaSPlatform\ThemeRuntime\Security\HtmlSanitizer;

trait WithSanitization
{
    protected static function escape(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    protected static function escapeAttr(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Phase 3E-C: delegates to the shared canonical sanitization authority
     * (SaaSPlatform\ThemeRuntime\Security\HtmlSanitizer) instead of this
     * trait's own independent strip_tags()-only copy. The prior local copy
     * never stripped attributes on tags it kept — the same stored-XSS
     * class Phase 3C fixed in the canonical renderer, left unpatched here.
     * There is now exactly one implementation of this logic.
     */
    protected static function sanitizeHtml(string $s): string
    {
        return HtmlSanitizer::sanitizeRichHtml($s);
    }

    /**
     * Phase 3E-C: delegates to HtmlSanitizer::isValidUrl() — see
     * sanitizeHtml() above. This trait's own copy was already byte-identical
     * to canonical's, but kept as a second independent implementation it
     * would have been free to silently drift from.
     */
    protected static function isValidUrl(string $url): bool
    {
        return HtmlSanitizer::isValidUrl($url);
    }
}

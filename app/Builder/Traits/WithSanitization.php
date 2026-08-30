<?php

namespace App\Builder\Traits;

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
     * Allow safe HTML tags in rich text.
     */
    protected static function sanitizeHtml(string $s): string
    {
        return strip_tags($s, '<p><br><a><strong><em><ul><ol><li><h2><h3>');
    }

    /**
     * Validate URL for href/src to prevent javascript: etc.
     *
     * Phase 2j: was `FILTER_VALIDATE_URL`, which rejects relative URLs
     * ("/services", "about.html") — real, common, and safe (no scheme to
     * exploit). Only an explicit dangerous scheme is rejected; only a
     * known-safe explicit scheme is accepted; no scheme at all (relative)
     * passes through. Kept in sync with the promoted canonical
     * SaaSPlatform\ThemeRuntime\BlockRenderer::isValidUrl().
     */
    protected static function isValidUrl(string $url): bool
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return true;
        }
        if (preg_match('#^[\s\x00-\x1f]*(javascript|data|vbscript):#i', $trimmed)) {
            return false;
        }
        if (!preg_match('#^[a-z][a-z0-9+.\-]*:#i', $trimmed)) {
            return true;
        }
        return (bool) preg_match('#^(https?|mailto|tel):#i', $trimmed);
    }
}

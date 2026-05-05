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
     */
    protected static function isValidUrl(string $url): bool
    {
        if ($url === '' || $url === '#') {
            return true;
        }
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && !preg_match('#^\s*javascript:#i', $url);
    }
}

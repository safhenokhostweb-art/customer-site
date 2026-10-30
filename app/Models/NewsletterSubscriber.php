<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Phase 0.7 (saas-platform ADR-066): a newsletter sign-up from a theme
 * newsletter block. `contact` is normalised (lower-case email or
 * 09xxxxxxxxx mobile) and unique.
 */
class NewsletterSubscriber extends Model
{
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_MOBILE = 'mobile';

    protected $fillable = ['contact', 'channel', 'source', 'ip_hash', 'unsubscribed_at'];

    protected $casts = [
        'unsubscribed_at' => 'datetime',
    ];

    /**
     * ['channel' => …, 'contact' => …] for an email or an Iranian mobile in
     * any digit script/format, or null when it is neither. The block's
     * script (site-forms.js normalizeContact) applies the same rules.
     *
     * @return array{channel: string, contact: string}|null
     */
    public static function normalizeContact(mixed $raw): ?array
    {
        if (!is_string($raw)) {
            return null;
        }
        $text = trim(\SaaSPlatform\ThemeRuntime\Support\PersianNumber::latinDigits($raw));
        if ($text === '') {
            return null;
        }
        if (str_contains($text, '@')) {
            $email = mb_strtolower($text);
            return strlen($email) <= 190 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
                ? ['channel' => self::CHANNEL_EMAIL, 'contact' => $email]
                : null;
        }
        $digits = preg_replace('/[\s\-().]/', '', $text);
        return preg_match('/^(?:\+98|0098|98|0)?(9\d{9})$/', $digits, $m) === 1
            ? ['channel' => self::CHANNEL_MOBILE, 'contact' => '0' . $m[1]]
            : null;
    }
}

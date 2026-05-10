<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Session;

class CaptchaService
{
    public const TYPE_PERSIAN_NUMBER = 'persian_number';
    public const TYPE_NORMAL = 'normal';
    public const TYPE_HCAPTCHA = 'hcaptcha';

    public static function isModuleEnabled(): bool
    {
        return (string) Setting::get('captcha_enabled', '0') === '1';
    }

    public static function getForSite(?int $siteId = null): array
    {
        $enabled = (string) Setting::get('captcha_enabled', '0') === '1';
        $type = (string) Setting::get('captcha_type', self::TYPE_PERSIAN_NUMBER);
        $siteKey = (string) Setting::get('hcaptcha_site_key', '');
        $secretKey = (string) Setting::get('hcaptcha_secret_key', '');

        return [
            'enabled' => $enabled,
            'type' => $type,
            'hcaptcha_site_key' => $siteKey,
            'hcaptcha_secret_key' => $secretKey,
        ];
    }

    /** کپچا روی فرم ورود فقط وقتی ماژول فعال است و گزینهٔ «ورود و ثبت‌نام» در پنل روشن باشد */
    public static function isAuthCaptchaEnabled(): bool
    {
        if (!self::isModuleEnabled()) {
            return false;
        }

        return (string) Setting::get('auth_captcha_enabled', '0') === '1';
    }

    /**
     * @return array{enabled: bool, type: ?string, hcaptcha_site_key: string, hcaptcha_secret_key: string}
     */
    public static function getAuthCaptchaConfig(): array
    {
        if (!self::isAuthCaptchaEnabled()) {
            return ['enabled' => false, 'type' => null, 'hcaptcha_site_key' => '', 'hcaptcha_secret_key' => ''];
        }
        $cfg = self::getForSite(null);
        $cfg['enabled'] = true;

        return $cfg;
    }

    public static function generatePersianNumber(int $length = 4): string
    {
        $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $persianDigits[random_int(0, 9)];
        }
        Session::put('captcha_persian_answer', $code);
        return $code;
    }

    public static function persianToEnglish(string $input): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($persian, $english, $input);
    }

    public static function validatePersianNumber(string $userInput): bool
    {
        $expected = Session::pull('captcha_persian_answer');
        if (!$expected) {
            return false;
        }
        $normalized = self::persianToEnglish(trim($userInput));
        $expectedNormalized = self::persianToEnglish($expected);
        return $normalized === $expectedNormalized;
    }

    public static function numberToPersian(int $n): string
    {
        $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $s = (string) $n;
        $out = '';
        for ($i = 0; $i < strlen($s); $i++) {
            $out .= $persianDigits[(int) $s[$i]];
        }
        return $out;
    }

    public static function generateNormal(): array
    {
        $a = random_int(1, 12);
        $b = random_int(1, 12);
        $sum = $a + $b;
        Session::put('captcha_normal_answer', (string) $sum);
        return [
            'text' => self::numberToPersian($a) . ' + ' . self::numberToPersian($b) . ' = ?',
            'answer' => (string) $sum,
        ];
    }

    public static function validateNormal(string $userInput): bool
    {
        $expected = Session::pull('captcha_normal_answer');
        if ($expected === null || $expected === '') {
            return false;
        }
        $normalized = trim(self::persianToEnglish($userInput));
        return $normalized === $expected;
    }

    public static function verifyHcaptcha(string $token, string $secretKey): bool
    {
        if (!$secretKey) {
            return false;
        }
        if (trim($token) === '') {
            return false;
        }
        if (config('app.env') === 'local' && filter_var(config('services.hcaptcha.skip_in_local', false), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }
        $response = \Illuminate\Support\Facades\Http::timeout(10)->asForm()->post('https://hcaptcha.com/siteverify', [
            'secret' => $secretKey,
            'response' => $token,
        ]);
        $data = $response->json();
        return isset($data['success']) && $data['success'] === true;
    }
}

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
     * Phase 5 / Wave 5.3 / P1-5: کپچا روی نقاط نوشتنی فروشگاه (checkout/review/stock-notify)
     * فقط وقتی ماژول کپچا فعال است **و** ادمین در صفحهٔ تنظیمات کپچا گزینهٔ
     * «کپچا روی فرم‌های فروشگاه» را روشن کرده باشد.
     */
    public static function isShopCaptchaEnabled(): bool
    {
        if (!self::isModuleEnabled()) {
            return false;
        }
        return (string) Setting::get('shop_captcha_enabled', '0') === '1';
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

    /**
     * Phase 5 / Wave 5.3 / P1-5: تولید پایلود کپچا برای فرم‌های فروشگاه.
     * **فقط برای کاربر مهمان** فعال است؛ کاربر authenticated همیشه bypass می‌شود
     * (rate-limit بر اساس user_id کافی است).
     *
     * @return array{enabled: bool, type: ?string, persian_code?: string, normal_text?: string, hcaptcha_site_key?: string, hcaptcha_secret_key?: string}
     */
    public static function getShopCaptchaConfig(): array
    {
        if (!self::isShopCaptchaEnabled() || auth()->check()) {
            return ['enabled' => false, 'type' => null];
        }
        $cfg = self::getForSite(null);
        $cfg['enabled'] = true;
        if (($cfg['type'] ?? '') === self::TYPE_PERSIAN_NUMBER) {
            $cfg['persian_code'] = self::generatePersianNumber(4);
        } elseif (($cfg['type'] ?? '') === self::TYPE_NORMAL) {
            $gen = self::generateNormal();
            $cfg['normal_text'] = $gen['text'];
        }
        return $cfg;
    }

    /**
     * Phase 5 / Wave 5.3 / P1-5: اعتبارسنجی پاسخ کپچا برای فرم‌های فروشگاه.
     * اگر ماژول shop-captcha خاموش است یا کاربر authenticated است ⇒ همیشه `true`.
     * در غیر این صورت بسته به نوع کپچا، فیلد ورودی استاندارد بررسی می‌شود:
     *   - persian_number, normal ⇒ `captcha_answer`
     *   - hcaptcha                 ⇒ `h-captcha-response`
     */
    public static function validateShopCaptcha(\Illuminate\Http\Request $request): bool
    {
        if (!self::isShopCaptchaEnabled() || auth()->check()) {
            return true;
        }
        $cfg = self::getForSite(null);
        $type = $cfg['type'] ?? '';
        if ($type === self::TYPE_PERSIAN_NUMBER) {
            return self::validatePersianNumber((string) $request->input('captcha_answer', ''));
        }
        if ($type === self::TYPE_NORMAL) {
            return self::validateNormal((string) $request->input('captcha_answer', ''));
        }
        if ($type === self::TYPE_HCAPTCHA) {
            return self::verifyHcaptcha(
                (string) $request->input('h-captcha-response', ''),
                (string) ($cfg['hcaptcha_secret_key'] ?? '')
            );
        }
        return false;
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

<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Session;
use SaaSPlatform\CoreUtils\Contracts\Services\CaptchaServiceInterface;

class CaptchaService implements CaptchaServiceInterface
{
    public const TYPE_PERSIAN_NUMBER = 'persian_number';
    public const TYPE_NORMAL = 'normal';
    public const TYPE_HCAPTCHA = 'hcaptcha';

    // Interface required methods
    public function isEnabled(): bool
    {
        return $this->isModuleEnabled();
    }

    public function getConfigForSite(?int $siteId = null): array
    {
        return $this->getForSite($siteId);
    }

    // Existing methods...
    public function isModuleEnabled(): bool
    {
        return (string) Setting::get('captcha_enabled', '0') === '1';
    }

    public function getForSite(?int $siteId = null): array
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


    /**
     * Phase 5 / Wave 5.3 / P1-5: تولید پایلود کپچا برای فرم‌های فروشگاه.
     * **فقط برای کاربر مهمان** فعال است؛ کاربر authenticated همیشه bypass می‌شود
     * (rate-limit بر اساس user_id کافی است).
     *
     * @return array{enabled: bool, type: ?string, persian_code?: string, normal_text?: string, hcaptcha_site_key?: string, hcaptcha_secret_key?: string}
     */
    public function getShopCaptchaConfig(): array
    {
        if (!$this->isShopCaptchaEnabled() || auth()->check()) {
            return ['enabled' => false, 'type' => null];
        }
        $cfg = $this->getForSite(null);
        $cfg['enabled'] = true;
        if (($cfg['type'] ?? '') === self::TYPE_PERSIAN_NUMBER) {
            $cfg['persian_code'] = $this->generatePersianNumber(4);
        } elseif (($cfg['type'] ?? '') === self::TYPE_NORMAL) {
            $gen = $this->generateNormal();
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
    public function validateShopCaptcha(\Illuminate\Http\Request $request): bool
    {
        if (!$this->isShopCaptchaEnabled() || auth()->check()) {
            return true;
        }
        $cfg = $this->getForSite(null);
        $type = $cfg['type'] ?? '';
        if ($type === self::TYPE_PERSIAN_NUMBER) {
            return $this->validatePersianNumber((string) $request->input('captcha_answer', ''));
        }
        if ($type === self::TYPE_NORMAL) {
            return $this->validateNormal((string) $request->input('captcha_answer', ''));
        }
        if ($type === self::TYPE_HCAPTCHA) {
            return $this->verifyHcaptcha(
                (string) $request->input('h-captcha-response', ''),
                (string) ($cfg['hcaptcha_secret_key'] ?? '')
            );
        }
        return false;
    }

    public function generatePersianNumber(int $length = 4): string
    {
        $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $persianDigits[random_int(0, 9)];
        }
        Session::put('captcha_persian_answer', $code);
        return $code;
    }

    public function persianToEnglish(string $input): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($persian, $english, $input);
    }

    public function validatePersianNumber(string $userInput): bool
    {
        $expected = Session::pull('captcha_persian_answer');
        if (!$expected) {
            return false;
        }
        $normalized = $this->persianToEnglish(trim($userInput));
        $expectedNormalized = $this->persianToEnglish($expected);
        return $normalized === $expectedNormalized;
    }

    public function numberToPersian(int $n): string
    {
        $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $s = (string) $n;
        $out = '';
        for ($i = 0; $i < strlen($s); $i++) {
            $out .= $persianDigits[(int) $s[$i]];
        }
        return $out;
    }

    public function generateNormal(): array
    {
        $a = random_int(1, 12);
        $b = random_int(1, 12);
        $sum = $a + $b;
        Session::put('captcha_normal_answer', (string) $sum);
        return [
            'text' => $this->numberToPersian($a) . ' + ' . $this->numberToPersian($b) . ' = ?',
            'answer' => (string) $sum,
        ];
    }

    public function validateNormal(string $userInput): bool
    {
        $expected = Session::pull('captcha_normal_answer');
        if ($expected === null || $expected === '') {
            return false;
        }
        $normalized = trim($this->persianToEnglish($userInput));
        return $normalized === $expected;
    }

    public function verifyHcaptcha(string $token, string $secretKey): bool
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

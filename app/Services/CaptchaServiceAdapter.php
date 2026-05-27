<?php

namespace App\Services;

use SaaSPlatform\CoreUtils\Contracts\Services\CaptchaServiceInterface;

/**
 * Adapter that implements the package interface and delegates to the
 * existing static CaptchaService helpers, preserving backward compat.
 */
class CaptchaServiceAdapter implements CaptchaServiceInterface
{
    public function isEnabled(): bool
    {
        return CaptchaService::isModuleEnabled();
    }

    public function getConfigForSite(?int $siteId): array
    {
        return CaptchaService::getForSite($siteId);
    }

    public function generatePersianNumber(int $length): string
    {
        return CaptchaService::generatePersianNumber($length);
    }

    public function generateNormal(): array
    {
        return CaptchaService::generateNormal();
    }

    public function validatePersianNumber(string $answer): bool
    {
        return CaptchaService::validatePersianNumber($answer);
    }

    public function validateNormal(string $answer): bool
    {
        return CaptchaService::validateNormal($answer);
    }

    public function verifyHcaptcha(string $token, string $secretKey): bool
    {
        return CaptchaService::verifyHcaptcha($token, $secretKey);
    }
}

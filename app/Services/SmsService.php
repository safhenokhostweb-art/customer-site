<?php

namespace App\Services;

use App\Models\Setting;

class SmsService
{
    public function send(string $to, string $message): bool
    {
        $provider = Setting::get('sms_provider', config('sms.provider', ''));
        $apiKey = Setting::get('sms_api_key', config('sms.api_key', ''));
        $lineNumber = Setting::get('sms_line_number', config('sms.line_number', ''));

        if (empty($provider) || empty($apiKey)) {
            return false;
        }

        // Placeholder for actual SMS gateway integration
        // Each provider (Kavenegar, Ghasedak, etc.) would have its own implementation
        return true;
    }
}

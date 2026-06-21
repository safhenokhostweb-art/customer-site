<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private string $provider;
    private string $apiKey;
    private string $lineNumber;

    public function __construct()
    {
        $this->provider   = Setting::get('sms_provider', config('sms.provider', ''));
        $this->apiKey     = Setting::get('sms_api_key', config('sms.api_key', ''));
        $this->lineNumber = Setting::get('sms_line_number', config('sms.line_number', ''));
    }

    public function isConfigured(): bool
    {
        return !empty($this->provider) && !empty($this->apiKey);
    }

    public function send(string $to, string $message): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('SmsService: not configured', ['provider' => $this->provider]);
            return false;
        }

        $to = $this->normalizeNumber($to);

        return match ($this->provider) {
            'kavenegar' => $this->sendKavenegar($to, $message),
            'ghasedak'  => $this->sendGhasedak($to, $message),
            default     => false,
        };
    }

    public function sendBulk(array $recipients, string $message): array
    {
        $results = [];
        foreach ($recipients as $mobile) {
            $results[$mobile] = $this->send($mobile, $message);
            // Brief pause to avoid gateway rate limiting
            usleep(50_000);
        }
        return $results;
    }

    private function sendKavenegar(string $to, string $message): bool
    {
        try {
            $response = Http::timeout(10)->post(
                "https://api.kavenegar.com/v1/{$this->apiKey}/sms/send.json",
                [
                    'receptor' => $to,
                    'message'  => $message,
                    'sender'   => $this->lineNumber,
                ]
            );

            if ($response->successful()) {
                return ($response->json('return.status') ?? 0) === 200;
            }

            Log::warning('SmsService[kavenegar]: send failed', ['status' => $response->status(), 'to' => $to]);
            return false;
        } catch (\Throwable $e) {
            Log::error('SmsService[kavenegar]: exception', ['message' => $e->getMessage(), 'to' => $to]);
            return false;
        }
    }

    private function sendGhasedak(string $to, string $message): bool
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['apikey' => $this->apiKey])
                ->post('https://api.ghasedak.me/v2/sms/send/simple', [
                    'receptor'    => $to,
                    'message'     => $message,
                    'linenumber'  => $this->lineNumber,
                ]);

            if ($response->successful()) {
                return ($response->json('result.code') ?? 0) === 200;
            }

            Log::warning('SmsService[ghasedak]: send failed', ['status' => $response->status(), 'to' => $to]);
            return false;
        } catch (\Throwable $e) {
            Log::error('SmsService[ghasedak]: exception', ['message' => $e->getMessage(), 'to' => $to]);
            return false;
        }
    }

    private function normalizeNumber(string $mobile): string
    {
        $mobile = preg_replace('/\D/', '', $mobile);
        if (str_starts_with($mobile, '0')) {
            $mobile = '98' . substr($mobile, 1);
        }
        if (strlen($mobile) === 10) {
            $mobile = '98' . $mobile;
        }
        return $mobile;
    }
}

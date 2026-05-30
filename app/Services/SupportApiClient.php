<?php

namespace App\Services;

use App\Models\License;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SupportApiClient
{
    public function index(License $license): array
    {
        $response = $this->request('get', '/api/tenant/tickets', $license, []);

        return [
            'tickets' => collect($response['tickets'] ?? []),
            'error' => ($response['success'] ?? false) ? null : ($response['message'] ?? 'خطا در دریافت تاریخچه تیکت‌ها'),
        ];
    }

    public function find(License $license, int|string $ticketId): array
    {
        $response = $this->request('get', '/api/tenant/tickets/' . (int) $ticketId, $license, []);

        return [
            'ticket' => ($response['success'] ?? false) ? ($response['ticket'] ?? null) : null,
            'error' => ($response['success'] ?? false) ? null : ($response['message'] ?? 'تیکت مورد نظر یافت نشد.'),
        ];
    }

    public function store(License $license, array $payload, array $uploadedFiles = []): array
    {
        $payload['attachments'] = $uploadedFiles;
        $response = $this->request('post', '/api/tenant/tickets', $license, $payload);

        if (!($response['success'] ?? false)) {
            $this->deleteUploadedFiles($uploadedFiles);
        }

        return $response;
    }

    public function reply(License $license, int|string $ticketId, array $payload, array $uploadedFiles = []): array
    {
        $payload['attachments'] = $uploadedFiles;
        $response = $this->request('post', '/api/tenant/tickets/' . (int) $ticketId . '/reply', $license, $payload);

        if (!($response['success'] ?? false)) {
            $this->deleteUploadedFiles($uploadedFiles);
        }

        return $response;
    }

    public function poll(License $license, int|string $ticketId, int $lastId = 0): array
    {
        $params   = $lastId > 0 ? ['last_id' => $lastId] : [];
        $response = $this->request('get', '/api/tenant/tickets/' . (int) $ticketId . '/poll', $license, $params);

        return [
            'replies' => $response['replies'] ?? [],
            'last_id' => (int) ($response['last_id'] ?? $lastId),
        ];
    }

    public function uploadAttachments(array $files): array
    {
        $uploadedFiles = [];
        foreach ($files as $file) {
    // Skip invalid or missing files
    if (!$file instanceof \Illuminate\Http\UploadedFile || !$file->isValid() || $file->getSize() === 0) {
        continue;
    }

    // Verify file actually exists
    if (!$file->getRealPath() || !file_exists($file->getRealPath())) {
        continue;
    }

    $path = $file->store('support-tickets/' . now()->format('Y/m'), 'public');
    if (!$path) {
        continue;
    }

    $uploadedFiles[] = [
        'name' => $file->getClientOriginalName(),
        'path' => $path,
        'url' => asset(Storage::url($path)),
        'size' => $file->getSize(),
        'mime' => $file->getMimeType(),
    ];
}

        return $uploadedFiles;
    }

    public function deleteUploadedFiles(array $uploadedFiles): void
    {
        foreach ($uploadedFiles as $file) {
            if (!empty($file['path'])) {
                Storage::disk('public')->delete($file['path']);
            }
        }
    }

    protected function request(string $method, string $path, License $license, array $payload): array
    {
        $platformUrl = rtrim((string) config('site.platform_url', ''), '/');
        if ($platformUrl === '') {
            return ['success' => false, 'message' => 'PLATFORM_URL تنظیم نشده است.'];
        }

        $basePayload = [
            'license_key' => $license->license_key,
            'domain' => $license->domain,
        ];

        try {
            $client = Http::timeout(15)->acceptJson();
            $response = $method === 'get'
                ? $client->get($platformUrl . $path, array_merge($basePayload, $payload))
                : $client->post($platformUrl . $path, array_merge($basePayload, $payload));

            $data = $response->json();
            if (!is_array($data)) {
                return ['success' => false, 'message' => 'پاسخ پشتیبانی نامعتبر است.'];
            }

            return $data;
        } catch (ConnectionException $exception) {
            Log::warning('support.api.connection_failed', [
                'license_id' => $license->id,
            ]);

            return ['success' => false, 'message' => 'ارتباط با سرور پشتیبانی برقرار نشد.'];
        } catch (\Throwable $exception) {
            Log::error('support.api.failed', [
                'license_id' => $license->id,
            ]);

            return ['success' => false, 'message' => 'خطا در ارتباط با سرور پشتیبانی.'];
        }
    }
}

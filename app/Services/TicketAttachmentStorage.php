<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Stores a support-ticket attachment on the media disk (Phase 4). Replaces
 * five copies of `$file->move(public_path('uploads/...'))`, which wrote past
 * the storage abstraction and so stayed on the server's disk even with
 * MEDIA_DISK=arvan.
 *
 * The array shape is unchanged (name, path, url, size, mime) plus `disk`:
 * with the local uploads disk the path ("uploads/tickets/...") and URL
 * ("/uploads/tickets/...") are byte-identical to before.
 */
class TicketAttachmentStorage
{
    public function __construct(
        private readonly MediaStorage $storage,
        private readonly MediaQuota $quota,
    ) {
    }

    /**
     * @return array{name: string, path: string, url: string, size: int, mime: string, disk: string}
     */
    public function store(UploadedFile $file): array
    {
        $size = (int) $file->getSize();
        $this->quota->assertCanStore($size, 'attachments');

        $extension = $file->guessExtension() ?: ($file->getClientOriginalExtension() ?: 'bin');
        $key = 'tickets/' . now()->format('Y/m') . '/' . Str::random(32) . '.' . strtolower($extension);
        $disk = $this->storage->diskNameFor('uploads');

        $stream = @fopen($file->getRealPath() ?: $file->getPathname(), 'rb');
        if ($stream === false) {
            throw new \RuntimeException('Ticket attachment temp file is not readable.');
        }

        try {
            if (! $this->storage->disk('uploads')->put($key, $stream, ['visibility' => 'public'])) {
                throw new \RuntimeException('Ticket attachment could not be stored.');
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return [
            'name' => $file->getClientOriginalName(),
            'path' => 'uploads/' . $key,
            'url'  => $this->storage->url($key, 'uploads'),
            'size' => $size,
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
            'disk' => $disk,
        ];
    }

    /** Delete attachments stored by store() (and legacy public-disk ones). */
    public function delete(array $attachment): void
    {
        $path = (string) ($attachment['path'] ?? '');
        if ($path === '') {
            return;
        }

        try {
            if (str_starts_with($path, 'uploads/')) {
                $this->storage->delete(substr($path, strlen('uploads/')), 'uploads');
            } else {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
            }
        } catch (\Throwable) {
            // best effort, like the previous Storage::disk('public')->delete()
        }
    }
}

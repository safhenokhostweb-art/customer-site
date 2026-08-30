<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Single point of truth for "which disk does this media live on".
 *
 * Application code must never hardcode 'uploads' / 'public' / 'private' /
 * 'arvan' as a Storage::disk() argument — ask this service for the disk
 * name (or the Filesystem instance) instead. It resolves the actual disk
 * from config/filesystems.php's `upload_disk` / `media_private_disk` keys
 * (env MEDIA_DISK / MEDIA_PRIVATE_DISK — see also the legacy UPLOAD_DISK
 * fallback), so moving media to S3 or Arvan Object Storage later is an
 * environment change, not a code change.
 *
 * Kinds:
 *   'uploads' — SmartImageUploadService output (avatars, product/theme
 *               images, ...), currently the 'uploads' disk
 *   'legacy'  — pre-existing paths stored under the old "storage" convention
 *               (a literal "uploads/" prefix baked into the stored path),
 *               currently the 'public' disk
 *   'private' — support/tenant attachments, never web-accessible
 *
 * In local mode (default) each kind keeps resolving to the exact disk name
 * it always has, so existing stored paths/URLs are unaffected. Once
 * MEDIA_DISK points at an object-storage disk (e.g. "arvan" or "s3"), the
 * 'uploads' and 'legacy' kinds converge on that single remote disk.
 */
class MediaStorage
{
    public function diskNameFor(string $kind = 'uploads'): string
    {
        if ($kind === 'private') {
            return config('filesystems.media_private_disk', 'private');
        }

        $mode = config('filesystems.upload_disk', 'uploads');

        if (in_array($mode, ['uploads', 'public'], true)) {
            return $kind === 'legacy' ? 'public' : 'uploads';
        }

        return $mode;
    }

    /**
     * Resolves the actual write/read handle for a disk. Reserved for the
     * write path (uploads, deletes, directory listing): instantiating a
     * local Flysystem adapter attempts to create its root directory, so
     * callers here are expected to run inside their own try/catch and
     * surface failures — this method must never be used just to build a URL.
     */
    public function disk(string $kind = 'uploads'): Filesystem
    {
        return Storage::disk($this->diskNameFor($kind));
    }

    /**
     * Read-only URL generation. Must never require the local disk root to
     * exist or be creatable — resolving a Flysystem local adapter eagerly
     * runs mkdir on its configured root, so touching Storage::disk() here
     * for a local disk can throw League\Flysystem\UnableToCreateDirectory
     * and turn a missing/unwritable uploads folder into a fatal 500 for a
     * plain page render. For local disks with a configured `url`, build the
     * URL string directly from config instead of resolving the adapter.
     * Any other failure (e.g. a remote driver misbehaving) is logged and
     * degrades to an empty string rather than throwing.
     */
    public function url(string $path, string $kind = 'uploads'): string
    {
        $disk = $this->diskNameFor($kind);

        $localUrl = $this->localUrlFor($disk, $path);
        if ($localUrl !== null) {
            return $localUrl;
        }

        try {
            return Storage::disk($disk)->url($path);
        } catch (\Throwable $e) {
            Log::warning('media_storage.url_resolution_failed', [
                'disk' => $disk,
                'path' => $path,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return '';
        }
    }

    private function localUrlFor(string $disk, string $path): ?string
    {
        $config = config("filesystems.disks.{$disk}", []);

        if (($config['driver'] ?? null) !== 'local' || empty($config['url'])) {
            return null;
        }

        return rtrim($config['url'], '/').'/'.ltrim($path, '/');
    }

    /**
     * Write-adjacent, but the return type is already a bool contract for
     * "did it delete" — honor that on disk-resolution failure too (e.g. a
     * missing root) instead of throwing, so a failed cleanup of a stale
     * file never turns into an unrelated fatal error for the caller.
     */
    public function delete(string $path, string $kind = 'uploads'): bool
    {
        $disk = $this->diskNameFor($kind);

        try {
            return Storage::disk($disk)->delete($path);
        } catch (\Throwable $e) {
            Log::warning('media_storage.delete_failed', [
                'disk' => $disk,
                'path' => $path,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Read path — a missing/unwritable root means the file can't exist from
     * the caller's point of view, so this fails soft to false rather than
     * propagating the disk-resolution exception.
     */
    public function exists(string $path, string $kind = 'uploads'): bool
    {
        $disk = $this->diskNameFor($kind);

        try {
            return Storage::disk($disk)->exists($path);
        } catch (\Throwable $e) {
            Log::warning('media_storage.exists_check_failed', [
                'disk' => $disk,
                'path' => $path,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

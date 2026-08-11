<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
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

    public function disk(string $kind = 'uploads'): Filesystem
    {
        return Storage::disk($this->diskNameFor($kind));
    }

    public function url(string $path, string $kind = 'uploads'): string
    {
        return Storage::disk($this->diskNameFor($kind))->url($path);
    }

    public function delete(string $path, string $kind = 'uploads'): bool
    {
        return Storage::disk($this->diskNameFor($kind))->delete($path);
    }

    public function exists(string $path, string $kind = 'uploads'): bool
    {
        return Storage::disk($this->diskNameFor($kind))->exists($path);
    }
}

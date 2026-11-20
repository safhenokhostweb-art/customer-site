<?php

namespace App\Services;

use App\Models\CustomerMedia;
use App\Support\ResourceSummary;
use Illuminate\Validation\ValidationException;

/**
 * Upload-time disk quota check (Phase 4). The platform measures the site's
 * usage (local du of the web root + bytes of media on object storage) and
 * pushes it with the hard limit into settings.site_resource_summary; media
 * stored since that measurement are added here so a burst of uploads
 * between two daily pushes cannot run far past the limit.
 *
 * No summary, or one the platform could not measure, never blocks an
 * upload: the platform alone decides the limit.
 */
class MediaQuota
{
    private ?ResourceSummary $summary = null;

    /** Check against this summary instead of the stored setting. */
    public function withSummary(?ResourceSummary $summary): self
    {
        $clone = clone $this;
        $clone->summary = $summary;

        return $clone;
    }

    /** @throws ValidationException when $incomingBytes would exceed the hard limit */
    public function assertCanStore(int $incomingBytes, string $field = 'file'): void
    {
        $summary = $this->summary ?? ResourceSummary::fromSettings();
        if ($summary === null || ! $summary->isMeasured()) {
            return;
        }

        $limit = $summary->hardLimitBytes();
        if ($limit <= 0) {
            return;
        }

        $used = $summary->usedBytes() + $this->storedSince($summary);
        if ($used + max(0, $incomingBytes) <= $limit) {
            return;
        }

        throw ValidationException::withMessages([
            $field => sprintf(
                'فضای ذخیره‌سازی سایت پر است: %s از %s مصرف شده و این فایل (%s) جا نمی‌شود. فایل‌های بلااستفاده را حذف کنید یا سهمیه را ارتقا دهید.',
                self::human($used),
                self::human($limit),
                self::human($incomingBytes),
            ),
        ]);
    }

    /** Bytes of media written after the platform's last measurement. */
    private function storedSince(ResourceSummary $summary): int
    {
        $at = $summary->measuredAt();
        if ($at === null) {
            return 0;
        }

        try {
            return (int) CustomerMedia::query()->where('created_at', '>', $at)->sum('size_bytes');
        } catch (\Throwable) {
            return 0;
        }
    }

    public static function human(int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB', 'TB'] as $unit) {
            if ($bytes < 1024 || $unit === 'TB') {
                return round($bytes, 1) . ' ' . $unit;
            }
            $bytes /= 1024;
        }

        return $bytes . ' B';
    }
}

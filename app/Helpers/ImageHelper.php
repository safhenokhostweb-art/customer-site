<?php

namespace App\Helpers;

use App\Services\MediaStorage;

class ImageHelper
{
    /**
     * آدرس کامل تصویر برای نمایش، از طریق MediaStorage (نه asset() خام) —
     * تا وقتی MEDIA_DISK به Object Storage اشاره کند، لینک تصویر همچنان
     * درست تولید شود. در حالت محلی (پیش‌فرض) خروجی دقیقاً همان قبلی است:
     * مسیرهای قدیمی (storage): uploads/... → دیسک 'public'
     * مسیرهای جدید (public/uploads): thumbnail/... → دیسک 'uploads'
     */
    public static function url(?string $path): ?string
    {
        if (!$path || trim($path) === '') {
            return null;
        }
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        $media = app(MediaStorage::class);

        // Admin avatars written before the MediaStorage migration used the
        // public/uploads root but persisted the literal "uploads/" prefix.
        // Keep those records readable while new uploads use canonical paths.
        if (str_starts_with($path, 'uploads/admin-avatars/')) {
            return $media->url(substr($path, strlen('uploads/')), 'uploads');
        }

        if (str_starts_with($path, 'uploads/')) {
            return $media->url($path, 'legacy');
        }

        return $media->url($path, 'uploads');
    }
}

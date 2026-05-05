<?php

namespace App\Helpers;

class ImageHelper
{
    /**
     * آدرس کامل تصویر برای نمایش.
     * مسیرهای قدیمی (storage): uploads/... → asset('storage/...')
     * مسیرهای جدید (public/uploads): thumbnail/... → asset('uploads/...')
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
        if (str_starts_with($path, 'uploads/')) {
            return asset('storage/' . $path);
        }
        return asset('uploads/' . $path);
    }
}

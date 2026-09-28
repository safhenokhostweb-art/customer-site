<?php

namespace App\Services\ThemeCanvas\DataBinding;

use App\Helpers\ImageHelper;

final class ThemeBindingMedia
{
    public function url(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '' || str_contains($path, "\0") || str_contains($path, '\\')) {
            return null;
        }
        if (preg_match('/\Ahttps?:\/\//i', $path)) {
            $parts = parse_url($path);

            return is_array($parts) && isset($parts['host']) ? ImageHelper::url($path) : null;
        }
        if (str_starts_with($path, '/') || in_array('..', explode('/', $path), true)) {
            return null;
        }

        return ImageHelper::url($path);
    }
}

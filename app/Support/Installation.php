<?php

namespace App\Support;

/**
 * نصب کامل: فایل قفل در ریشهٔ storage. مسیر و وجود فایل از یک جا کنترل می‌شود.
 */
final class Installation
{
    public static function lockPath(): string
    {
        return storage_path('installed.lock');
    }

    public static function isLockPresent(): bool
    {
        $path = self::lockPath();
        clearstatcache(true, $path);

        return is_file($path);
    }

    /**
     * نوشتن اتمیک قفل نصب (کاهش ریسک فایل نیمه‌کاره / race کوتاه‌مدت).
     */
    public static function writeLockFile(): bool
    {
        $path = self::lockPath();
        $dir = dirname($path);
        if (! is_dir($dir) || ! is_writable($dir)) {
            return false;
        }

        $payload = (string) now();
        $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (file_put_contents($tmp, $payload) === false) {
            return false;
        }

        if (! @rename($tmp, $path)) {
            @unlink($tmp);

            return false;
        }

        clearstatcache(true, $path);

        return is_file($path);
    }
}

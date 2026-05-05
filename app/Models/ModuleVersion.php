<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * نسخهٔ نصب‌شدهٔ هر ماژول در این سایت.
 * برای گزارش به پلتفرم و بررسی بروزرسانی استفاده می‌شود.
 */
class ModuleVersion extends Model
{
    protected $fillable = [
        'module_slug',
        'version',
        'installed_at',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
    ];

    /** نسخهٔ پیش‌فرض */
    public const DEFAULT_VERSION = '1.0.0';

    /**
     * نسخهٔ ماژول را بر اساس slug برگردان.
     */
    public static function getVersion(string $slug): string
    {
        $row = self::where('module_slug', $slug)->first();
        return $row?->version ?? self::DEFAULT_VERSION;
    }

    /**
     * نسخهٔ ماژول را به‌روز کن (مثلاً پس از بروزرسانی).
     */
    public static function setVersion(string $slug, string $version): void
    {
        self::updateOrCreate(
            ['module_slug' => $slug],
            [
                'version' => $version,
                'installed_at' => now(),
            ]
        );
    }

    /**
     * همهٔ ماژول‌ها و نسخه‌هایشان را به‌صورت آرایه برگردان.
     *
     * @return array<string, string>
     */
    public static function allVersions(): array
    {
        return self::pluck('version', 'module_slug')->all();
    }
}

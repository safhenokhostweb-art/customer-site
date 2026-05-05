<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = ['site_id', 'name', 'location'];

    public const LOCATION_PRIMARY = 'primary';
    public const LOCATION_FOOTER = 'footer';
    public const LOCATION_MOBILE = 'mobile';
    public const LOCATION_TOPBAR = 'topbar';

    public static function locations(): array
    {
        return [
            self::LOCATION_PRIMARY => 'منوی اصلی (هدر)',
            self::LOCATION_FOOTER => 'فوتر',
            self::LOCATION_MOBILE => 'موبایل',
            self::LOCATION_TOPBAR => 'نوار بالا',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->whereNull('parent_id')->orderBy('order');
    }

    public function allItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('order');
    }

    /**
     * Get menu by location (and optional site_id for multi-tenant).
     */
    public static function forLocation(string $location, ?int $siteId = null): ?self
    {
        $q = self::where('location', $location);
        if ($siteId !== null) {
            $q->where('site_id', $siteId);
        } else {
            $q->whereNull('site_id');
        }
        return $q->first();
    }
}

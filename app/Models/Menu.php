<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Menu extends Model
{
    protected $fillable = [
        'site_id', 'name', 'location', 'status', 'go_live_at', 'preview_token',
    ];

    protected function casts(): array
    {
        return [
            'go_live_at' => 'datetime',
        ];
    }

    public const LOCATION_PRIMARY = 'primary';
    public const LOCATION_FOOTER = 'footer';
    public const LOCATION_MOBILE = 'mobile';
    public const LOCATION_TOPBAR = 'topbar';
    public const LOCATION_SECONDARY = 'secondary';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    public static function locations(): array
    {
        return [
            self::LOCATION_PRIMARY => 'منوی اصلی (هدر)',
            self::LOCATION_FOOTER => 'فوتر',
            self::LOCATION_MOBILE => 'موبایل',
            self::LOCATION_SECONDARY => 'منوی ثانویه',
            self::LOCATION_TOPBAR => 'نوار بالا',
        ];
    }

    /**
     * TASK 3/3 Phase 6.5C (BLD-27) — Menu location runtime-connection audit.
     * `locations()` above models all 5, but only 'primary' (nav_header's
     * nav_source=menu, and the pre-existing nav_menu block) and 'footer'
     * (nav_footer's nav_source=menu) are actually wired to ANY renderer or
     * render context — confirmed by exhaustive search: no block, no theme
     * layout, no context key anywhere reads a mobile/secondary/topbar Menu
     * at render time. Selecting one of those 3 in the create form was a
     * real, silent no-op trap ("I made a Mobile Menu and nothing changed
     * on my site"). Per this phase's own convergence rule ("do not retain
     * fake locations that cannot affect runtime"), new Menus can no longer
     * be created against them — this is the create-form's option list,
     * not a schema/migration change: the underlying `location` column and
     * `locations()` label lookup are untouched, so an already-existing row
     * (there are none in any fixture/test — confirmed) keeps rendering its
     * real label instead of a raw key.
     */
    public static function selectableLocations(): array
    {
        return [
            self::LOCATION_PRIMARY => self::locations()[self::LOCATION_PRIMARY],
            self::LOCATION_FOOTER => self::locations()[self::LOCATION_FOOTER],
        ];
    }

    /**
     * نگاشت موقعیت دیتابیس به کلیدهای سازگار با PlatformUx (header, footer, …).
     *
     * @return array<string, string>
     */
    public static function locationToPlatformKey(): array
    {
        return [
            self::LOCATION_PRIMARY => 'header',
            self::LOCATION_FOOTER => 'footer',
            self::LOCATION_MOBILE => 'mobile',
            self::LOCATION_SECONDARY => 'secondary',
            self::LOCATION_TOPBAR => 'topbar',
        ];
    }

    /** @internal Cached per PHP process; reflects DB at first check. */
    public static function menusTableHasStatusColumn(): bool
    {
        static $v = null;
        if ($v === null) {
            $v = Schema::hasColumn('menus', 'status');
        }

        return $v;
    }

    public static function menusTableHasGoLiveAtColumn(): bool
    {
        static $v = null;
        if ($v === null) {
            $v = Schema::hasColumn('menus', 'go_live_at');
        }

        return $v;
    }

    public static function menusTableHasPreviewTokenColumn(): bool
    {
        static $v = null;
        if ($v === null) {
            $v = Schema::hasColumn('menus', 'preview_token');
        }

        return $v;
    }

    /**
     * Strip keys that are not present on `menus` yet (pre-migration deploys).
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function stripUnsupportedMenuTableAttributes(array $attributes): array
    {
        $out = $attributes;
        if (! self::menusTableHasStatusColumn()) {
            unset($out['status']);
        }
        if (! self::menusTableHasGoLiveAtColumn()) {
            unset($out['go_live_at']);
        }
        if (! self::menusTableHasPreviewTokenColumn()) {
            unset($out['preview_token']);
        }

        return $out;
    }

    public function scopeVisibleOnFront(Builder $query): Builder
    {
        if (! self::menusTableHasStatusColumn()) {
            return $query;
        }

        $query->where('status', self::STATUS_PUBLISHED);
        if (self::menusTableHasGoLiveAtColumn()) {
            $query->where(function (Builder $q) {
                $q->whereNull('go_live_at')->orWhere('go_live_at', '<=', now());
            });
        }

        return $query;
    }

    public function isVisibleOnFront(): bool
    {
        if (! self::menusTableHasStatusColumn()) {
            return true;
        }
        if ($this->status !== self::STATUS_PUBLISHED) {
            return false;
        }
        if (self::menusTableHasGoLiveAtColumn()) {
            $goLive = $this->go_live_at;
            if ($goLive && $goLive->isFuture()) {
                return false;
            }
        }

        return true;
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(MenuRevision::class)->orderByDesc('id');
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(MenuChangeLog::class)->orderByDesc('id');
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

    /**
     * منوی منتشرشدهٔ قابل نمایش در سایت برای یک موقعیت.
     */
    public static function forPublishedLocation(string $location, ?int $siteId = null): ?self
    {
        $q = self::query()->visibleOnFront()->where('location', $location);
        if ($siteId !== null) {
            $q->where('site_id', $siteId);
        } else {
            $q->whereNull('site_id');
        }

        return $q->first();
    }

    public function regeneratePreviewToken(): string
    {
        if (! self::menusTableHasPreviewTokenColumn()) {
            return '';
        }
        $this->preview_token = bin2hex(random_bytes(16));
        $this->save();

        return (string) $this->preview_token;
    }
}

<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves site menu by location and builds tree for rendering.
 * Caches menu output per location for performance.
 */
class MenuService
{
    public const CACHE_KEY_PREFIX = 'site_menu_';
    public const CACHE_TTL = 3600;

    /**
     * Get menu for location (primary, footer, mobile, topbar). Returns null if none.
     */
    public function getMenuForLocation(string $location, ?int $siteId = null): ?Menu
    {
        return Menu::forLocation($location, $siteId);
    }

    /**
     * Build tree of items for a menu (title, url, target, children). For use in views.
     */
    public function buildMenuTree(Menu $menu): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $menu->id;
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($menu) {
            $items = $menu->allItems()->get();
            $byParent = $items->groupBy('parent_id');
            $root = $byParent->get(null, collect());
            return $root->map(fn (MenuItem $item) => $this->itemToArray($item, $byParent))->values()->all();
        });
    }

    protected function itemToArray(MenuItem $item, $byParent): array
    {
        $children = $byParent->get($item->id, collect())
            ->map(fn (MenuItem $c) => $this->itemToArray($c, $byParent))
            ->values()
            ->all();
        return [
            'id' => $item->id,
            'title' => $item->title,
            'url' => $item->resolveUrl(),
            'target' => $item->open_in_new_tab ? '_blank' : '_self',
            'icon' => $item->icon,
            'css_class' => $item->css_class,
            'children' => $children,
        ];
    }

    public function clearMenuCache(?int $menuId = null): void
    {
        if ($menuId !== null) {
            Cache::forget(self::CACHE_KEY_PREFIX . $menuId);
        } else {
            Menu::all()->each(fn (Menu $m) => Cache::forget(self::CACHE_KEY_PREFIX . $m->id));
        }
    }
}

<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Resolves site menu by location and builds tree for rendering.
 * Caches menu output per menu id for performance.
 */
class MenuService
{
    public const CACHE_KEY_PREFIX = 'site_menu_';

    public const CACHE_TTL = 3600;

    public function __construct(
        protected PlatformUxService $platformUx
    ) {}

    /**
     * @param  array{respect_menu_visibility?: bool, filter_modules?: bool}  $options
     */
    public function buildMenuTree(Menu $menu, array $options = []): array
    {
        $options = array_merge([
            'respect_menu_visibility' => true,
            'filter_modules' => true,
        ], $options);

        if ($options['respect_menu_visibility'] && ! $menu->isVisibleOnFront()) {
            return [];
        }

        $build = function () use ($menu, $options) {
            $items = $menu->allItems()->get();
            $byParent = $items->groupBy('parent_id');
            $root = $byParent->get(null, collect());

            return $root
                ->map(fn (MenuItem $item) => $this->itemToArray($item, $byParent, $options['filter_modules']))
                ->filter(fn ($row) => $row !== [])
                ->values()
                ->all();
        };

        $useCache = $options['respect_menu_visibility'] && $options['filter_modules'];
        if ($useCache) {
            return Cache::remember(self::CACHE_KEY_PREFIX.$menu->id, self::CACHE_TTL, $build);
        }

        return $build();
    }

    /**
     * بدون کش — برای پیش‌نمایش ادمین.
     *
     * @param  array{filter_modules?: bool}  $options
     */
    public function buildMenuTreeUncached(Menu $menu, array $options = []): array
    {
        $options = array_merge(['filter_modules' => false], $options);
        $items = $menu->allItems()->get();
        $byParent = $items->groupBy('parent_id');
        $root = $byParent->get(null, collect());

        return $root
            ->map(fn (MenuItem $item) => $this->itemToArray($item, $byParent, $options['filter_modules']))
            ->filter(fn ($row) => $row !== [])
            ->values()
            ->all();
    }

    protected function itemToArray(MenuItem $item, $byParent, bool $filterModules): array
    {
        if ($filterModules && ! $item->passesModuleVisibility()) {
            return [];
        }

        $title = $item->resolvedTitle();
        $baseUrl = $item->resolveUrl();
        $url = $item->urlWithUtm($baseUrl);

        $children = $byParent->get($item->id, collect())
            ->map(fn (MenuItem $c) => $this->itemToArray($c, $byParent, $filterModules))
            ->filter(fn ($c) => $c !== [])
            ->values()
            ->all();

        $children = array_merge($children, $this->syntheticAutoListChildren($item, $filterModules));

        $megaGroups = $this->groupMegaColumns($children);

        return [
            'id' => $item->id,
            'title' => $title,
            'url' => $url,
            'target' => $item->open_in_new_tab ? '_blank' : '_self',
            'rel' => $item->relAttributeValue(),
            'icon' => $item->icon,
            'css_class' => $item->css_class,
            'badge' => $item->badge_label,
            'image_url' => $item->image_url,
            'mega_column' => $item->mega_column,
            'featured_html' => $item->featured_html,
            'mega_columns' => $megaGroups,
            'type' => $item->type,
            'sort_order' => (int) $item->order,
            'children' => $children,
            'utm_query' => $item->utm_query,
            'utm_json' => $item->utm_json,
            'labels' => $item->labels,
            'title_en' => $item->title_en,
        ];
    }

    /**
     * @return list<array{column:int, items: list<mixed>}>
     */
    protected function groupMegaColumns(array $children): array
    {
        if ($children === []) {
            return [];
        }
        $grouped = [];
        foreach ($children as $c) {
            $col = (int) ($c['mega_column'] ?? 0);
            if (! isset($grouped[$col])) {
                $grouped[$col] = [];
            }
            $grouped[$col][] = $c;
        }
        ksort($grouped);
        $out = [];
        foreach ($grouped as $col => $items) {
            $out[] = ['column' => (int) $col, 'items' => $items];
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function syntheticAutoListChildren(MenuItem $item, bool $filterModules): array
    {
        if (! $item->auto_list_children) {
            return [];
        }
        if ($item->type === MenuItem::TYPE_CATEGORY && $item->reference_id && \App\Helpers\SiteHelper::shopEnabled()) {
            $cat = ProductCategory::find($item->reference_id);
            if (! $cat) {
                return [];
            }
            $out = [];
            foreach ($cat->children()->orderBy('order')->get() as $child) {
                $row = [
                    'id' => 'auto-shop-cat-'.$child->id,
                    'title' => $child->name,
                    'url' => route('shop.index', ['category' => $child->slug ?? $child->id]),
                    'target' => '_self',
                    'rel' => '',
                    'icon' => null,
                    'css_class' => 'menu-synthetic-child',
                    'badge' => null,
                    'image_url' => null,
                    'mega_column' => null,
                    'featured_html' => null,
                    'mega_columns' => [],
                    'type' => 'synthetic_shop_category',
                    'sort_order' => (int) $child->order,
                    'children' => [],
                    'synthetic' => true,
                ];
                if (! $filterModules || $item->passesModuleVisibility()) {
                    $out[] = $row;
                }
            }

            return $out;
        }

        return [];
    }

    /**
     * منوی منتشرشده برای یک موقعیت مکانی.
     */
    public function getMenuForLocation(string $location, ?int $siteId = null): ?Menu
    {
        return Menu::forPublishedLocation($location, $siteId);
    }

    /**
     * همان منطق قدیمی — اولین منوی موقعیت (شامل پیش‌نویس) برای ادمین.
     */
    public function getMenuForLocationAdmin(string $location, ?int $siteId = null): ?Menu
    {
        return Menu::forLocation($location, $siteId);
    }

    /**
     * TASK 3/3 Phase 6.5B — the single canonical place that turns a Menu
     * location into real, rendered nav HTML (partials.site-menu, falling
     * back to partials.platform-menu when no customer Menu exists for that
     * location yet). Previously this logic was duplicated inline only in
     * ThemeCanvasPageController (the public render), which is why every
     * OTHER render path (Theme Canvas / Page Builder admin preview) fell
     * back to an empty string instead of real menu content — a real,
     * previously-undiscovered preview gap, not by design. Both the real
     * public render and every admin preview now call this one method.
     *
     * $admin=true resolves the location's current menu INCLUDING an unpublished
     * draft (Menu::forLocation, matching draft-first Theme Canvas preview
     * semantics — a preview should show what you're about to publish, not
     * what's already live); $admin=false (public render) only ever resolves
     * an actually-published menu.
     */
    public function renderMenuHtml(string $location = 'primary', bool $admin = false, ?int $siteId = null): string
    {
        $menu = $admin ? $this->getMenuForLocationAdmin($location, $siteId) : $this->getMenuForLocation($location, $siteId);

        if ($menu) {
            return view('partials.site-menu', [
                'menu'    => $menu,
                'variant' => 'nav',
                'items'   => $this->buildMenuTree($menu),
            ])->render();
        }

        return view('partials.platform-menu', [
            'location' => 'header',
            'variant'  => 'nav',
        ])->render();
    }

    public function clearMenuCache(?int $menuId = null): void
    {
        if ($menuId !== null) {
            Cache::forget(self::CACHE_KEY_PREFIX.$menuId);
        } else {
            Menu::query()->pluck('id')->each(fn ($id) => Cache::forget(self::CACHE_KEY_PREFIX.$id));
        }
        $this->platformUx->clearCache();
    }

    /**
     * خروجی سازگار با PlatformUx (header, footer, mobile, secondary, topbar).
     *
     * @return array<string, array{items: list<array<string, mixed>>}>
     */
    public function exportPublishedMenusForPlatformShape(): array
    {
        $keys = ['header', 'footer', 'mobile', 'secondary', 'topbar'];
        $base = [];
        foreach ($keys as $k) {
            $base[$k] = ['items' => []];
        }
        $map = Menu::locationToPlatformKey();
        foreach ($map as $dbLoc => $uxKey) {
            $menu = Menu::forPublishedLocation($dbLoc);
            if (! $menu) {
                continue;
            }
            $base[$uxKey]['items'] = $this->buildMenuTree($menu, [
                'respect_menu_visibility' => true,
                'filter_modules' => true,
            ]);
        }

        return $base;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function snapshotItemsFlat(Menu $menu): array
    {
        return $menu->allItems()->orderBy('parent_id')->orderBy('order')->get()->map(function (MenuItem $item) {
            return [
                'id' => $item->id,
                'parent_id' => $item->parent_id,
                'title' => $item->title,
                'title_en' => $item->title_en,
                'labels' => $item->labels,
                'type' => $item->type,
                'reference_id' => $item->reference_id,
                'url' => $item->url,
                'utm_query' => $item->utm_query,
                'utm_json' => $item->utm_json,
                'icon' => $item->icon,
                'order' => $item->order,
                'open_in_new_tab' => $item->open_in_new_tab,
                'rel_nofollow' => $item->rel_nofollow,
                'css_class' => $item->css_class,
                'badge_label' => $item->badge_label,
                'image_url' => $item->image_url,
                'mega_column' => $item->mega_column,
                'featured_html' => $item->featured_html,
                'auto_list_children' => $item->auto_list_children,
                'visible_modules' => $item->visible_modules,
            ];
        })->values()->all();
    }

    public function storeRevision(Menu $menu, ?int $userId, ?string $note = null): \App\Models\MenuRevision
    {
        $menuKeys = ['id', 'name', 'location'];
        if (Menu::menusTableHasStatusColumn()) {
            $menuKeys[] = 'status';
        }
        if (Menu::menusTableHasGoLiveAtColumn()) {
            $menuKeys[] = 'go_live_at';
        }
        $payload = [
            'menu' => $menu->only($menuKeys),
            'items' => $this->snapshotItemsFlat($menu),
        ];

        return \App\Models\MenuRevision::create([
            'menu_id' => $menu->id,
            'user_id' => $userId,
            'snapshot_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'note' => $note,
        ]);
    }

    /**
     * بازگردانی کامل آیتم‌ها از یک نسخه.
     */
    public function restoreFromRevision(\App\Models\MenuRevision $revision): void
    {
        $menu = $revision->menu;
        $data = $revision->decodeSnapshot();
        $items = $data['items'] ?? [];
        if (! is_array($items)) {
            return;
        }

        DB::transaction(function () use ($menu, $items, $data) {
            $menu->allItems()->delete();
            $idMap = [];
            foreach ($items as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $oldId = (int) ($row['id'] ?? 0);
                $new = MenuItem::create([
                    'menu_id' => $menu->id,
                    'parent_id' => null,
                    'title' => (string) ($row['title'] ?? ''),
                    'title_en' => $row['title_en'] ?? null,
                    'labels' => $row['labels'] ?? null,
                    'type' => (string) ($row['type'] ?? MenuItem::TYPE_CUSTOM_URL),
                    'reference_id' => $row['reference_id'] ?? null,
                    'url' => $row['url'] ?? null,
                    'utm_query' => $row['utm_query'] ?? null,
                    'utm_json' => $row['utm_json'] ?? null,
                    'icon' => $row['icon'] ?? null,
                    'order' => (int) ($row['order'] ?? 0),
                    'open_in_new_tab' => (bool) ($row['open_in_new_tab'] ?? false),
                    'rel_nofollow' => (bool) ($row['rel_nofollow'] ?? false),
                    'css_class' => $row['css_class'] ?? null,
                    'badge_label' => $row['badge_label'] ?? null,
                    'image_url' => $row['image_url'] ?? null,
                    'mega_column' => $row['mega_column'] ?? null,
                    'featured_html' => $row['featured_html'] ?? null,
                    'auto_list_children' => (bool) ($row['auto_list_children'] ?? false),
                    'visible_modules' => $row['visible_modules'] ?? null,
                ]);
                if ($oldId) {
                    $idMap[$oldId] = $new->id;
                }
            }
            foreach ($items as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $oldId = (int) ($row['id'] ?? 0);
                $oldParent = $row['parent_id'] ?? null;
                if (! $oldId || ! isset($idMap[$oldId])) {
                    continue;
                }
                $newId = $idMap[$oldId];
                $newParent = $oldParent && isset($idMap[(int) $oldParent]) ? $idMap[(int) $oldParent] : null;
                MenuItem::whereKey($newId)->update(['parent_id' => $newParent]);
            }
            if (isset($data['menu']) && is_array($data['menu'])) {
                $m = $data['menu'];
                if (Menu::menusTableHasStatusColumn() && isset($m['status'])) {
                    $menu->status = (string) $m['status'];
                }
                if (Menu::menusTableHasGoLiveAtColumn() && array_key_exists('go_live_at', $m)) {
                    $menu->go_live_at = $m['go_live_at'] ? \Carbon\Carbon::parse($m['go_live_at']) : null;
                }
                $menu->save();
            }
        });

        $this->clearMenuCache($menu->id);
    }

    public function duplicateBranch(MenuItem $root, ?int $targetParentId): MenuItem
    {
        $copy = $root->replicate();
        $copy->parent_id = $targetParentId;
        $maxOrder = (int) MenuItem::where('menu_id', $root->menu_id)
            ->where('parent_id', $targetParentId)
            ->max('order');
        $copy->order = $maxOrder + 1;
        $copy->save();
        foreach ($root->children()->orderBy('order')->get() as $child) {
            $this->duplicateBranch($child, $copy->id);
        }

        return $copy;
    }
}

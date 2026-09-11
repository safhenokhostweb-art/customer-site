<?php

namespace App\Support;

use App\Models\Page;
use App\Models\Setting;

/**
 * The one canonical Page-namespace policy between the two Page-authoring
 * surfaces: Page Builder / the Admin Pages CRUD (Page-model rows) and Theme
 * Design Canvas (an active canvas theme's own embedded `pages[]`).
 * ThemeCanvasPageController::show()/index() always resolve a canvas-owned
 * page before ever considering a Page row of the same slug — so the two
 * namespaces must never intersect, in either direction:
 *
 *   - Page-authoring surfaces call all() before creating/renaming a Page,
 *     to reject a slug the active canvas theme already owns.
 *   - ThemeInstanceRevisionService::record() — the single write path for
 *     anything that can become the live canvas theme (publish, install,
 *     import, rollback, upgrade-reconcile) — calls
 *     collidingWithExistingPages() before any of those operations can make
 *     a new canvas page slug live, to reject one that an existing Page row
 *     already owns.
 *
 * Both directions read/inspect the same data; this class does not
 * introduce a second source of truth, only a single shared policy over
 * the one that already exists.
 */
final class CanvasReservedSlugs
{
    /** Slugs the currently-active canvas theme owns. @return array<int, string> */
    public static function all(): array
    {
        $raw = Setting::get('active_canvas_theme_data');
        if (empty($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? self::fromThemeData($decoded) : [];
    }

    /**
     * Slugs an arbitrary (not-yet-active) theme_data array would own —
     * used to inspect a draft/import/install payload before it goes live,
     * not just the currently-active one.
     *
     * Mirrors ThemeCanvasPageController::findPage()'s own key/slug
     * resolution exactly (an explicit 'slug' field, else the array key
     * when it's a string) so this reserves precisely what that controller
     * would actually resolve, never more or less.
     *
     * @return array<int, string>
     */
    public static function fromThemeData(array $themeData): array
    {
        $pages = $themeData['pages'] ?? [];
        if (!is_array($pages)) {
            return [];
        }

        $slugs = [];
        foreach ($pages as $key => $canvasPage) {
            if (!is_array($canvasPage)) {
                continue;
            }
            $slug = $canvasPage['slug'] ?? (is_string($key) ? $key : '');
            if ($slug !== '') {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }

    /**
     * Which of $themeData's page slugs are already owned by an existing
     * Page row — i.e. which pages this theme_data would silently shadow if
     * it became active. Empty means safe to activate.
     *
     * @return array<int, string>
     */
    public static function collidingWithExistingPages(array $themeData): array
    {
        $slugs = self::fromThemeData($themeData);
        if ($slugs === []) {
            return [];
        }

        return Page::whereIn('slug', $slugs)->pluck('slug')->all();
    }
}

<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Support\Str;

/**
 * Provisions theme pages into the site's pages table when a theme is assigned.
 * Pages created here are marked as is_protected=true so they cannot be accidentally deleted.
 */
class ThemePageProvisionService
{
    /**
     * Provision pages from a theme into the local pages table.
     * Skips pages that already exist for this theme+key combination.
     *
     * @param  string $themeId    Theme slug/identifier from saas-platform
     * @param  string $themeName  Human-readable theme name (for audit)
     * @param  array  $themePages Map of page_key => {title, slug, blocks}
     * @return array{created: int, skipped: int, pages: array}
     */
    public function provision(string $themeId, string $themeName, array $themePages): array
    {
        $created = 0;
        $skipped = 0;
        $provisionedPages = [];

        foreach ($themePages as $pageKey => $pageDef) {
            $title = $pageDef['title'] ?? $pageKey;
            $slug  = $pageDef['slug'] ?? Str::slug($title) ?: $pageKey;
            $blocks = $pageDef['blocks'] ?? [];

            // Skip if this theme page is already provisioned
            $existing = Page::where('theme_id', $themeId)
                ->where('theme_page_key', $pageKey)
                ->first();

            if ($existing) {
                $skipped++;
                $provisionedPages[] = [
                    'id'     => $existing->id,
                    'key'    => $pageKey,
                    'title'  => $existing->title,
                    'slug'   => $existing->slug,
                    'status' => 'skipped',
                ];
                continue;
            }

            // Ensure slug is unique
            $baseSlug = $slug;
            $i = 1;
            while (Page::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $i++;
            }

            $page = Page::create([
                'title'              => $title,
                'slug'               => $slug,
                'is_published'       => true,
                'show_in_menu'       => ($pageKey === 'home' || ($pageDef['show_in_menu'] ?? false)),
                'is_protected'       => true,
                'theme_id'           => $themeId,
                'theme_page_key'     => $pageKey,
                'builder_type'       => Page::BUILDER_TYPE_BUILDER,
                'builder_content_v2' => ['blocks' => $blocks],
                'order'              => Page::max('order') + 1,
            ]);

            $created++;
            $provisionedPages[] = [
                'id'     => $page->id,
                'key'    => $pageKey,
                'title'  => $page->title,
                'slug'   => $page->slug,
                'status' => 'created',
            ];
        }

        return ['created' => $created, 'skipped' => $skipped, 'pages' => $provisionedPages];
    }

    /**
     * Remove theme protection from all pages of a given theme (used when theme is unassigned).
     */
    public function unprotect(string $themeId): int
    {
        return Page::where('theme_id', $themeId)
            ->update(['is_protected' => false, 'theme_id' => null, 'theme_page_key' => null]);
    }
}

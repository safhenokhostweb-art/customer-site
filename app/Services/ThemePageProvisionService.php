<?php

namespace App\Services;

use App\Models\Page;

/**
 * SP-02 (TASK 2-SEC): provision() — REMOVED. It inserted `pages` rows
 * directly via a bare Page::create() with its own hand-rolled
 * slug-uniqueness loop, bypassing ThemeInstanceRevisionService/
 * CanvasReservedSlugs/revision history — a second, independent Theme
 * page-provision authority alongside PageBuilderController::installTheme()
 * (the canonical /builder/api/theme/install endpoint). It was reachable
 * only from PageBuilderController::provisionThemePages() (also removed),
 * itself reachable only from saas-platform's ThemePurchaseOrderObserver,
 * which now calls installTheme() via the same ThemeInstallGateway manual
 * installs already use. THEME_INSTALL_MUTATION_AUTHORITIES = 1.
 *
 * unprotect() is a distinct, still-used concern (removing theme protection
 * from pages when a theme is unassigned, not page creation) and is kept.
 */
class ThemePageProvisionService
{
    /**
     * Remove theme protection from all pages of a given theme (used when theme is unassigned).
     */
    public function unprotect(string $themeId): int
    {
        return Page::where('theme_id', $themeId)
            ->update(['is_protected' => false, 'theme_id' => null, 'theme_page_key' => null]);
    }
}

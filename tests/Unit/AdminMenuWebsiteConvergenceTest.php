<?php

namespace Tests\Unit;

use App\Helpers\AdminMenu;
use Tests\TestCase;

/**
 * TASK 3/3 Phase 6.5B (BLD-23) — Website-management IA convergence.
 *
 * Prior to this phase, "پیجز" (Pages/Media/Menu) and "ظاهر" (Theme
 * Design/Marketplace/Bundles) were two separate top-level sidebar groups —
 * a customer had to already know these were related to find them both.
 * This is a permanent regression guard for the consolidation: one "وبسایت"
 * (Website) group now owns every website-management entry point, and the
 * old "ظاهر" group no longer exists as a separate top-level concept.
 *
 * No route, controller, or authorization changed — this is purely a
 * navigation-grouping fact, verified against the real AdminMenu::getGroups()
 * output real admin-facing code reads.
 */
class AdminMenuWebsiteConvergenceTest extends TestCase
{
    public function test_website_group_owns_every_website_management_entry_point(): void
    {
        $groups = AdminMenu::getGroups();

        self::assertArrayHasKey('وبسایت', $groups);
        foreach (['pages', 'media', 'site_content', 'menu', 'theme_design', 'theme_marketplace', 'theme_bundles'] as $key) {
            self::assertContains($key, $groups['وبسایت'], "expected \"{$key}\" under the converged وبسایت group");
        }
    }

    public function test_the_old_separate_appearance_group_no_longer_exists(): void
    {
        self::assertArrayNotHasKey('ظاهر', AdminMenu::getGroups(), 'theme_design/marketplace/bundles must not live under a second, separate top-level group any more');
    }

    public function test_theme_related_keys_appear_in_exactly_one_group(): void
    {
        $groups = AdminMenu::getGroups();
        foreach (['theme_design', 'theme_marketplace', 'theme_bundles'] as $key) {
            $owningGroups = array_keys(array_filter($groups, fn (array $keys) => in_array($key, $keys, true)));
            self::assertCount(1, $owningGroups, "\"{$key}\" must appear in exactly one sidebar group, found in: " . implode(', ', $owningGroups));
            self::assertSame('وبسایت', $owningGroups[0]);
        }
    }
}

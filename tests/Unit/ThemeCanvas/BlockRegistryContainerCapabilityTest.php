<?php

namespace Tests\Unit\ThemeCanvas;

use App\Services\ThemeCanvas\BlockRegistry;
use Tests\TestCase;

/**
 * Phase 3F-B — proves BlockRegistry::isContainerType() (customer-site's
 * Canvas domain) delegates to the canonical shared authority correctly and
 * regardless of local schema shadowing (section/grid/columns are locally
 * redefined here with a simpler schema than the canonical one, and must
 * still report container capability correctly).
 */
class BlockRegistryContainerCapabilityTest extends TestCase
{
    public function test_locally_shadowed_canonical_container_types_are_still_true(): void
    {
        // These 3 have their own local definitions in this class (simpler
        // Canvas-specific schema) yet must still report as containers.
        foreach (['section', 'grid', 'columns'] as $type) {
            $this->assertTrue(BlockRegistry::isContainerType($type), "{$type} must be a container despite local schema shadowing");
        }
    }

    public function test_canonical_only_container_types_inherited_via_merge_are_true(): void
    {
        // These 2 have no local definition here at all — reached purely via
        // the canonical registry's merge-in (BlockRegistry::all()'s own
        // `+ CanonicalBlockRegistry::all()`).
        foreach (['container', 'flex_container'] as $type) {
            $this->assertTrue(BlockRegistry::isContainerType($type), "{$type} must be a container via canonical inheritance");
        }
    }

    public function test_representative_non_container_types_are_false(): void
    {
        foreach (['hero', 'accordion', 'slider'] as $type) {
            $this->assertFalse(BlockRegistry::isContainerType($type), "{$type} must not be a container");
        }
    }

    public function test_types_absent_from_this_domain_entirely_are_false(): void
    {
        // row/column exist in the Production Builder's own ElementRegistry
        // (host-only types there) but have no palette entry in this Canvas
        // domain at all — must not be reported as containers here.
        foreach (['row', 'column', 'not_a_real_type'] as $type) {
            $this->assertFalse(BlockRegistry::isContainerType($type), "{$type} must not be a container in the Canvas domain");
        }
    }
}

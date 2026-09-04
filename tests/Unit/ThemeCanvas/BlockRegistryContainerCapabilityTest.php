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
 *
 * Phase 3F-C extended isContainerType() with the host/extension layer
 * (App\Builder\ElementRegistry) already anticipated by ADR-043 — see the
 * updated test below for row/column, which now correctly report true
 * through that layer despite still having no Canvas palette entry.
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

    public function test_wholly_unregistered_type_is_false(): void
    {
        $this->assertFalse(BlockRegistry::isContainerType('not_a_real_type'), 'an unregistered type must never be a container');
    }

    /**
     * Phase 3F-C: row/column exist in the Production Builder's own
     * ElementRegistry (host-only types there, declaring
     * HasContainerCapability since 3E-D) — isContainerType() now correctly
     * reports true for them via the host/extension layer, the same layer
     * an ext:* container type is recognized through. This is purely a
     * defensive-classification correctness fix: row/column still have NO
     * Canvas palette entry (see the companion test below), so no real
     * Canvas Theme content can ever actually contain a 'row'/'column'
     * block — this assertion cannot change any currently-authorable
     * behavior, only how a hypothetical/foreign such type would classify.
     */
    public function test_host_only_types_report_true_via_extension_layer_despite_no_palette_entry(): void
    {
        foreach (['row', 'column'] as $type) {
            $this->assertTrue(BlockRegistry::isContainerType($type), "{$type} must be a container via the host/extension layer");
            $this->assertNull(BlockRegistry::get($type), "{$type} must still have no Canvas palette entry");
        }
    }
}

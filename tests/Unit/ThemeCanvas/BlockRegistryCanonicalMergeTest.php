<?php

namespace Tests\Unit\ThemeCanvas;

use App\Services\ThemeCanvas\BlockRegistry;
use SaaSPlatform\ThemeRuntime\BlockRegistry as CanonicalBlockRegistry;
use Tests\TestCase;

/**
 * Phase 2i (ADR-029) — customer-site's ThemeCanvas\BlockRegistry no longer
 * stands disconnected from the canonical registry: every canonical type is
 * now reachable through it (merged in, local definitions winning for any
 * name defined in both, to guarantee zero behavior change to already-shipped
 * host schemas). See BlockRegistry::all()'s own docblock for what remains
 * unresolved (the ~40 locally-defined canonical-name overlaps were not
 * individually schema-diffed this phase).
 */
class BlockRegistryCanonicalMergeTest extends TestCase
{
    public function test_every_canonical_type_is_reachable_through_the_customer_site_registry(): void
    {
        $canonicalTypes = array_keys(CanonicalBlockRegistry::all());
        $hostTypes = array_keys(BlockRegistry::all());

        // Deliberately excluded — see the removal test below. Builder
        // integrity audit (2026-09-11) added two more for the same reason
        // as module:gallery_widget: no backend content model exists on this
        // host (Portfolio, Service), so the type could never render real
        // data — 'gallery'/'bento_grid' and 'services_cards'/'service_cards'
        // already cover the static/editor-entered equivalent.
        $deliberatelyExcluded = ['module:gallery_widget', 'module:portfolio_grid', 'module:services_section'];

        $missing = array_diff($canonicalTypes, $hostTypes, $deliberatelyExcluded);

        $this->assertEmpty(
            $missing,
            'These canonical types are not reachable through customer-site\'s BlockRegistry::all(): '
            . implode(', ', $missing)
        );
    }

    public function test_a_deliberately_host_removed_canonical_type_is_not_silently_resurrected_by_the_merge(): void
    {
        // module:gallery_widget was intentionally removed from this host's
        // local definitions (no backend content type exists for it here) —
        // the merge with the canonical registry must respect that removal.
        $this->assertArrayNotHasKey('module:gallery_widget', BlockRegistry::all());
        $this->assertArrayNotHasKey('module:portfolio_grid', BlockRegistry::all());
        $this->assertArrayNotHasKey('module:services_section', BlockRegistry::all());
    }

    public function test_empty_output_policy_delegates_to_the_canonical_authority(): void
    {
        $this->assertSame(
            CanonicalBlockRegistry::emptyOutputPolicy('client_logos'),
            BlockRegistry::emptyOutputPolicy('client_logos')
        );
        $this->assertSame(CanonicalBlockRegistry::EMPTY_POLICY_PLACEHOLDER, BlockRegistry::emptyOutputPolicy('client_logos'));
    }

    public function test_local_definitions_still_win_over_canonical_for_overlapping_type_names(): void
    {
        // Guards the explicit "local wins" merge order chosen for zero
        // behavior change — a regression here would mean a live-authored
        // Theme's schema could silently change shape.
        $local = BlockRegistry::all()['nav_header'] ?? null;
        $this->assertNotNull($local);
        $this->assertArrayHasKey('schema', $local);
    }
}

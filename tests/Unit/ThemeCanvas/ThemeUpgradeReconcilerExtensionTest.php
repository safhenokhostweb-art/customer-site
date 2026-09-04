<?php

namespace Tests\Unit\ThemeCanvas;

use App\Services\ThemeCanvas\ThemeUpgradeReconciler;
use PHPUnit\Framework\TestCase;

/**
 * Phase 3F-C — proves ThemeUpgradeReconciler (Canvas's existing, unmodified
 * BASE/LOCAL/REMOTE three-way reconciliation authority, 3D-D) stays
 * completely generic/type-blind with ext:* content, including a nested
 * ext:* child under a container — no extension-type-specific branching
 * exists or is needed here, since reconciliation operates on serialized
 * block arrays (id/type/props/children) alone, exactly as it already does
 * for every built-in type. Not a new reconciliation capability — a proof
 * that the existing one already covers this case, unmodified.
 */
class ThemeUpgradeReconcilerExtensionTest extends TestCase
{
    private function reconciler(): ThemeUpgradeReconciler
    {
        return new ThemeUpgradeReconciler();
    }

    private function snapshot(array $overrides = []): array
    {
        return array_merge([
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => [],
            'global_styles' => [],
            'settings' => [],
        ], $overrides);
    }

    private function page(array $blocks, array $extra = []): array
    {
        return array_merge(['blocks' => $blocks], $extra);
    }

    private function pageBlocks(array $reconciled, string $slug = 'home'): array
    {
        return $reconciled['pages'][$slug]['blocks'] ?? [];
    }

    public function test_ext_block_unchanged_across_all_three_survives(): void
    {
        $extBlock = ['id' => 'e1', 'type' => 'ext:acme:demo_card', 'props' => ['title' => 'A']];
        $snap = $this->snapshot(['pages' => ['home' => $this->page([$extBlock])]]);

        $result = $this->reconciler()->reconcile($snap, $snap, $snap);

        $this->assertSame([$extBlock], $this->pageBlocks($result->reconciledTheme));
        $this->assertSame([], $result->conflicts);
    }

    public function test_local_ext_prop_edit_is_kept(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([
            ['id' => 'e1', 'type' => 'ext:acme:demo_card', 'props' => ['title' => 'A']],
        ])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([
            ['id' => 'e1', 'type' => 'ext:acme:demo_card', 'props' => ['title' => 'Customer edited']],
        ])]]);
        $remote = $base;

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame('Customer edited', $this->pageBlocks($result->reconciledTheme)[0]['props']['title']);
        $this->assertSame([], $result->conflicts);
    }

    public function test_remote_built_in_neighbor_change_does_not_disturb_the_ext_block(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([
            ['id' => 'h1', 'type' => 'heading', 'props' => ['text' => 'Old']],
            ['id' => 'e1', 'type' => 'ext:acme:demo_card', 'props' => ['title' => 'A']],
        ])]]);
        $local = $base;
        $remote = $this->snapshot(['pages' => ['home' => $this->page([
            ['id' => 'h1', 'type' => 'heading', 'props' => ['text' => 'New from upstream release']],
            ['id' => 'e1', 'type' => 'ext:acme:demo_card', 'props' => ['title' => 'A']],
        ])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);
        $blocks = $this->pageBlocks($result->reconciledTheme);

        $this->assertSame('New from upstream release', $blocks[0]['props']['text']);
        $this->assertSame('A', $blocks[1]['props']['title']);
        $this->assertSame([], $result->conflicts);
    }

    /**
     * Reconciliation itself has no concept of "provider available" — that
     * trust boundary belongs to ExtensionBlockReconciler at the save/render
     * layer (3E-H-C/3F-C), not here. This proves reconciliation preserves
     * an ext:* block's serialized data byte-for-byte regardless of whether
     * anything currently understands its type — the same type-blind
     * treatment every unknown/unrecognized block already gets.
     */
    public function test_unavailable_extension_block_preserved_type_blind(): void
    {
        $extBlock = ['id' => 'e1', 'type' => 'ext:acme:demo_card', 'props' => ['title' => 'Persisted while available']];
        $base = $this->snapshot(['pages' => ['home' => $this->page([$extBlock])]]);
        $local = $base;
        $remote = $base;

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame($extBlock, $this->pageBlocks($result->reconciledTheme)[0]);
        $this->assertSame([], $result->conflicts);
    }

    public function test_nested_ext_child_under_a_container_is_preserved(): void
    {
        $container = [
            'id' => 'c1',
            'type' => 'ext:acme:demo_container',
            'props' => [],
            'children' => [
                ['id' => 'e1', 'type' => 'ext:acme:demo_card', 'props' => ['title' => 'Nested']],
            ],
        ];
        $snap = $this->snapshot(['pages' => ['home' => $this->page([$container])]]);

        $result = $this->reconciler()->reconcile($snap, $snap, $snap);
        $blocks = $this->pageBlocks($result->reconciledTheme);

        $this->assertSame('ext:acme:demo_container', $blocks[0]['type']);
        $this->assertSame('ext:acme:demo_card', $blocks[0]['children'][0]['type']);
        $this->assertSame('Nested', $blocks[0]['children'][0]['props']['title']);
        $this->assertSame([], $result->conflicts);
    }
}

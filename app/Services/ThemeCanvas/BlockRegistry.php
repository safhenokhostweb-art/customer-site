<?php

namespace App\Services\ThemeCanvas;

final class BlockRegistry
{
    private static ?array $blocks = null;

    public const CATEGORY_LAYOUT    = 'layout';
    public const CATEGORY_CONTENT   = 'content';
    public const CATEGORY_DYNAMIC   = 'dynamic';
    public const CATEGORY_ECOMMERCE = 'ecommerce';
    public const CATEGORY_MARKETING = 'marketing';
    public const CATEGORY_UTILITY   = 'utility';
    public const CATEGORY_MODULE    = 'module';

    /**
     * Phase 0.2c (ADR-062): field-level host deltas over the canonical
     * definitions — type => [field => replacement definition]. Each entry
     * must state why this host cannot use the canonical field; parity
     * tests (CanonicalBlockDefinitionParityTest) fail on any other
     * difference.
     */
    public const HOST_PATCHES = [
        // The tenant renderer (ThemeCanvasRenderer::renderModuleProductGrid)
        // has no sales-backed query, so 'bestselling' would silently list
        // the newest products. Drop this patch once that source reads
        // ProductDataSource's best_selling_30d ordering.
        'module:shop_product_grid' => [
            'source' => ['type' => 'select', 'label' => 'منبع', 'options' => ['recent' => 'جدیدترین', 'featured' => 'ویژه'], 'default' => 'recent'],
        ],
    ];

    public static function all(): array
    {
        // Phase 3F-C: only the local+canonical portion is memoized — see
        // the merge at the end of this method for why the extension
        // portion deliberately is not.
        if (self::$blocks !== null) {
            return self::$blocks + \App\Builder\Extension\CanvasBuilderAdapter::palette();
        }

        // Phase 0.2c (ADR-062): this host no longer redefines canonical
        // types. Its 65 former local copies had all drifted from the
        // canonical registry — labels, control types, option lists, and
        // fields no renderer read — and were harmonized there instead, so
        // the canonical definition is the one authority on both hosts.
        // HOST_PATCHES is the entire remaining host delta.
        self::$blocks = \SaaSPlatform\ThemeRuntime\BlockRegistry::all();
        foreach (self::HOST_PATCHES as $type => $fields) {
            foreach ($fields as $field => $definition) {
                self::$blocks[$type]['schema'][$field] = $definition;
            }
        }

        // module:gallery_widget is not offered on this host (no backend
        // content type/model exists for it here); the canonical registry
        // still defines it for saas-platform's own Builder preview.
        unset(self::$blocks['module:gallery_widget']);

        // Same rule applied to two more canonical-only types with no
        // backend content model on this host (Builder integrity audit,
        // 2026-09-11): module:portfolio_grid (no Portfolio model — the
        // static, editor-entered 'gallery'/'bento_grid' blocks already
        // cover this use case) and module:services_section (no Service
        // model — 'services_cards'/'service_cards' already cover it).
        // Both remain defined canonically for saas-platform's own preview.
        unset(self::$blocks['module:portfolio_grid']);
        unset(self::$blocks['module:services_section']);

        // BLD-10 (Full Product Commercial Audit, TASK 1/3): the bare
        // `products` type (title/limit/source) is a duplicate authority for
        // exactly the same responsibility `module:shop_product_grid`/
        // `product_grid` already cover, correctly, on this host — same
        // schema shape, no distinct behavior. It has never had a renderer
        // here (falls through to the empty generic fallback), and building
        // a second real product-grid renderer would be the exact kind of
        // parallel product path the Pre-Customer Convergence Rule exists
        // to prevent. Removed from the authoring palette rather than
        // duplicated; the canonical replacement already exists and is
        // production-ready.
        unset(self::$blocks['products']);

        // Placeholder re-evaluation (Full Product Commercial Audit, TASK
        // 2A): `team` is a DYNAMIC-category type designed around a "team
        // member" data source that was never built (no Team model/CRUD
        // exists anywhere in this app) — it always renders the honest
        // canonical placeholder hint text, forever, with no way to ever
        // become real. `team_cards` already covers the exact same
        // customer need (a team-members section) via direct repeater
        // authoring, production-ready. Removed rather than left as a
        // permanently-inert duplicate choice in the palette.
        unset(self::$blocks['team']);

        // Phase 3F-C — registered Theme Builder Extension SDK definitions
        // (ext:*) are bridged in on every call, NOT folded into the
        // memoized self::$blocks above. This is deliberate, not an
        // oversight: ExtensionBlockTypeRegistry::assertAvailable() (the
        // existing 3E-B collision authority) itself calls
        // ThemeCanvasBlockRegistry::all() as one of its checks, DURING
        // extension registration — i.e. before ElementRegistry has yet
        // been told about the type being registered. If the extension
        // merge were folded into the memoized self::$blocks, that
        // in-progress registration call would permanently freeze a
        // pre-registration (extension-less) snapshot for the remainder of
        // the process — the very extension being registered would never
        // become visible here again. Keeping self::$blocks as the pure
        // local+canonical snapshot (never containing an ext:*-prefixed
        // key) also preserves, rather than merely coincidentally matches,
        // that collision check's own documented assumption. The merge
        // itself costs nothing extra to repeat: CanvasBuilderAdapter reads
        // ElementRegistry::definitions(), itself already memoized.
        return self::$blocks + \App\Builder\Extension\CanvasBuilderAdapter::palette();
    }

    public static function get(string $type): ?array
    {
        return self::all()[$type] ?? null;
    }

    /**
     * Phase 3F-C — test-only cache invalidation, mirroring
     * ElementRegistry::clearCache(). Before this phase all() was 100%
     * static/config-driven within a process, so no reset ever mattered;
     * now that it also merges CanvasBuilderAdapter::palette() (which
     * reflects test-scoped extension registration/reset cycles), a test
     * that registers or resets extensions must also reset this memoized
     * snapshot. No production caller needs this — a real request is
     * always a fresh process, exactly like ElementRegistry's own cache.
     */
    public static function reset(): void
    {
        self::$blocks = null;
    }

    /**
     * Phase 3F-B — the one container-capability answer for this Canvas
     * domain, delegating to the canonical shared authority rather than the
     * Canvas editor's own hardcoded JS array (which this replaces as the
     * decision source, not merely a duplicate of it). Deliberately queries
     * the canonical registry directly rather than reading an `is_container`
     * key off self::all()[$type], so it stays correct even if a host patch
     * ever touches a container type. No host-only or
     * extension-aware layer exists here yet — that is Phase 3F-C's job
     * (Extension SDK → Canvas authoring bridge); this phase only makes the
     * canonical/shared layer consumable.
     */
    public static function isContainerType(string $type): bool
    {
        // Phase 3F-C: layered exactly like App\Services\ThemeBuilder\
        // BlockRenderer::isContainerType() — canonical/shared layer first,
        // then the host/extension layer (App\Builder\ElementRegistry),
        // covering any registered ext:* type declaring
        // HasContainerCapability. Unregistered/unavailable types (including
        // unregistered ext:*) are never containers (definition() is null).
        if (\SaaSPlatform\ThemeRuntime\BlockRegistry::isContainerType($type)) {
            return true;
        }
        $definition = \App\Builder\ElementRegistry::definition($type);
        return $definition !== null && $definition->isContainer;
    }

    /**
     * Phase 2i: this registry had no empty-output-policy concept at all —
     * delegates to the canonical authority rather than reimplementing it.
     */
    public static function emptyOutputPolicy(string $type): string
    {
        return \SaaSPlatform\ThemeRuntime\BlockRegistry::emptyOutputPolicy($type);
    }

    public static function defaultProps(string $type): array
    {
        $def = self::get($type);
        return $def['defaultProps'] ?? [];
    }

    public static function byCategory(): array
    {
        $byCat = [];
        foreach (self::all() as $type => $def) {
            $cat = $def['category'] ?? 'content';
            $byCat[$cat][$type] = $def;
        }
        return $byCat;
    }

    public static function categoryLabels(): array
    {
        return [
            self::CATEGORY_LAYOUT    => 'چیدمان',
            self::CATEGORY_CONTENT   => 'محتوای ثابت',
            self::CATEGORY_DYNAMIC   => 'محتوای پویا',
            self::CATEGORY_ECOMMERCE => 'فروشگاه',
            self::CATEGORY_MARKETING => 'بازاریابی',
            self::CATEGORY_UTILITY   => 'ابزار',
            self::CATEGORY_MODULE    => 'ماژول‌ها',
        ];
    }

    public static function categoryIcons(): array
    {
        return [
            self::CATEGORY_LAYOUT    => '📐',
            self::CATEGORY_CONTENT   => '📝',
            self::CATEGORY_DYNAMIC   => '🔄',
            self::CATEGORY_ECOMMERCE => '🛒',
            self::CATEGORY_MARKETING => '📣',
            self::CATEGORY_UTILITY   => '🔧',
            self::CATEGORY_MODULE    => '🧩',
        ];
    }
}

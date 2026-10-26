<?php

namespace Tests\Feature\ThemeCanvas;

use App\Services\ThemeCanvas\BlockRegistry as HostRegistry;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SaaSPlatform\ThemeRuntime\BlockRegistry as CanonicalRegistry;
use SaaSPlatform\ThemeRuntime\BlockRenderer as CanonicalRenderer;
use SaaSPlatform\ThemeRuntime\Inspector\InspectorFieldRegistry;
use Tests\TestCase;

/**
 * Phase 0.2 (saas-platform docs/theme-builder/COMMERCIAL_WIDGET_ROADMAP.md,
 * ADR-060): this host's tested list (repeater) definitions are promoted into
 * the canonical shared BlockRegistry, so the platform Canvas and this host's
 * Canvas author the same list fields the same way.
 *
 * Contract: for every host repeater on a canonical type, the canonical field
 * is a repeater with the SAME storage and every host child field with the
 * SAME normalized control (recursively). Canonical may declare extra child
 * fields (it is the shared authority, e.g. bento_grid's media/glow fields).
 *
 * NOTE: vendor/saas-platform/modules-theme-runtime is a Composer mirror
 * (symlink: false) — re-mirror it (`composer reinstall
 * saas-platform/modules-theme-runtime`) after changing the package source,
 * or this test reads a stale canonical registry.
 */
class CanonicalListSchemaParityTest extends TestCase
{
    use RefreshDatabase;

    /** Every list field promoted in Phase 0.2 (+ bento_grid.items, canonical-ahead since Phase 0.1). */
    private const MIGRATED = [
        'slider.slides', 'gallery.items', 'module:faq_dynamic.items', 'features_grid.features',
        'stats_row.stats', 'client_logos.logos', 'process_steps.steps', 'timeline.items',
        'team_cards.members', 'skills_bars.skills', 'trust_badges.badges', 'pricing_cards.plans',
        'bento_grid.items', 'services_cards.services', 'nav_header.nav_links', 'nav_footer.columns',
        'nav_footer.links', 'nav_footer.social_links', 'about_section.features', 'about_section.stats',
        'service_cards.cards', 'project_cards.cards', 'contact_info.items',
    ];

    /**
     * Fields that were canonical JSON textareas before Phase 0.1/0.2, so
     * already-authored documents may hold them as JSON strings. The other
     * migrated fields were always arrays (or never authorable) — they have
     * no legacy JSON shape to stay compatible with.
     */
    private const LEGACY_JSON = [
        'features_grid.features', 'stats_row.stats', 'client_logos.logos', 'process_steps.steps',
        'timeline.items', 'team_cards.members', 'skills_bars.skills', 'trust_badges.badges',
        'pricing_cards.plans', 'services_cards.services', 'bento_grid.items',
    ];

    // The renderer's "script already emitted" flags are process-wide statics;
    // leaving them set would change what later tests in the same process
    // render (e.g. CanonicalStructuralParityTest's canonical-vs-host diff).
    protected function tearDown(): void
    {
        CanonicalRenderer::resetCarouselScriptEmission();
        CanonicalRenderer::resetCountdownAdapterEmission();
        CanonicalRenderer::resetReferenceBlockScriptEmission();
        parent::tearDown();
    }

    private function normalized(array $field, string $key): array
    {
        return InspectorFieldRegistry::normalizeField($key, $field);
    }

    /** @return list<string> mismatch descriptions */
    private function compare(string $path, array $host, ?array $canonical): array
    {
        if ($canonical === null) {
            return ["{$path}: missing canonically"];
        }
        $h = $this->normalized($host, $path);
        $c = $this->normalized($canonical, $path);
        if ($h['control'] !== $c['control']) {
            return ["{$path}: control host={$h['control']} canonical={$c['control']}"];
        }
        if ($h['control'] !== 'repeater' || ($host['type'] ?? null) === 'blocks') {
            return [];
        }
        $out = [];
        if (($host['storage'] ?? 'array') !== ($canonical['storage'] ?? 'array')) {
            $out[] = "{$path}: storage host=" . ($host['storage'] ?? 'array') . ' canonical=' . ($canonical['storage'] ?? 'array');
        }
        foreach ($host['fields'] ?? [] as $childKey => $childDef) {
            array_push($out, ...$this->compare("{$path}.{$childKey}", $childDef, $canonical['fields'][$childKey] ?? null));
        }
        return $out;
    }

    public function test_every_host_list_definition_matches_the_canonical_contract(): void
    {
        $canonical = CanonicalRegistry::all();
        $mismatches = [];
        $checked = 0;

        foreach (HostRegistry::all() as $type => $definition) {
            if (!CanonicalRegistry::isCanonicalType($type)) {
                continue;
            }
            foreach ($definition['schema'] ?? [] as $key => $field) {
                if (($field['type'] ?? null) !== 'repeater') {
                    continue;
                }
                $checked++;
                array_push($mismatches, ...$this->compare("{$type}.{$key}", $field, $canonical[$type]['schema'][$key] ?? null));
            }
        }

        $this->assertGreaterThanOrEqual(count(self::MIGRATED), $checked);
        $this->assertSame([], $mismatches, "Host list definitions drifted from the canonical registry:\n" . implode("\n", $mismatches));
    }

    public function test_every_migrated_field_is_a_canonical_repeater(): void
    {
        foreach (self::MIGRATED as $target) {
            [$type, $key] = explode('.', $target);
            $field = CanonicalRegistry::get($type)['schema'][$key] ?? null;
            $this->assertNotNull($field, "{$target} missing canonically");
            $this->assertSame('repeater', $field['type'], $target);
            $this->assertNotEmpty($field['fields'] ?? [], "{$target} declares no child fields");
        }
    }

    // ── Render parity: array storage == legacy JSON-string storage ─────────

    private function sampleItems(array $fields, int $count = 2): array
    {
        $items = [];
        for ($i = 1; $i <= $count; $i++) {
            $item = [];
            foreach ($fields as $key => $def) {
                $item[$key] = match ($this->normalized($def, $key)['control']) {
                    'media' => "/storage/media/parity-{$key}-{$i}.jpg",
                    'toggle' => true,
                    'number', 'range' => $i,
                    'select' => (string) array_key_first($def['options']),
                    'repeater' => $this->sampleItems($def['fields'] ?? [], 1),
                    default => match ($key) {
                        'url', 'link', 'link_url', 'cta_url' => "/parity/{$key}/{$i}",
                        'value', 'percent', 'number', 'rating' => (string) (10 * $i),
                        default => "نمونه {$key} {$i}",
                    },
                };
            }
            $items[] = $item;
        }
        return $items;
    }

    private function render(string $type, array $props): string
    {
        CanonicalRenderer::resetCarouselScriptEmission();
        CanonicalRenderer::resetCountdownAdapterEmission();
        CanonicalRenderer::resetReferenceBlockScriptEmission();
        $renderer = app(ThemeCanvasRenderer::class);
        $renderer->setThemeData(['pages' => []]);

        return $renderer->renderBlock(['id' => 'parity', 'type' => $type, 'props' => $props]);
    }

    public function test_every_migrated_list_reaches_the_public_render(): void
    {
        foreach (self::MIGRATED as $target) {
            [$type, $key] = explode('.', $target);
            $definition = CanonicalRegistry::get($type);
            $base = $definition['defaultProps'] ?? [];

            $authored = $this->render($type, [...$base, $key => $this->sampleItems($definition['schema'][$key]['fields'])]);
            $empty = $this->render($type, [...$base, $key => []]);
            $this->assertNotSame($empty, $authored, "{$target}: the public renderer must actually read the authored list");
        }
    }

    public function test_array_and_legacy_json_storage_render_identically(): void
    {
        foreach (self::LEGACY_JSON as $target) {
            [$type, $key] = explode('.', $target);
            $definition = CanonicalRegistry::get($type);
            $items = $this->sampleItems($definition['schema'][$key]['fields']);
            $base = $definition['defaultProps'] ?? [];

            $asArray = $this->render($type, [...$base, $key => $items]);
            $asJson = $this->render($type, [...$base, $key => json_encode($items, JSON_UNESCAPED_UNICODE)]);
            $this->assertSame($asArray, $asJson, "{$target}: array and JSON-string storage must render identically");
        }
    }
}

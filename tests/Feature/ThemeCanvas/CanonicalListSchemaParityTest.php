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

    /** Phase 0.2b (ADR-061): lists authored inside another list's items, two and three levels deep. */
    private const NESTED = [
        'pricing_cards.plans.features', 'nav_footer.columns.links',
        'nav_header.nav_links.mega_columns', 'nav_header.nav_links.mega_columns.items',
    ];

    /** Phase 0.2b: every navigational URL field — top-level or nested — is a `link` field on both sides. */
    private const LINK_FIELDS = [
        'slider.slides.link', 'promo_banner.link', 'client_logos.logos.url', 'timeline.items.link_url',
        'pricing_cards.plans.cta_url', 'services_cards.services.link', 'nav_header.logo_url',
        'nav_header.nav_links.url', 'nav_header.nav_links.mega_columns.items.url',
        'nav_header.nav_links.mega_banner_url', 'nav_header.cta_url', 'nav_footer.columns.links.url',
        'nav_footer.links.url', 'nav_footer.social_links.url', 'about_section.btn_url',
        'service_cards.cards.url', 'project_cards.cards.url', 'header_modern_v1.homeUrl',
        'header_modern_v1.ctaUrl', 'foodmart_hero.slides.cta_url', 'foodmart_hero.promos.cta_url',
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

    /** A field definition by dotted path: type.key[.childKey…] (descending through repeater `fields`). */
    private function fieldAt(?array $definition, string $path): ?array
    {
        $keys = array_slice(explode('.', $path), 1);
        $field = $definition['schema'][array_shift($keys)] ?? null;
        foreach ($keys as $key) {
            $field = $field['fields'][$key] ?? null;
        }
        return $field;
    }

    private function type(string $path): string
    {
        return explode('.', $path)[0];
    }

    public function test_nested_lists_are_first_class_repeaters_on_both_sides(): void
    {
        foreach (self::NESTED as $path) {
            foreach (['host' => HostRegistry::get($this->type($path)), 'canonical' => CanonicalRegistry::get($this->type($path))] as $side => $definition) {
                $field = $this->fieldAt($definition, $path);
                $this->assertNotNull($field, "{$side}: {$path} missing");
                $this->assertSame('repeater', $field['type'], "{$side}: {$path}");
                $this->assertNotEmpty($field['fields'] ?? [], "{$side}: {$path} declares no child fields");
                $this->assertNotSame('', $field['item_label'] ?? '', "{$side}: {$path} needs an item_label for its add button");
            }
        }
    }

    public function test_url_fields_are_link_fields_and_highlight_is_a_switch_on_both_sides(): void
    {
        $sides = fn (string $path) => array_filter([
            'host' => HostRegistry::get($this->type($path)),
            'canonical' => CanonicalRegistry::get($this->type($path)),
        ]);
        foreach (self::LINK_FIELDS as $path) {
            foreach ($sides($path) as $side => $definition) {
                $this->assertSame('link', $this->fieldAt($definition, $path)['type'] ?? null, "{$side}: {$path} must be a link field");
            }
        }
        foreach ($sides('pricing_cards.plans.highlight') as $side => $definition) {
            $highlight = $this->fieldAt($definition, 'pricing_cards.plans.highlight');
            $this->assertSame('boolean', $highlight['type'] ?? null, "{$side}: pricing highlight must be a boolean switch");
            $this->assertFalse($highlight['default'], "{$side}: a new plan is not highlighted");
        }
    }

    /**
     * Full sample props for a type, from the definition this host renders
     * and validates against: every list gets one sample item, every scalar
     * a real value.
     */
    private function sampleProps(string $type): array
    {
        $definition = HostRegistry::get($type) ?? CanonicalRegistry::get($type);
        $props = $definition['defaultProps'] ?? [];
        foreach ($definition['schema'] as $key => $def) {
            $props[$key] = $this->normalized($def, $key)['control'] === 'repeater'
                ? $this->sampleItems($def['fields'] ?? [], 1)
                : ($this->sampleItems([$key => $def], 1)[0][$key]);
        }
        return $props;
    }

    /** Sets a value at a dotted field path inside the first item of every list on the way. */
    private function withValueAt(array $props, string $path, mixed $value): array
    {
        $keys = array_slice(explode('.', $path), 1);
        $ref = &$props;
        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                $ref[$key] = $value;
                break;
            }
            $ref = &$ref[$key][0];
        }
        unset($ref);
        return $props;
    }

    public function test_every_link_field_renders_structured_links_and_refuses_unsafe_urls(): void
    {
        foreach (self::LINK_FIELDS as $i => $path) {
            $type = $this->type($path);
            $props = $this->sampleProps($type);

            $structured = $this->render($type, $this->withValueAt($props, $path, ['url' => "/structured-ok-{$i}", 'target' => '_self']));
            $this->assertStringContainsString("href=\"/structured-ok-{$i}\"", $structured, "{$path}: a structured {url} link must render its url");
            $this->assertStringNotContainsString('render_error', $structured, $path);

            $unsafe = $this->render($type, $this->withValueAt($props, $path, 'javascript:alert(1)'));
            $this->assertStringNotContainsStringIgnoringCase('javascript:', $unsafe, "{$path}: an unsafe scheme must never reach an href");
        }
    }

    public function test_nested_list_content_reaches_the_public_render(): void
    {
        $pricing = $this->render('pricing_cards', ['title' => 'پلن‌ها', 'plans' => [
            ['name' => 'پایه', 'price' => '۱۰', 'highlight' => 'false', 'features' => ['ویژگی قدیمی'], 'cta_text' => 'شروع', 'cta_url' => '/basic'],
            ['_id' => 'p2', 'name' => 'ویژه', 'price' => '۲۰', 'highlight' => true, 'features' => [['_id' => 'f1', 'text' => 'پشتیبانی ۲۴ ساعته'], ['_id' => 'f2', 'text' => 'دامنه رایگان']], 'cta_text' => 'خرید', 'cta_url' => ['url' => '/pro']],
        ]]);
        $this->assertStringContainsString('ویژگی قدیمی', $pricing, 'legacy scalar features still render');
        $this->assertStringContainsString('دامنه رایگان', $pricing, 'nested {text} features render');
        $this->assertSame(1, substr_count($pricing, 'tb-pricing-card highlighted'), 'the string "false" is not a highlight; true is');
        $this->assertStringContainsString('href="/pro"', $pricing);

        $footer = $this->render('nav_footer', ['columns' => [
            ['title' => 'فروشگاه', 'links' => [['label' => 'محصولات', 'url' => '/products'], ['label' => 'بد', 'url' => 'javascript:alert(1)']]],
        ]]);
        $this->assertStringContainsString('<a href="/products">محصولات</a>', $footer);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $footer);

        $header = $this->render('nav_header', ['logo_text' => 'برند', 'nav_links' => [[
            'label' => 'دسته‌ها', 'url' => '/shop',
            'mega_columns' => [['title' => 'پوشاک', 'items' => [['label' => 'پیراهن', 'url' => ['url' => '/shirts']]]]],
        ]]]);
        $this->assertStringContainsString('پوشاک', $header, 'a mega-menu column title renders');
        $this->assertStringContainsString('پیراهن', $header, 'a third-level mega-menu item renders');
        $this->assertStringContainsString('href="/shirts"', $header);
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

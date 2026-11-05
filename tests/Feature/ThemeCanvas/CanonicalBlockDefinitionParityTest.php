<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\SocialLink;
use App\Services\ThemeBuilder\ElementSchemaRegistry;
use App\Services\ThemeCanvas\BlockRegistry as HostRegistry;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SaaSPlatform\ThemeRuntime\BlockRegistry as CanonicalRegistry;
use Tests\TestCase;

/**
 * Phase 0.2c (saas-platform docs/theme-builder/COMMERCIAL_WIDGET_ROADMAP.md,
 * ADR-062): this host's registry is the canonical registry plus
 * HostRegistry::HOST_PATCHES. The 65 local copies it used to keep had all
 * drifted (labels, control types, options, fields no renderer read) and
 * were harmonized into the canonical definitions, then removed.
 *
 * NOTE: vendor/saas-platform/modules-theme-runtime is a Composer mirror —
 * re-mirror it after changing the package source, or this test reads a
 * stale canonical registry.
 */
class CanonicalBlockDefinitionParityTest extends TestCase
{
    use RefreshDatabase;

    /** Canonical types this host does not offer: no backing model or real renderer here. */
    private const NOT_OFFERED = ['products', 'team', 'module:gallery_widget', 'module:portfolio_grid', 'module:services_section'];

    /** Top-level field types this host's Canvas content tab has a control for. */
    private const EDITABLE_TYPES = [
        'string', 'text', 'textarea', 'rich_text', 'link', 'image', 'repeater', 'array',
        'number', 'motion', 'boolean', 'select', 'color', 'blocks',
        'motion_params', // ADR-079: the selected motion's runtime parameters (built from the manifest)
        'icon', // Phase 0.6 (saas-platform ADR-065): the shared icon picker
    ];

    private function hostTypes(): array
    {
        // ext:* types are bridged in from the Extension SDK, not the registry.
        return array_filter(HostRegistry::all(), fn ($t) => !str_starts_with($t, 'ext:'), ARRAY_FILTER_USE_KEY);
    }

    public function test_every_offered_type_is_the_canonical_definition_apart_from_declared_patches(): void
    {
        $host = $this->hostTypes();
        $drifted = [];

        foreach (CanonicalRegistry::all() as $type => $canonical) {
            if (in_array($type, self::NOT_OFFERED, true)) {
                $this->assertArrayNotHasKey($type, $host, "{$type} must stay unoffered on this host.");
                continue;
            }
            $this->assertArrayHasKey($type, $host, "Canonical type {$type} is missing on this host.");

            $expected = $canonical;
            foreach (HostRegistry::HOST_PATCHES[$type] ?? [] as $field => $definition) {
                $expected['schema'][$field] = $definition;
            }
            // Strict: same keys, values and field order — what both editors render.
            if ($host[$type] !== $expected) {
                $drifted[] = $type;
            }
        }

        $this->assertSame([], $drifted, 'Host definitions drifted from canonical: ' . implode(', ', $drifted));
        $this->assertSame([], array_diff(array_keys($host), array_keys(CanonicalRegistry::all())), 'Host-only registry types appeared.');
    }

    public function test_every_host_patch_replaces_a_real_canonical_field_with_a_real_difference(): void
    {
        $canonical = CanonicalRegistry::all();
        foreach (HostRegistry::HOST_PATCHES as $type => $fields) {
            foreach ($fields as $field => $definition) {
                $this->assertArrayHasKey($field, $canonical[$type]['schema'] ?? [], "Patch {$type}.{$field} targets no canonical field.");
                $this->assertNotSame($canonical[$type]['schema'][$field], $definition, "Patch {$type}.{$field} is identical to canonical — drop it.");
            }
        }

        // Phase 3 (saas-platform ADR-068): no patch is left — the bestselling
        // source reads Product::scopeBestSelling on this host too.
        $this->assertSame([], HostRegistry::HOST_PATCHES);
        $this->assertSame(['recent', 'featured', 'bestselling'], array_keys(HostRegistry::all()['module:shop_product_grid']['schema']['source']['options']));
    }

    public function test_every_field_has_a_canvas_control(): void
    {
        $missing = [];
        foreach ($this->hostTypes() as $type => $def) {
            foreach ($def['schema'] ?? [] as $key => $field) {
                if (!in_array($field['type'] ?? null, self::EDITABLE_TYPES, true)) {
                    $missing[] = "{$type}.{$key} (" . ($field['type'] ?? '?') . ')';
                }
            }
        }
        $this->assertSame([], $missing, 'Fields with no control in this Canvas: ' . implode(', ', $missing));
    }

    public function test_every_offered_types_defaults_pass_the_live_render_validator(): void
    {
        // ThemeBuilder\BlockRenderer::renderBlock() validates every block
        // before render and placeholders a failure, so a newly inserted
        // block must be valid with its own defaults.
        $validator = new ElementSchemaRegistry();
        $invalid = [];
        foreach ($this->hostTypes() as $type => $def) {
            if (str_starts_with($type, 'module:')) {
                continue; // never enters the validator (tenant-data adapters)
            }
            if (!$validator->validate(['id' => 'v1', 'type' => $type, 'props' => $def['defaultProps'] ?? []])) {
                $invalid[$type] = $validator->getLastErrors();
            }
        }
        $this->assertSame([], $invalid);
    }

    public function test_social_icons_keeps_this_hosts_markup_and_site_wide_fallback(): void
    {
        $renderer = app(ThemeCanvasRenderer::class);
        $renderer->setThemeData(['pages' => []]);

        // No links anywhere → nothing, as before.
        $this->assertSame('', trim(strip_tags($renderer->renderBlock(['id' => 's0', 'type' => 'social_icons', 'props' => []]))));

        SocialLink::create(['platform' => 'instagram', 'url' => 'https://instagram.com/acme', 'order' => 1]);
        $html = $renderer->renderBlock(['id' => 's1', 'type' => 'social_icons', 'props' => []]);
        // Default style is `icons` (first letter), byte-for-byte the former override's markup.
        $this->assertStringContainsString(
            '<div id="tb-s1" class="tb-social-icons tb-social-icons-icons" style="display:flex;gap:0.5rem;flex-wrap:wrap;">'
            . '<a href="https://instagram.com/acme" target="_blank" rel="noopener noreferrer" class="tb-social-link" aria-label="instagram">i</a></div>',
            $html
        );

        $own = $renderer->renderBlock(['id' => 's2', 'type' => 'social_icons', 'props' => [
            'style' => 'buttons', 'links' => [['label' => 'تلگرام', 'url' => 'https://t.me/acme']],
        ]]);
        $this->assertStringContainsString('class="tb-btn tb-btn-secondary" aria-label="تلگرام">تلگرام</a>', $own);
        $this->assertStringNotContainsString('instagram', $own);
    }

    public function test_video_inherits_the_hardened_canonical_renderer(): void
    {
        $renderer = app(ThemeCanvasRenderer::class);
        $renderer->setThemeData(['pages' => []]);

        $html = $renderer->renderBlock(['id' => 'v1', 'type' => 'video', 'props' => [
            'url' => '/storage/intro.mp4', 'poster' => '/storage/intro.jpg', 'autoplay' => false, 'loop' => true, 'controls' => true,
        ]]);
        $this->assertStringContainsString('<video src="/storage/intro.mp4" poster="/storage/intro.jpg" controls loop', $html);

        $unsafe = $renderer->renderBlock(['id' => 'v2', 'type' => 'video', 'props' => ['url' => 'javascript:alert(1)']]);
        $this->assertStringNotContainsString('javascript:', $unsafe);
        $this->assertStringContainsString('ویدیویی انتخاب نشده است', $unsafe);
    }
}

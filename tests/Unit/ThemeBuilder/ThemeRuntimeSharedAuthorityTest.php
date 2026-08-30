<?php

namespace Tests\Unit\ThemeBuilder;

use App\Services\ThemeBuilder\BlockRenderer;
use App\Services\ThemeBuilder\ElementSchemaRegistry;
use SaaSPlatform\ThemeRuntime\ThemeTokenNormalizer;
use Tests\TestCase;

/**
 * Phase 2h (ADR-029) — proves customer-site genuinely consumes the ONE
 * shared canonical rendering/token authority saas-platform's Builder
 * preview also consumes, rather than an independently forked copy. See
 * saas-platform's own equivalent test for the other side of this proof.
 */
class ThemeRuntimeSharedAuthorityTest extends TestCase
{
    private function renderer(): BlockRenderer
    {
        $renderer = new BlockRenderer();
        $renderer->setSchemaRegistry(new ElementSchemaRegistry());
        return $renderer;
    }

    public function test_the_apps_block_renderer_is_the_shared_canonical_renderer(): void
    {
        $this->assertInstanceOf(\SaaSPlatform\ThemeRuntime\BlockRenderer::class, $this->renderer());
    }

    public function test_a_canonical_method_not_redeclared_locally_resolves_via_shared_inheritance(): void
    {
        // renderModulePlaceholder is never redeclared in customer-site's
        // own BlockRenderer (it has its own, separate module: dispatch —
        // see ThemeCanvasRenderer) — calling it directly must resolve to
        // the shared base class's implementation, proving real inheritance
        // rather than a coincidentally-matching independent copy.
        $reflection = new \ReflectionMethod($this->renderer(), 'renderModulePlaceholder');

        $this->assertSame(
            \SaaSPlatform\ThemeRuntime\BlockRenderer::class,
            $reflection->getDeclaringClass()->getName()
        );
    }

    public function test_a_locally_overridden_canonical_type_still_takes_precedence_over_the_shared_default(): void
    {
        // blog_posts IS overridden here with real Post-model data — the
        // shared base's honest placeholder must NOT be what actually renders.
        $html = $this->renderer()->renderBlock(['type' => 'blog_posts', 'id' => 'x1', 'props' => ['limit' => 2]]);

        $sharedPlaceholderPhrase = 'پس از انتشار محتوا در این‌جا نمایش داده می‌شود';
        $this->assertStringNotContainsString($sharedPlaceholderPhrase, $html);
    }

    public function test_the_shared_token_normalizer_is_available_for_runtime_css_variables(): void
    {
        $normalizer = app(ThemeTokenNormalizer::class);

        $vars = $normalizer->normalize(['primary_color' => '#123456']);

        $this->assertSame('#123456', $vars['--tb-primary']);
    }
}

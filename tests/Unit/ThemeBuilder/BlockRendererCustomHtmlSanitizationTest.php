<?php

namespace Tests\Unit\ThemeBuilder;

use App\Services\ThemeBuilder\BlockRenderer;
use App\Services\ThemeBuilder\ElementSchemaRegistry;
use Tests\TestCase;

/**
 * Phase 3H-S1 — proves the `custom_html` block type (config/builder-
 * elements.php, dispatched via Str::studly('custom_html') => renderCustomHtml())
 * sanitizes exactly like the canonical SaaSPlatform\ThemeRuntime\Security\
 * HtmlSanitizer authority, not via an independent implementation.
 *
 * Exercises the REAL production path end-to-end — BlockRenderer::renderBlock()
 * — never a synthetic reimplementation of the sanitizer.
 */
class BlockRendererCustomHtmlSanitizationTest extends TestCase
{
    protected BlockRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new BlockRenderer();
        $this->renderer->setSchemaRegistry(new ElementSchemaRegistry());
    }

    private function renderCustomHtml(string $content): string
    {
        return $this->renderer->renderBlock([
            'type' => 'custom_html',
            'id' => 'ch1',
            'props' => ['content' => $content],
        ]);
    }

    // ── the specific divergence the 3H-A audit found ────────────────────────

    public function test_event_handler_attribute_on_an_otherwise_allowed_element_is_stripped(): void
    {
        $html = $this->renderCustomHtml('<p onmouseover="alert(1)">hello</p>');

        $this->assertStringNotContainsString('onmouseover', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        // The tag itself is still an allowed, legitimate formatting tag.
        $this->assertStringContainsString('<p>hello</p>', $html);
    }

    public function test_javascript_href_is_neutralized(): void
    {
        $html = $this->renderCustomHtml('<a href="javascript:alert(1)">click</a>');

        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_javascript_scheme_is_rejected_case_insensitively_and_with_leading_whitespace(): void
    {
        $html = $this->renderCustomHtml('<a href="  JaVaScRiPt:alert(1)">click</a>');

        $this->assertDoesNotMatchRegularExpression('/javascript\s*:/i', $html);
    }

    public function test_html_entity_encoded_javascript_scheme_is_neutralized(): void
    {
        // &#106; decodes to "j" — html_entity_decode() must run before the scheme check.
        $html = $this->renderCustomHtml('<a href="&#106;avascript:alert(1)">click</a>');

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
    }

    public function test_script_element_is_removed(): void
    {
        $html = $this->renderCustomHtml('<p>before</p><script>alert(1)</script><p>after</p>');

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringContainsString('before', $html);
        $this->assertStringContainsString('after', $html);
    }

    public function test_malformed_nested_markup_cannot_smuggle_a_script_tag(): void
    {
        $html = $this->renderCustomHtml('<a href="https://ok.example.com/" title="</a><script>alert(1)</script>">click</a>');

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
    }

    // ── must keep working ────────────────────────────────────────────────────

    public function test_safe_href_survives(): void
    {
        $html = $this->renderCustomHtml('<a href="https://example.com/page">link</a>');

        $this->assertStringContainsString('href="https://example.com/page"', $html);
        $this->assertStringContainsString('link', $html);
    }

    public function test_tel_href_is_permitted_by_canonical_url_policy(): void
    {
        // Canonical HtmlSanitizer::isValidUrl() allows tel: alongside
        // https?/mailto — an intentional consequence of converging on the
        // one URL-scheme authority, not a regression to guard against.
        $html = $this->renderCustomHtml('<a href="tel:+15551234567">call</a>');

        $this->assertStringContainsString('href="tel:+15551234567"', $html);
    }

    public function test_safe_formatting_survives(): void
    {
        $html = $this->renderCustomHtml('<p>Hello <strong>world</strong> and <em>friends</em></p>');

        $this->assertStringContainsString('<p>Hello <strong>world</strong> and <em>friends</em></p>', $html);
    }

    public function test_ordinary_attributes_follow_canonical_policy_not_a_wider_local_allowlist(): void
    {
        // Canonical HtmlSanitizer::sanitizeRichHtml() strips ALL attributes
        // except the rebuilt <a href> — a class attribute on <p> must not
        // survive, proving this is the canonical policy, not a bespoke one.
        $html = $this->renderCustomHtml('<p class="hero-heading" data-x="1">text</p>');

        $this->assertStringNotContainsString('class="hero-heading"', $html);
        $this->assertStringNotContainsString('data-x', $html);
        $this->assertStringContainsString('<p>text</p>', $html);
    }
}

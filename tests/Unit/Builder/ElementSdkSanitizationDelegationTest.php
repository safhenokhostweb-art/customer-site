<?php

namespace Tests\Unit\Builder;

use App\Builder\Elements\BaseElement;
use App\Builder\Rendering\ElementRenderer;
use App\Builder\Validation\ElementValidator;
use SaaSPlatform\ThemeRuntime\Security\HtmlSanitizer;
use Tests\TestCase;

/**
 * Phase 3E-C — proves the two duplicate, pre-Phase-3C-style sanitizers found
 * by Phase 3E-A (App\Builder\Traits\WithSanitization::sanitizeHtml() and
 * App\Builder\Validation\ElementValidator::sanitizeRichHtml()) now delegate
 * to the single canonical authority (SaaSPlatform\ThemeRuntime\Security\
 * HtmlSanitizer) instead of maintaining their own independent, unpatched
 * strip_tags()-only copy of the Phase 3C attribute/URL-scheme hardening.
 *
 * WithSanitization's sanitizeHtml()/isValidUrl() are `protected static`, so
 * a minimal test-only subclass exposes them — the same standard pattern
 * used to test any protected trait method, not a change to production code.
 */
class ElementSdkSanitizationDelegationTest extends TestCase
{
    private function probe(): object
    {
        return new class extends BaseElement {
            public static function getType(): string { return 'test_sanitization_probe'; }
            public static function getName(): string { return 'Probe'; }
            public static function getIcon(): string { return ''; }
            public static function getCategory(): string { return 'content'; }
            public static function getDefaultProps(): array { return []; }
            public static function getSchema(): array { return []; }
            public static function render(array $props, array $context = []): string { return ''; }

            public static function probeSanitizeHtml(string $s): string { return self::sanitizeHtml($s); }
            public static function probeIsValidUrl(string $url): bool { return self::isValidUrl($url); }
        };
    }

    // A. event handler attribute
    public function test_a_with_sanitization_strips_event_handler_attribute(): void
    {
        $html = $this->probe()::probeSanitizeHtml('<a onmouseover="alert(1)">x</a>');
        $this->assertStringNotContainsString('onmouseover', $html);
        $this->assertSame('<a href="#">x</a>', $html);
    }

    // B. javascript: href
    public function test_b_with_sanitization_neutralizes_javascript_href(): void
    {
        $html = $this->probe()::probeSanitizeHtml('<a href="javascript:alert(1)">x</a>');
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertSame('<a href="#">x</a>', $html);
    }

    // C. disallowed tag entirely removed
    public function test_c_with_sanitization_removes_disallowed_tag_with_dangerous_src(): void
    {
        $html = $this->probe()::probeSanitizeHtml('<img src="javascript:alert(1)">');
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    // D. mixed-case/whitespace-obfuscated scheme
    public function test_d_with_sanitization_rejects_mixed_case_whitespace_scheme(): void
    {
        $this->assertFalse($this->probe()::probeIsValidUrl('  JaVaScRiPt:alert(1)'));
    }

    // E. safe https
    public function test_e_with_sanitization_preserves_safe_https_link(): void
    {
        $html = $this->probe()::probeSanitizeHtml('<a href="https://example.com/docs">docs</a>');
        $this->assertSame('<a href="https://example.com/docs">docs</a>', $html);
    }

    // F. safe relative URL
    public function test_f_with_sanitization_preserves_safe_relative_url(): void
    {
        $html = $this->probe()::probeSanitizeHtml('<a href="/services">svc</a>');
        $this->assertSame('<a href="/services">svc</a>', $html);
    }

    // G. allowed formatting tags intact
    public function test_g_with_sanitization_preserves_allowed_formatting_tags(): void
    {
        $html = $this->probe()::probeSanitizeHtml('<p>Hello <strong>World</strong></p>');
        $this->assertSame('<p>Hello <strong>World</strong></p>', $html);
    }

    // H. unknown attributes stripped
    public function test_h_with_sanitization_strips_unknown_attributes(): void
    {
        $html = $this->probe()::probeSanitizeHtml('<p class="evil" onclick="alert(1)">text</p>');
        $this->assertSame('<p>text</p>', $html);
    }

    // I. plain text unchanged
    public function test_i_with_sanitization_leaves_plain_text_unchanged(): void
    {
        $this->assertSame('Hello World', $this->probe()::probeSanitizeHtml('Hello World'));
    }

    // J. both Element SDK sanitizers now produce byte-identical output to
    // canonical HtmlSanitizer — proving genuine single-authority delegation,
    // not merely coincidentally-similar independent logic.
    public function test_j_both_element_sdk_sanitizers_match_the_canonical_authority_exactly(): void
    {
        $payloads = [
            '<a onmouseover="alert(1)">x</a>',
            '<a href="javascript:alert(1)">x</a>',
            '<img src="javascript:alert(1)">',
            '<p class="evil" onclick="alert(1)">text</p>',
            '<p>Hello <strong>World</strong></p>',
            'Hello World',
        ];

        foreach ($payloads as $payload) {
            $canonical = HtmlSanitizer::sanitizeRichHtml($payload);

            $this->assertSame(
                $canonical,
                $this->probe()::probeSanitizeHtml($payload),
                "WithSanitization::sanitizeHtml() diverged from canonical for payload: {$payload}"
            );
            $this->assertSame(
                $canonical,
                ElementValidator::sanitizeRichHtml($payload),
                "ElementValidator::sanitizeRichHtml() diverged from canonical for payload: {$payload}"
            );
        }
    }

    public function test_element_validator_sanitize_rich_html_is_directly_hardened(): void
    {
        $html = ElementValidator::sanitizeRichHtml('<a href="javascript:alert(1)" onmouseover="alert(2)">click</a>');
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('onmouseover', $html);
        $this->assertSame('<a href="#">click</a>', $html);
    }

    // ── Stored-XSS integration coverage (Phase 3E-C §11) ────────────────────
    // Exercises the real schema-validate → render pipeline
    // (App\Builder\Rendering\ElementRenderer) a persisted Builder block
    // actually flows through — not a synthetic harness — for a malicious
    // "content" prop on the real, registered `text` element type.

    public function test_stored_malicious_rich_text_content_cannot_reach_rendered_output(): void
    {
        $html = (new ElementRenderer())->render('text', [
            'content' => '<a href="javascript:alert(document.cookie)" onmouseover="alert(1)">click me</a><img src=x onerror=alert(2)>',
        ], ['id' => 'xss1']);

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('onmouseover', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('click me', $html);
    }

    public function test_stored_malicious_button_url_cannot_reach_rendered_output(): void
    {
        $html = (new ElementRenderer())->render('button', [
            'text' => 'خرید', 'url' => 'javascript:alert(1)',
        ], ['id' => 'xss2']);

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('href="#"', $html);
        $this->assertStringContainsString('خرید', $html);
    }
}

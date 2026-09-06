<?php

namespace Tests\Unit\Helpers;

use App\Helpers\PostHtml;
use Tests\TestCase;

/**
 * Phase 3H-S1 — PostHtml is a genuinely different rich-text domain (blog
 * posts / static pages authored via a WYSIWYG editor: images, headings 1-6,
 * blockquote, figure/figcaption) from Theme "custom_html" blocks, so its
 * TAG allowlist is deliberately preserved, not converged onto the narrower
 * canonical HtmlSanitizer::sanitizeRichHtml() allowlist (that would silently
 * delete real stored content — images and most headings).
 *
 * What must still hold, because it's a fundamental security invariant, not
 * a domain choice: no `on*` event-handler attribute may survive on ANY
 * allowed tag, not only the two (`<img>`, `<a>`) this class already
 * rebuilt from scratch. Pre-fix, every other allowed tag (`<p>`, `<div>`,
 * `<span>`, headings, etc.) got zero attribute treatment from a bare
 * `strip_tags($html, $allowed)` call.
 */
class PostHtmlTest extends TestCase
{
    // ── the specific gap this closure fixes ─────────────────────────────────

    public function test_event_handler_attribute_on_a_div_is_stripped(): void
    {
        $html = PostHtml::safe('<div onclick="alert(1)">hello</div>');

        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringContainsString('hello', $html);
    }

    public function test_event_handler_attribute_on_a_paragraph_is_stripped(): void
    {
        $html = PostHtml::safe('<p onmouseover="alert(1)">text</p>');

        $this->assertStringNotContainsString('onmouseover', $html);
        $this->assertStringContainsString('text', $html);
    }

    public function test_event_handler_attribute_on_a_span_is_stripped(): void
    {
        $html = PostHtml::safe('<span onload="alert(1)">x</span>');

        $this->assertStringNotContainsString('onload', $html);
    }

    // ── must keep working: this is a real product's stored content ─────────

    public function test_safe_link_survives_with_noopener(): void
    {
        $html = PostHtml::safe('<a href="https://example.com/post">read more</a>');

        $this->assertStringContainsString('href="https://example.com/post"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
        $this->assertStringContainsString('read more', $html);
    }

    public function test_safe_image_survives(): void
    {
        $html = PostHtml::safe('<img src="https://example.com/photo.jpg" alt="ignored">');

        $this->assertStringContainsString('src="https://example.com/photo.jpg"', $html);
    }

    public function test_javascript_href_still_rejected(): void
    {
        $html = PostHtml::safe('<a href="javascript:alert(1)">click</a>');

        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_javascript_img_src_still_rejected(): void
    {
        $html = PostHtml::safe('<img src="javascript:alert(1)">');

        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_encoded_javascript_href_still_rejected(): void
    {
        $html = PostHtml::safe('<a href="&#106;avascript:alert(1)">click</a>');

        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_tel_href_is_permitted_by_canonical_url_policy(): void
    {
        // Delegating isSafeHref() to canonical HtmlSanitizer::isValidUrl()
        // is an intentional widening: tel: is in canonical's own scheme
        // allowlist (https?|mailto|tel), so a "call us" link now survives.
        $html = PostHtml::safe('<a href="tel:+15551234567">call us</a>');

        $this->assertStringContainsString('href="tel:+15551234567"', $html);
    }

    public function test_domain_specific_tags_not_in_canonical_html_sanitizer_still_survive(): void
    {
        // Proves the tag allowlist itself was NOT narrowed to canonical's —
        // these are legitimate blog/page content tags canonical doesn't
        // support (it has no <img>/<h1>/<h4-6>/<blockquote>/<figure>).
        $html = PostHtml::safe(
            '<h1>Title</h1><blockquote>quote</blockquote>'
            . '<figure><img src="https://example.com/a.jpg"><figcaption>cap</figcaption></figure>'
            . '<h4>Sub</h4><hr><u>u</u><s>s</s>'
        );

        foreach (['<h1>', '<blockquote>', '<figure>', '<figcaption>', '<h4>', '<hr', '<u>', '<s>'] as $tag) {
            $this->assertStringContainsString($tag, $html, "expected {$tag} to survive");
        }
    }

    public function test_script_element_is_removed(): void
    {
        $html = PostHtml::safe('<p>before</p><script>alert(1)</script><p>after</p>');

        $this->assertStringNotContainsString('<script', $html);
    }

    public function test_malformed_nested_markup_cannot_smuggle_a_script_tag(): void
    {
        $html = PostHtml::safe('<a href="https://ok.example.com/" title="</a><script>alert(1)</script>">click</a>');

        $this->assertStringNotContainsString('<script', $html);
    }

    public function test_empty_input_returns_empty_string(): void
    {
        $this->assertSame('', PostHtml::safe(null));
        $this->assertSame('', PostHtml::safe(''));
    }
}

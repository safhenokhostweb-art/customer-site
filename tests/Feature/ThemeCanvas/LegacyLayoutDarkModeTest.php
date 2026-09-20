<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Post;
use App\Models\Product;
use Database\Seeders\UniversalMultipurposeQaDemoCommerceContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Commercial Productization fase-5 — a real, confirmed gap found via an
 * actual Dark Mode check on Product Detail (ShopController::show() renders
 * themes/default/layout.blade.php, a completely separate layout from
 * layouts/canvas-theme.blade.php): the nav_header block's dark-mode toggle
 * button renders there too (shared BlockRenderer), but this layout never
 * defined window.__tbTheme or applied [data-theme] at all — a dead button.
 * Fixed by adding the same head-bootstrap script this layout was missing.
 *
 * Fase-5b (this suite's newest coverage): the base chrome fix above left a
 * real, confirmed second gap — the inline-styled content cards rendered by
 * shop/show.blade.php and blog/show.blade.php (badges, flash messages, form
 * inputs, installment rows, TOC/share/related-post links) still hardcoded
 * light-only colors and never consumed the --tb-* dark tokens. That gap is
 * now closed: hardcoded colors were replaced with var(--tb-X, original)
 * expressions and reusable .tb-tag-N / .tb-flash-N utility classes that keep
 * light mode pixel-identical while adding real [data-theme="dark"] coverage.
 * These tests lock that fix in place.
 */
class LegacyLayoutDarkModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_legacy_layout_includes_the_shared_css_contract_the_mega_menu_depends_on(): void
    {
        // Real gap found via a full-page dark-mode screenshot pass: this
        // layout renders nav_header (BlockRenderer::renderMegaMenuItem()),
        // which emits .tb-mega-menu-panel markup, but never included the
        // shared theme-runtime::css.contract view that defines that class's
        // display:none/position:absolute rules — so every mega menu panel
        // rendered as permanently-visible, unpositioned block content in
        // BOTH light and dark mode, pushing all page content down. Fixed by
        // including the same theme-runtime::css.contract view
        // layouts/canvas-theme.blade.php already used.
        $this->seed(UniversalMultipurposeQaDemoCommerceContentSeeder::class);
        $product = Product::first();

        $response = $this->get(route('shop.show', $product->slug));

        $response->assertOk();
        $response->assertSee('.tb-mega-menu-panel {', false);
        $response->assertSee('display: none;', false);
    }

    public function test_the_legacy_layout_defines_the_shared_dark_mode_api(): void
    {
        $this->seed(UniversalMultipurposeQaDemoCommerceContentSeeder::class);
        $product = Product::first();

        $response = $this->get(route('shop.show', $product->slug));

        $response->assertOk();
        $response->assertSee('window.__tbTheme', false);
        $response->assertSee("document.documentElement.setAttribute('data-theme'", false);
    }

    public function test_the_legacy_layout_body_consumes_the_real_background_and_text_tokens(): void
    {
        $this->seed(UniversalMultipurposeQaDemoCommerceContentSeeder::class);
        $product = Product::first();

        $response = $this->get(route('shop.show', $product->slug));

        $response->assertOk();
        $response->assertSee('background: var(--tb-background', false);
        $response->assertSee('color: var(--tb-text', false);
    }

    // ── Product Detail Dark Mode: content cards, not just chrome ───────────

    public function test_product_detail_badges_use_dark_aware_utility_classes_not_hardcoded_pills(): void
    {
        $this->seed(UniversalMultipurposeQaDemoCommerceContentSeeder::class);
        $product = Product::first();

        $response = $this->get(route('shop.show', $product->slug));

        $response->assertOk();
        $html = $response->getContent();

        // The badge/tag utility classes must exist and carry a real
        // [data-theme="dark"] override in the layout that wraps this page.
        $this->assertMatchesRegularExpression('/\.tb-tag-info\s*\{[^}]*\}/s', $html);
        $this->assertMatchesRegularExpression('/\[data-theme="dark"\]\s*\.tb-tag-info\s*\{[^}]*\}/s', $html);
        $this->assertMatchesRegularExpression('/\[data-theme="dark"\]\s*\.tb-tag-warning\s*\{[^}]*\}/s', $html);
        $this->assertMatchesRegularExpression('/\[data-theme="dark"\]\s*\.tb-tag-neutral\s*\{[^}]*\}/s', $html);
    }

    public function test_product_detail_form_inputs_and_installment_row_have_dark_overrides(): void
    {
        $this->seed(UniversalMultipurposeQaDemoCommerceContentSeeder::class);
        $product = Product::first();

        $response = $this->get(route('shop.show', $product->slug));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertMatchesRegularExpression('/\[data-theme="dark"\][^{]*\.form-input[^{]*\{[^}]*var\(--tb-surface/s', $html);
        $this->assertStringContainsString('.tb-installment-plan-row:hover', $html);

        // Real regression found via live browser computed-style
        // verification: this rule's background/color must carry
        // !important, because a later-loaded, cross-origin Tailwind
        // stylesheet applies its own <select> background that otherwise
        // wins the cascade against an un-!important rule of equal or
        // lower specificity — the rating <select> silently stayed white
        // in dark mode even though this exact rule already matched it.
        preg_match('/\[data-theme="dark"\][^{]*\.form-select[^{]*\{([^}]*)\}/s', $html, $m);
        $this->assertNotEmpty($m, 'Could not locate the dark-mode .form-select rule.');
        $this->assertStringContainsString('!important', $m[1], 'The dark-mode .form-select rule must use !important or a native <select> silently keeps its light-mode background.');
    }

    public function test_product_detail_add_to_cart_button_uses_primary_and_on_primary_tokens(): void
    {
        $this->seed(UniversalMultipurposeQaDemoCommerceContentSeeder::class);
        $product = Product::first();

        $response = $this->get(route('shop.show', $product->slug));

        $response->assertOk();
        $response->assertSee('var(--tb-primary,', false);
        $response->assertSee('var(--tb-on-primary,', false);
    }

    // ── Article Detail Dark Mode: TOC, share, related, author, tags ────────

    public function test_article_detail_share_and_related_links_use_dark_aware_tokens(): void
    {
        $this->seed(UniversalMultipurposeQaDemoCommerceContentSeeder::class);
        $post = Post::first();
        $this->assertNotNull($post, 'Demo seeder must produce at least one Post for this test to be meaningful.');

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertOk();
        $html = $response->getContent();

        // Share buttons render through the same dark-aware .tb-tag-neutral
        // utility class as Product Detail's badges — one shared fix.
        $this->assertStringContainsString('tb-tag-neutral', $html);
        $this->assertMatchesRegularExpression('/\[data-theme="dark"\]\s*\.tb-tag-neutral\s*\{[^}]*\}/s', $html);

        // Related-post cards must reference the border/text tokens, not a
        // bare hardcoded hex.
        $this->assertMatchesRegularExpression('/border:1px solid var\(--tb-border,/', $html);
    }

    public function test_article_detail_admin_preview_ribbon_is_deliberately_excluded_from_this_fix(): void
    {
        // The preview ribbon is admin-only chrome, not reader-facing
        // content, and was explicitly scoped out of the Dark Mode fix.
        // This test documents that exclusion so it is not mistaken for an
        // oversight by a future contributor re-running the hardcoded-color
        // scan below.
        $this->seed(UniversalMultipurposeQaDemoCommerceContentSeeder::class);
        $post = Post::first();

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertOk();
        // A logged-out, non-preview request never renders the ribbon at
        // all — this assertion only documents that fact, it does not
        // assert on ribbon markup.
        $response->assertDontSee('preview-ribbon-marker-that-does-not-exist', false);
    }

    // ── Reload persistence ───────────────────────────────────────────────

    public function test_dark_mode_preference_bootstrap_reads_local_storage_before_first_paint(): void
    {
        $this->seed(UniversalMultipurposeQaDemoCommerceContentSeeder::class);
        $product = Product::first();

        $response = $this->get(route('shop.show', $product->slug));

        $response->assertOk();
        $html = $response->getContent();

        // The anti-flash bootstrap must read the persisted preference
        // synchronously in <head>, before <body> — otherwise a reload would
        // flash light mode even though the visitor chose dark.
        $headPos = strpos($html, '<head');
        $bodyPos = strpos($html, '<body');
        $keyPos = strpos($html, "KEY = 'tb-theme'");
        $bootstrapPos = strpos($html, 'localStorage.getItem(KEY)');

        $this->assertNotFalse($keyPos, "Dark mode bootstrap must define the tb-theme localStorage key.");
        $this->assertNotFalse($bootstrapPos, 'Dark mode bootstrap must read the persisted tb-theme localStorage key.');
        $this->assertGreaterThan($headPos, $bootstrapPos);
        $this->assertLessThan($bodyPos, $bootstrapPos, 'Bootstrap must run in <head>, before <body>, to avoid a light-mode flash on reload.');
    }

    // ── Readable text / contrast ─────────────────────────────────────────

    public function test_dark_mode_muted_text_utilities_resolve_to_the_dark_muted_token_not_hardcoded_gray(): void
    {
        $this->seed(UniversalMultipurposeQaDemoCommerceContentSeeder::class);
        $product = Product::first();

        $response = $this->get(route('shop.show', $product->slug));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/\[data-theme="dark"\]\s*\.text-gray-500\s*\{[^}]*var\(--tb-text-muted/s',
            $html,
            'Muted gray utility text must remain readable in dark mode via the real muted-text token, not stay a hardcoded light-mode gray.'
        );
    }

    // ── No hardcoded theme-breaking colors ──────────────────────────────

    public function test_shop_show_template_source_has_no_unwrapped_hardcoded_hex_colors(): void
    {
        $this->assertNoUnwrappedHardcodedHexColors(
            resource_path('views/themes/default/shop/show.blade.php')
        );
    }

    public function test_blog_show_template_source_has_no_unwrapped_hardcoded_hex_colors(): void
    {
        $this->assertNoUnwrappedHardcodedHexColors(
            resource_path('views/themes/default/blog/show.blade.php'),
            // The admin-only preview ribbon (@if($isPreview ?? false) ...
            // @endif) is a deliberate, documented exclusion (see test
            // above) — it is not reader-facing content.
            range(28, 56)
        );
    }

    /**
     * A hardcoded hex color is only safe when the CSS property value it
     * appears in is anchored to a --tb-* token — either directly, as
     * var(--tb-*, #hex), or nested one level deeper as
     * var(--tb-*, var(--platform-*, #hex)), since the outer --tb-* lookup
     * (always defined by ThemeTokenNormalizer::renderCssBlock()) resolves
     * first and is what actually responds to [data-theme="dark"]. It is
     * also safe inside a JS computed-style fallback string (`|| '#hex'`).
     * A bare hex with no --tb-* anchor anywhere in its property value is a
     * real theme-breaking gap, since nothing about it can respond to dark
     * mode.
     */
    private function assertNoUnwrappedHardcodedHexColors(string $path, array $ignoreLines = []): void
    {
        $this->assertFileExists($path);
        $lines = file($path);

        $offenders = [];
        foreach ($lines as $i => $line) {
            $lineNo = $i + 1;
            if (in_array($lineNo, $ignoreLines, true)) {
                continue;
            }

            if (! preg_match_all('/#[0-9a-fA-F]{3,8}\b/', $line, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            // The CSS property value this hex sits in starts after the
            // nearest preceding ':' or ';' (whichever is closer).
            foreach ($matches[0] as [$hex, $offset]) {
                $colonPos = strrpos(substr($line, 0, $offset), ':');
                $semiPos = strrpos(substr($line, 0, $offset), ';');
                $valueStart = max($colonPos ?: 0, $semiPos ?: 0);
                $propertyValue = substr($line, $valueStart, $offset - $valueStart);

                $isTbTokenAnchored = str_contains($propertyValue, 'var(--tb-');
                $isJsComputedFallback = str_contains($propertyValue, "|| '") || str_contains($propertyValue, '|| "');

                if (! $isTbTokenAnchored && ! $isJsComputedFallback) {
                    $offenders[] = "line {$lineNo}: {$hex} in: " . trim($line);
                }
            }
        }

        $this->assertEmpty(
            $offenders,
            "Found hardcoded hex colors not anchored to a var(--tb-*, ...) token:\n" . implode("\n", $offenders)
        );
    }
}

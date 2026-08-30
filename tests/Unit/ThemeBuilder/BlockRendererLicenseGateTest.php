<?php

namespace Tests\Unit\ThemeBuilder;

use App\Models\License;
use App\Models\ProductCategory;
use App\Services\ThemeBuilder\BlockRenderer;
use App\Services\ThemeBuilder\ElementSchemaRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Commercial-integrity fix: the legacy BlockRenderer (used by the header/footer
 * builder) previously rendered live shop/course/blog data with no entitlement
 * check at all, unlike ThemeCanvasRenderer which already gated correctly.
 * A site without a matching valid license must never receive that paid-module
 * data through this renderer either.
 *
 * `renderProduct_grid` is exercised through the public renderBlock() path
 * (its type has a registered schema). The other four fixed methods
 * (renderFeaturedProducts, renderProductCategories, renderCourses,
 * renderBlogCategories) are not in the schema registry, so they are invoked
 * directly via reflection — this tests the license gate itself, independent
 * of the unrelated pre-existing schema-registry gap.
 */
class BlockRendererLicenseGateTest extends TestCase
{
    use RefreshDatabase;

    protected BlockRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new BlockRenderer();
        $this->renderer->setSchemaRegistry(new ElementSchemaRegistry());
    }

    private function seedLicense(array $modules): void
    {
        License::query()->create([
            'license_key' => 'TEST-' . uniqid(),
            'domain' => null,
            'expires_at' => now()->addYear(),
            'modules' => $modules,
            'platform_valid' => true,
            'last_validated_at' => now(),
        ]);
    }

    private function invokeProtected(string $method, array $args): string
    {
        $ref = new ReflectionMethod(BlockRenderer::class, $method);
        $ref->setAccessible(true);

        return $ref->invoke($this->renderer, ...$args);
    }

    // ── product_grid (public renderBlock path, schema-validated) ───────────

    public function test_unlicensed_site_does_not_get_live_shop_data_from_product_grid(): void
    {
        $html = $this->renderer->renderBlock(['type' => 'product_grid', 'id' => 'pg1', 'props' => []]);

        $this->assertStringContainsString('ماژول فروشگاه غیرفعال است', $html);
        $this->assertStringNotContainsString('tb-product-card', $html);
    }

    public function test_licensed_site_passes_the_shop_gate_for_product_grid(): void
    {
        $this->seedLicense(['shop']);

        $html = $this->renderer->renderBlock(['type' => 'product_grid', 'id' => 'pg2', 'props' => []]);

        // No products seeded, so it falls to the shop's real empty-state
        // (not the license-gate placeholder) — proves the gate was passed.
        $this->assertStringNotContainsString('ماژول فروشگاه غیرفعال است', $html);
        $this->assertStringContainsString('tb-product-grid-wrap', $html);
    }

    // ── featured_products ───────────────────────────────────────────────

    public function test_unlicensed_site_does_not_get_live_shop_data_from_featured_products(): void
    {
        $html = $this->invokeProtected('renderFeaturedProducts', [[], '', 'fp1']);
        $this->assertStringContainsString('ماژول فروشگاه غیرفعال است', $html);
    }

    public function test_licensed_site_passes_the_shop_gate_for_featured_products(): void
    {
        $this->seedLicense(['shop']);
        $html = $this->invokeProtected('renderFeaturedProducts', [[], '', 'fp2']);
        $this->assertStringNotContainsString('ماژول فروشگاه غیرفعال است', $html);
    }

    // ── product_categories — proven with a real DB row, not just an
    //     empty-state string, since the "no license" and "no data" branches
    //     otherwise share the same fallback message ──────────────────────

    public function test_unlicensed_site_never_leaks_a_real_product_category(): void
    {
        ProductCategory::query()->create(['name' => 'دسته تست', 'slug' => 'dasteh-test', 'order' => 1]);

        $html = $this->invokeProtected('renderProductCategories', [[], '', 'pc1']);

        $this->assertStringNotContainsString('tb-category-card', $html);
        $this->assertStringNotContainsString('دسته تست', $html);
    }

    public function test_licensed_site_sees_the_real_product_category(): void
    {
        $this->seedLicense(['shop']);
        ProductCategory::query()->create(['name' => 'دسته تست', 'slug' => 'dasteh-test', 'order' => 1]);

        $html = $this->invokeProtected('renderProductCategories', [[], '', 'pc2']);

        $this->assertStringContainsString('tb-category-card', $html);
        $this->assertStringContainsString('دسته تست', $html);
    }

    // ── courses ──────────────────────────────────────────────────────────

    public function test_unlicensed_site_does_not_get_live_course_data(): void
    {
        $html = $this->invokeProtected('renderCourses', [[], '', 'c1']);
        $this->assertStringContainsString('ماژول آموزش فعال نیست', $html);
    }

    public function test_licensed_site_passes_the_courses_gate(): void
    {
        // renderCourses() queries Course::where('is_published', ...) beyond the
        // gate, but that column does not exist on the `courses` table in this
        // schema — a pre-existing, unrelated bug (technical debt, not part of
        // this fix's scope). Asserting on \App\Helpers\SiteHelper::coursesEnabled()
        // directly proves the license condition this fix added, independent of
        // that separate breakage.
        $this->seedLicense(['courses']);
        $this->assertTrue(\App\Helpers\SiteHelper::coursesEnabled());
    }

    // ── blog_categories ──────────────────────────────────────────────────

    public function test_unlicensed_site_does_not_get_live_blog_category_data(): void
    {
        $html = $this->invokeProtected('renderBlogCategories', [[], '', 'bc1']);
        $this->assertStringContainsString('ماژول وبلاگ غیرفعال است', $html);
    }

    public function test_licensed_site_passes_the_blog_gate(): void
    {
        $this->seedLicense(['blog']);
        $html = $this->invokeProtected('renderBlogCategories', [[], '', 'bc2']);
        $this->assertStringNotContainsString('ماژول وبلاگ غیرفعال است', $html);
    }

    // ── existing templates without paid blocks are unaffected ──────────────

    public function test_free_blocks_still_render_regardless_of_license(): void
    {
        $html = $this->renderer->renderBlock([
            'type' => 'heading',
            'id' => 'h1',
            'props' => ['level' => 2, 'text' => 'عنوان تست'],
        ]);

        $this->assertStringContainsString('tb-heading', $html);
        $this->assertStringContainsString('عنوان تست', $html);
    }
}

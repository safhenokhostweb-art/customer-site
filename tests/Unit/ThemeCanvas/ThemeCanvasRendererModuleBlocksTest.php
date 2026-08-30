<?php

namespace Tests\Unit\ThemeCanvas;

use App\Models\License;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Setting;
use App\Services\ThemeCanvas\BlockRegistry;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SaaSPlatform\Modules\Forms\Models\Form;
use Tests\TestCase;

/**
 * Theme Builder gap closure — module:form_embed, module:whatsapp_widget and
 * module:reviews_grid previously fell through to renderModuleGenericPlaceholder()
 * (silent empty string) even though a customer could drag them onto a page in
 * the editor. Each now has a real implementation backed by real data. A
 * fourth block, module:gallery_widget, was removed from the palette instead —
 * there is no gallery content type anywhere in this codebase to back it.
 */
class ThemeCanvasRendererModuleBlocksTest extends TestCase
{
    use RefreshDatabase;

    protected ThemeCanvasRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new ThemeCanvasRenderer();
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
        $ref = new \ReflectionMethod(ThemeCanvasRenderer::class, $method);
        $ref->setAccessible(true);

        return $ref->invoke($this->renderer, ...$args);
    }

    // ── gallery_widget removed from the palette ─────────────────────────

    public function test_gallery_widget_is_no_longer_in_the_block_palette(): void
    {
        $this->assertArrayNotHasKey('module:gallery_widget', BlockRegistry::all());
    }

    public function test_the_remaining_three_module_blocks_are_still_in_the_palette(): void
    {
        $all = BlockRegistry::all();
        $this->assertArrayHasKey('module:form_embed', $all);
        $this->assertArrayHasKey('module:whatsapp_widget', $all);
        $this->assertArrayHasKey('module:reviews_grid', $all);
    }

    // ── whatsapp_widget ──────────────────────────────────────────────────

    public function test_whatsapp_widget_renders_nothing_when_module_unlicensed(): void
    {
        Setting::set('whatsapp_phone', '09120000000', 'general');

        $html = $this->invokeProtected('renderModuleWhatsappWidget', [[], 'w1']);

        $this->assertSame('', $html);
    }

    public function test_whatsapp_widget_renders_nothing_when_licensed_but_no_phone_configured(): void
    {
        $this->seedLicense(['whatsapp']);

        $html = $this->invokeProtected('renderModuleWhatsappWidget', [[], 'w2']);

        $this->assertSame('', $html);
    }

    public function test_whatsapp_widget_renders_a_real_wame_link_when_licensed_and_configured(): void
    {
        $this->seedLicense(['whatsapp']);
        Setting::set('whatsapp_phone', '09120000000', 'general');

        $html = $this->invokeProtected('renderModuleWhatsappWidget', [[], 'w3']);

        // Matches the existing MessagingChannel wa.me link convention:
        // strip non-digits only, no country-code normalization.
        $this->assertStringContainsString('https://wa.me/09120000000', $html);
    }

    public function test_whatsapp_widget_honors_the_messaging_alias(): void
    {
        // SiteHelper::MODULE_ALIASES maps whatsapp <-> messaging bidirectionally.
        $this->seedLicense(['messaging']);
        Setting::set('whatsapp_phone', '09120000000', 'general');

        $html = $this->invokeProtected('renderModuleWhatsappWidget', [[], 'w4']);

        $this->assertStringContainsString('wa.me', $html);
    }

    // ── form_embed ───────────────────────────────────────────────────────

    public function test_form_embed_renders_nothing_when_module_unlicensed(): void
    {
        $html = $this->invokeProtected('renderModuleFormEmbed', [['form_id' => '1'], 'f1']);

        $this->assertSame('', $html);
    }

    public function test_form_embed_shows_a_safe_message_when_no_form_selected(): void
    {
        $this->seedLicense(['forms']);

        $html = $this->invokeProtected('renderModuleFormEmbed', [[], 'f2']);

        $this->assertStringContainsString('فرم انتخاب نشده است', $html);
    }

    public function test_form_embed_shows_a_safe_message_when_form_not_found(): void
    {
        $this->seedLicense(['forms']);

        $html = $this->invokeProtected('renderModuleFormEmbed', [['form_id' => '999999'], 'f3']);

        $this->assertStringContainsString('فرم یافت نشد', $html);
    }

    public function test_form_embed_renders_a_real_iframe_for_an_existing_form_by_id(): void
    {
        $this->seedLicense(['forms']);
        $form = Form::create(['title' => 'فرم تماس', 'slug' => 'contact-test', 'is_active' => true]);

        $html = $this->invokeProtected('renderModuleFormEmbed', [['form_id' => (string) $form->id], 'f4']);

        $this->assertStringContainsString('<iframe', $html);
        $this->assertStringContainsString('/forms/contact-test', $html);
    }

    public function test_form_embed_resolves_by_slug_too(): void
    {
        $this->seedLicense(['forms']);
        Form::create(['title' => 'فرم تماس', 'slug' => 'contact-test', 'is_active' => true]);

        $html = $this->invokeProtected('renderModuleFormEmbed', [['form_id' => 'contact-test'], 'f5']);

        $this->assertStringContainsString('<iframe', $html);
    }

    public function test_form_embed_never_resolves_an_inactive_form(): void
    {
        $this->seedLicense(['forms']);
        $form = Form::create(['title' => 'فرم غیرفعال', 'slug' => 'inactive-test', 'is_active' => false]);

        $html = $this->invokeProtected('renderModuleFormEmbed', [['form_id' => (string) $form->id], 'f6']);

        $this->assertStringContainsString('فرم یافت نشد', $html);
    }

    // ── reviews_grid ─────────────────────────────────────────────────────

    public function test_reviews_grid_renders_nothing_when_module_unlicensed(): void
    {
        $html = $this->invokeProtected('renderModuleReviewsGrid', [[], 'r1']);

        $this->assertSame('', $html);
    }

    public function test_reviews_grid_shows_empty_state_when_licensed_but_no_reviews(): void
    {
        $this->seedLicense(['reviews']);

        $html = $this->invokeProtected('renderModuleReviewsGrid', [[], 'r2']);

        $this->assertStringContainsString('هنوز نظری ثبت نشده است', $html);
    }

    public function test_reviews_grid_renders_a_real_approved_review(): void
    {
        $this->seedLicense(['reviews']);
        $product = Product::create([
            'title' => 'محصول تست', 'slug' => 'test-product-' . uniqid(),
            'price' => 1000, 'status' => Product::STATUS_PUBLISHED,
        ]);
        ProductReview::create([
            'product_id' => $product->id, 'guest_name' => 'علی رضایی',
            'rating' => 5, 'body' => 'محصول عالی بود', 'approved' => true,
        ]);

        $html = $this->invokeProtected('renderModuleReviewsGrid', [[], 'r3']);

        $this->assertStringContainsString('علی رضایی', $html);
        $this->assertStringContainsString('محصول عالی بود', $html);
        $this->assertStringContainsString('محصول تست', $html);
    }

    public function test_reviews_grid_never_shows_an_unapproved_review(): void
    {
        $this->seedLicense(['reviews']);
        $product = Product::create([
            'title' => 'محصول تست', 'slug' => 'test-product-' . uniqid(),
            'price' => 1000, 'status' => Product::STATUS_PUBLISHED,
        ]);
        ProductReview::create([
            'product_id' => $product->id, 'guest_name' => 'ناشناس',
            'rating' => 1, 'body' => 'در انتظار تایید', 'approved' => false,
        ]);

        $html = $this->invokeProtected('renderModuleReviewsGrid', [[], 'r4']);

        $this->assertStringNotContainsString('در انتظار تایید', $html);
    }
}

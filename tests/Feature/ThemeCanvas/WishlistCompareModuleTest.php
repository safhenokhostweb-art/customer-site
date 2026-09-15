<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MOD-01 (Full Product Commercial Audit, TASK 2A): `module:shop_wishlist_icon`
 * reuses the real, already-working `ShopWishlistService` (wishlist mode) and
 * `session('shop_compare')` (compare mode) — no new backend logic, no fake
 * counts.
 */
class WishlistCompareModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        License::query()->create([
            'license_key' => 'WISH-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop'],
        ]);
    }

    private function renderer(): \App\Services\ThemeCanvas\ThemeCanvasRenderer
    {
        $renderer = app(\App\Services\ThemeCanvas\ThemeCanvasRenderer::class);
        $renderer->setThemeData(['pages' => []]);
        return $renderer;
    }

    private function product(string $slug): Product
    {
        return Product::create([
            'title' => 'محصول ' . $slug, 'slug' => $slug, 'price' => 100000,
            'status' => Product::STATUS_PUBLISHED, 'order' => 0,
        ]);
    }

    public function test_wishlist_icon_reflects_the_real_authenticated_users_wishlist_count(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::create(['user_id' => $user->id]);
        WishlistItem::create(['wishlist_id' => $wishlist->id, 'product_id' => $this->product('w1')->id]);
        WishlistItem::create(['wishlist_id' => $wishlist->id, 'product_id' => $this->product('w2')->id]);

        $html = $this->actingAs($user)->renderer()->renderBlock([
            'id' => 'wi1', 'type' => 'module:shop_wishlist_icon', 'props' => ['mode' => 'wishlist'],
        ]);

        $this->assertStringContainsString('>2<', $html);
        $this->assertStringContainsString(route('shop.wishlist'), $html);
    }

    public function test_wishlist_icon_with_zero_items_shows_no_badge(): void
    {
        $user = User::factory()->create();
        Wishlist::create(['user_id' => $user->id]);

        $html = $this->actingAs($user)->renderer()->renderBlock([
            'id' => 'wi2', 'type' => 'module:shop_wishlist_icon', 'props' => ['mode' => 'wishlist'],
        ]);

        $this->assertStringNotContainsString('background:#ef4444', $html);
    }

    public function test_compare_mode_reflects_the_real_session_compare_list(): void
    {
        $p1 = $this->product('c1');
        $p2 = $this->product('c2');
        session(['shop_compare' => [$p1->id, $p2->id]]);

        $html = $this->renderer()->renderBlock([
            'id' => 'ci1', 'type' => 'module:shop_wishlist_icon', 'props' => ['mode' => 'compare'],
        ]);

        $this->assertStringContainsString('>2<', $html);
        $this->assertStringContainsString(route('shop.compare'), $html);
    }

    public function test_style_full_shows_the_label_text(): void
    {
        // Wishlist mode (guest branch) needs a real session store, which a
        // bare renderBlock() call with no prior HTTP request doesn't have —
        // use compare mode here instead, which only reads the `session()`
        // helper directly; the style/label rendering path under test is
        // identical for both modes.
        session(['shop_compare' => []]);
        $html = $this->renderer()->renderBlock([
            'id' => 'wi3', 'type' => 'module:shop_wishlist_icon',
            'props' => ['mode' => 'compare', 'style' => 'full', 'label' => 'مورد علاقه من'],
        ]);

        $this->assertStringContainsString('مورد علاقه من', $html);
    }

    public function test_toggling_a_real_product_through_the_real_endpoint_changes_the_rendered_count(): void
    {
        $user = User::factory()->create();
        $product = $this->product('toggle-1');

        $before = $this->actingAs($user)->renderer()->renderBlock(['id' => 'wi4', 'type' => 'module:shop_wishlist_icon', 'props' => []]);
        $this->assertStringNotContainsString('background:#ef4444', $before);

        $this->actingAs($user)->post(route('shop.wishlist.toggle', $product))->assertRedirect();

        $after = $this->renderer()->renderBlock(['id' => 'wi5', 'type' => 'module:shop_wishlist_icon', 'props' => []]);
        $this->assertStringContainsString('>1<', $after);
    }
}

<?php

namespace Tests\Feature;

use App\Helpers\ShopCartHelper;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

class ShopModuleFeatureTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    /**
     * Wave 6.2 — License/installation seeding delegated to
     * `SeedsValidLicenseWithShop`. The legacy `domain => 'localhost'` seed
     * silently broke whenever local `APP_URL` pointed elsewhere (e.g.
     * `http://customer-site.test`); the trait derives the host from
     * `config('app.url')` so it stays correct across dev and CI.
     *
     * The previous tearDown `@unlink(installed.lock)` is removed because
     * the file is tracked in the repo; deleting it after every test run
     * corrupted developers' working trees. The trait now only creates the
     * file if missing.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();
        $this->seedValidLicense(['shop']);
    }

    public function test_shop_index_uses_effective_price_for_filter_and_sort(): void
    {
        Product::query()->create([
            'title' => 'محصول گران',
            'slug' => 'expensive',
            'price' => 500000,
            'sale_price' => 450000,
            'stock' => 2,
            'status' => Product::STATUS_PUBLISHED,
        ]);
        Product::query()->create([
            'title' => 'محصول اقتصادی',
            'slug' => 'budget',
            'price' => 300000,
            'sale_price' => 200000,
            'stock' => 5,
            'status' => Product::STATUS_PUBLISHED,
        ]);

        $response = $this->get(route('shop.index', [
            'min_price' => 150000,
            'max_price' => 250000,
            'sort' => 'price_asc',
        ]));

        $response->assertOk();
        $response->assertSee('محصول اقتصادی');
        $response->assertDontSee('محصول گران');
    }

    public function test_checkout_creates_order_decrements_stock_and_clears_cart(): void
    {
        $product = Product::query()->create([
            'title' => 'محصول سفارش',
            'slug' => 'order-product',
            'price' => 120000,
            'stock' => 3,
            'status' => Product::STATUS_PUBLISHED,
        ]);
        $lineKey = ShopCartHelper::lineKey($product->id, null);

        $this->withSession([
            'cart' => [
                $lineKey => [
                    'product_id' => $product->id,
                    'id' => $product->id,
                    'title' => $product->title,
                    'price' => $product->effective_price,
                    'quantity' => 2,
                    'image' => null,
                ],
            ],
        ])->post(route('shop.checkout'), [
            'customer_name'    => 'کاربر تست',
            'customer_mobile'  => '09120000000',
            'customer_phone'   => '09120000000',
            'customer_address' => 'آدرس تست',
            'note'             => 'بدون توضیح',
        ])->assertRedirect(route('shop.index'));

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseHas('orders', ['status' => 'pending']);
        $this->assertSame(1, (int) $product->fresh()->stock);
        $this->assertSame([], session('cart', []));
    }

    public function test_checkout_with_variant_decrements_variant_stock(): void
    {
        $product = Product::query()->create([
            'title' => 'با تنوع',
            'slug' => 'with-variant',
            'price' => 100000,
            'stock' => 0,
            'status' => Product::STATUS_PUBLISHED,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'label' => 'قرمز',
            'stock' => 4,
            'sort_order' => 0,
        ]);
        $lineKey = ShopCartHelper::lineKey($product->id, $variant->id);
        $this->withSession([
            'cart' => [
                $lineKey => [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'id' => $product->id,
                    'title' => $product->title,
                    'price' => $variant->effective_price,
                    'quantity' => 1,
                    'image' => null,
                ],
            ],
        ])->post(route('shop.checkout'), [
            'customer_name'    => 'تست',
            'customer_mobile'  => '09130000001',
            'customer_address' => 'آدرس',
        ])->assertRedirect(route('shop.index'));

        $this->assertSame(3, (int) $variant->fresh()->stock);
        $this->assertDatabaseHas('order_items', ['product_variant_id' => $variant->id]);
    }

    public function test_product_review_not_visible_until_approved(): void
    {
        $product = Product::query()->create([
            'title' => 'نظر',
            'slug' => 'rev-prod',
            'price' => 1000,
            'status' => Product::STATUS_PUBLISHED,
        ]);
        ProductReview::query()->create([
            'product_id' => $product->id,
            'rating' => 5,
            'body' => 'متن مخفی',
            'approved' => false,
        ]);
        $r = $this->get(route('shop.show', $product->slug));
        $r->assertOk();
        $r->assertDontSee('متن مخفی');
    }

    public function test_wishlist_toggle_persists_in_session(): void
    {
        $product = Product::query()->create([
            'title' => 'علاقه',
            'slug' => 'wish',
            'price' => 5000,
            'status' => Product::STATUS_PUBLISHED,
        ]);
        $this->post(route('shop.wishlist.toggle', $product))->assertRedirect();
        $this->get(route('shop.wishlist'))->assertSee('علاقه');
    }

    public function test_compare_session_stores_product_ids(): void
    {
        $p = Product::query()->create([
            'title' => 'مقایسه',
            'slug' => 'cmp',
            'price' => 1000,
            'status' => Product::STATUS_PUBLISHED,
        ]);
        $this->post(route('shop.compare.toggle', $p))->assertRedirect();
        $this->assertContains($p->id, session('shop_compare', []));
    }
}

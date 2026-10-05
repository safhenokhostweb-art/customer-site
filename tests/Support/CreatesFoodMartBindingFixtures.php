<?php

namespace Tests\Support;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Storage;

trait CreatesFoodMartBindingFixtures
{
    /** @return array<string,mixed> */
    protected function createFoodMartBindingFixtures(): array
    {
        Storage::fake('uploads');
        $this->writeFixturePng('foodmart-fixtures/fruit.png', [242, 185, 76]);
        $this->writeFixturePng('foodmart-fixtures/bakery.png', [191, 116, 73]);
        $this->writeFixturePng('foodmart-fixtures/drinks.png', [91, 170, 220]);
        $this->writeFixturePng('foodmart-fixtures/secondary.png', [118, 188, 122]);

        $fruit = ProductCategory::create(['name' => 'Fixture Fruit', 'slug' => 'fixture-fruit', 'order' => 1]);
        $bakery = ProductCategory::create(['name' => 'Fixture Bakery', 'slug' => 'fixture-bakery', 'order' => 2]);
        $empty = ProductCategory::create(['name' => 'Fixture Empty', 'slug' => 'fixture-empty', 'order' => 3]);

        $sale = $this->makeProduct($fruit, 'Fixture Sale Apple', 'fixture-sale-apple', 120000, 90000, 8, 'foodmart-fixtures/fruit.png', ['views_count' => 40, 'is_new' => true, 'order' => 2]);
        ProductImage::create(['product_id' => $sale->id, 'path' => 'foodmart-fixtures/secondary.png', 'sort_order' => 0]);
        $regular = $this->makeProduct($bakery, 'Fixture Plain Bread', 'fixture-plain-bread', 70000, null, 12, 'foodmart-fixtures/bakery.png', ['views_count' => 90, 'order' => 1]);
        ProductVariant::create(['product_id' => $regular->id, 'sku' => 'FIX-BREAD-S', 'label' => 'Small', 'price_override' => 65000, 'stock' => 4, 'sort_order' => 1, 'min_order_qty' => 2, 'max_order_qty' => 4]);
        $out = $this->makeProduct($fruit, 'Fixture Sold Out Juice', 'fixture-sold-out-juice', 50000, null, 0, 'foodmart-fixtures/drinks.png', ['views_count' => 4, 'order' => 3]);
        $long = $this->makeProduct($fruit, 'Fixture Product With An Intentionally Long Name For Responsive Card Verification', 'fixture-long-name', 88000, null, 3, null, ['views_count' => 2, 'order' => 4]);
        $draft = $this->makeProduct($fruit, 'Fixture Hidden Draft', 'fixture-hidden-draft', 1, null, 99, 'foodmart-fixtures/fruit.png', ['status' => Product::STATUS_DRAFT]);

        return compact('fruit', 'bakery', 'empty', 'sale', 'regular', 'out', 'long', 'draft');
    }

    private function makeProduct(ProductCategory $category, string $title, string $slug, float $price, ?float $salePrice, int $stock, ?string $image, array $extra = []): Product
    {
        return Product::create(array_merge([
            'title' => $title,
            'slug' => $slug,
            'category_id' => $category->id,
            'price' => $price,
            'sale_price' => $salePrice,
            'stock' => $stock,
            'image' => $image,
            'status' => Product::STATUS_PUBLISHED,
            'order' => 0,
            'views_count' => 0,
            'is_new' => false,
        ], $extra));
    }

    /** @param array{0:int,1:int,2:int} $rgb */
    private function writeFixturePng(string $path, array $rgb): void
    {
        $image = imagecreatetruecolor(96, 72);
        imagefilledrectangle($image, 0, 0, 95, 71, imagecolorallocate($image, ...$rgb));
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);
        Storage::disk('uploads')->put($path, $bytes);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\CurrencyHelper;
use App\Models\Product;
use App\Models\ProductBundle;
use App\Models\ProductImage;
use App\Models\ProductTag;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->orderBy('order')->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = \App\Models\ProductCategory::orderBy('order')->get();
        $tags = ProductTag::orderBy('name')->get();
        $attributes = \App\Models\ProductAttribute::with('values')->orderBy('sort_order')->get();
        $allProducts = Product::orderBy('title')->get(['id', 'title']);
        $relatedIds = ['related' => [], 'upsell' => [], 'cross_sell' => []];
        $product = new Product([
            'status' => Product::STATUS_PUBLISHED,
            'min_order_qty' => 1,
            'price' => 0,
        ]);

        return view('admin.products.create', compact('categories', 'tags', 'attributes', 'allProducts', 'relatedIds', 'product'));
    }

    public function store(Request $request)
    {
        $data = $this->validateProduct($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        $data['order'] = Product::max('order') + 1;

        $product = DB::transaction(function () use ($data, $request) {
            $p = Product::create($data);
            $this->syncExtended($p, $request);

            return $p;
        });

        return redirect()->route('admin.products.edit', $product)->with('success', 'محصول ذخیره شد. می‌توانید تنوع و گالری را تکمیل کنید.');
    }

    public function edit(Product $product)
    {
        $product->load(['variants', 'images', 'tags', 'attributeValues', 'bundleLines.child']);
        $categories = \App\Models\ProductCategory::orderBy('order')->get();
        $tags = ProductTag::orderBy('name')->get();
        $attributes = \App\Models\ProductAttribute::with('values')->orderBy('sort_order')->get();
        $allProducts = Product::orderBy('title')->where('id', '!=', $product->id)->get(['id', 'title']);

        $relatedIds = [
            'related' => DB::table('product_related')->where('product_id', $product->id)->where('relation_type', 'related')->pluck('related_product_id')->all(),
            'upsell' => DB::table('product_related')->where('product_id', $product->id)->where('relation_type', 'upsell')->pluck('related_product_id')->all(),
            'cross_sell' => DB::table('product_related')->where('product_id', $product->id)->where('relation_type', 'cross_sell')->pluck('related_product_id')->all(),
        ];

        return view('admin.products.edit', compact('product', 'categories', 'tags', 'attributes', 'allProducts', 'relatedIds'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateProduct($request);
        DB::transaction(function () use ($product, $data, $request) {
            $product->update($data);
            $this->syncExtended($product, $request);
        });

        return redirect()->route('admin.products.index')->with('success', 'محصول به‌روزرسانی شد.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'محصول حذف شد.');
    }

    private function syncExtended(Product $product, Request $request): void
    {
        $this->syncTags($product, $request);
        $this->syncAttributeValues($product, $request);
        $this->syncImages($product, $request);
        $this->syncVariants($product, $request);
        $this->syncRelated($product, $request);
        $this->syncBundles($product, $request);
    }

    private function syncTags(Product $product, Request $request): void
    {
        $ids = array_filter(array_map('intval', (array) $request->input('tag_ids', [])));
        foreach (preg_split('/\s*,\s*/', (string) $request->input('new_tags', ''), -1, PREG_SPLIT_NO_EMPTY) as $name) {
            $slug = Str::slug($name);
            if ($slug === '') {
                continue;
            }
            $tag = ProductTag::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'tag_type' => 'label']
            );
            $ids[] = $tag->id;
        }
        $product->tags()->sync(array_unique($ids));
    }

    private function syncAttributeValues(Product $product, Request $request): void
    {
        $ids = array_filter(array_map('intval', (array) $request->input('attribute_value_ids', [])));
        $product->attributeValues()->sync($ids);
    }

    private function syncImages(Product $product, Request $request): void
    {
        $paths = array_filter(array_map('trim', (array) $request->input('gallery_paths', [])));
        if ($paths === [] && $request->filled('gallery_paths_text')) {
            $paths = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $request->input('gallery_paths_text')))));
        }
        $product->images()->delete();
        foreach ($paths as $i => $path) {
            if ($path === '') {
                continue;
            }
            ProductImage::query()->create([
                'product_id' => $product->id,
                'path' => $path,
                'sort_order' => $i,
            ]);
        }
    }

    private function syncVariants(Product $product, Request $request): void
    {
        $rows = (array) $request->input('variants', []);
        $keep = [];
        foreach ($rows as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            $label = trim((string) ($row['label'] ?? ''));
            $sku = trim((string) ($row['sku'] ?? ''));
            if ($label === '' && $sku === '') {
                continue;
            }
            $payload = [
                'sku' => $sku !== '' ? $sku : null,
                'label' => $label !== '' ? $label : null,
                'price_override' => isset($row['price_override']) && $row['price_override'] !== ''
                    ? CurrencyHelper::toStorageValue($row['price_override']) : null,
                'sale_price' => isset($row['sale_price']) && $row['sale_price'] !== ''
                    ? CurrencyHelper::toStorageValue($row['sale_price']) : null,
                'stock' => array_key_exists('stock', $row) && $row['stock'] !== '' ? (int) $row['stock'] : null,
                'image' => trim((string) ($row['image'] ?? '')) ?: null,
                'sort_order' => (int) ($row['sort_order'] ?? $i),
                'min_order_qty' => max(1, (int) ($row['min_order_qty'] ?? 1)),
                'max_order_qty' => isset($row['max_order_qty']) && $row['max_order_qty'] !== '' ? (int) $row['max_order_qty'] : null,
                'allow_backorder' => ! empty($row['allow_backorder']),
            ];
            $vid = isset($row['id']) ? (int) $row['id'] : 0;
            if ($vid > 0) {
                $v = ProductVariant::query()->where('product_id', $product->id)->whereKey($vid)->first();
                if ($v) {
                    $v->update($payload);
                    $keep[] = $v->id;
                }
            } else {
                $keep[] = ProductVariant::query()->create(array_merge($payload, ['product_id' => $product->id]))->id;
            }
        }
        ProductVariant::query()->where('product_id', $product->id)->whereNotIn('id', $keep)->delete();
    }

    private function syncRelated(Product $product, Request $request): void
    {
        DB::table('product_related')->where('product_id', $product->id)->delete();
        foreach (['related' => 'related', 'upsell' => 'upsell', 'cross_sell' => 'cross_sell'] as $key => $type) {
            $ids = array_filter(array_map('intval', (array) $request->input('related_' . $key, [])));
            foreach ($ids as $rid) {
                if ($rid === $product->id) {
                    continue;
                }
                DB::table('product_related')->insert([
                    'product_id' => $product->id,
                    'related_product_id' => $rid,
                    'relation_type' => $type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function syncBundles(Product $product, Request $request): void
    {
        ProductBundle::query()->where('parent_product_id', $product->id)->delete();
        if (! $product->is_bundle) {
            return;
        }
        $lines = (array) $request->input('bundle_lines', []);
        foreach ($lines as $row) {
            if (! is_array($row)) {
                continue;
            }
            $cid = (int) ($row['child_product_id'] ?? 0);
            $qty = max(1, (int) ($row['quantity'] ?? 1));
            if ($cid < 1 || $cid === $product->id) {
                continue;
            }
            ProductBundle::query()->create([
                'parent_product_id' => $product->id,
                'child_product_id' => $cid,
                'quantity' => $qty,
            ]);
        }
    }

    private function validateProduct(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published',
            'category_id' => 'nullable|exists:product_categories,id',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'sku' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'order' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|string|max:500',
            'og_image' => 'nullable|string|max:500',
            'video_url' => 'nullable|string|max:500',
            'shipping_text' => 'nullable|string',
            'returns_text' => 'nullable|string',
            'faq_items' => 'nullable|array',
            'faq_items.*.q' => 'nullable|string|max:2000',
            'faq_items.*.a' => 'nullable|string',
            'size_chart_url' => 'nullable|string|max:500',
            'size_chart_html' => 'nullable|string',
            'is_new'       => 'nullable|boolean',
            'early_access' => 'nullable|boolean',
            'sale_ends_at' => 'nullable|date',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'warranty_text' => 'nullable|string',
            'guarantee_text' => 'nullable|string',
            'allow_backorder' => 'nullable|boolean',
            'min_order_qty' => 'nullable|integer|min:1',
            'max_order_qty' => 'nullable|integer|min:1',
            'is_bundle' => 'nullable|boolean',
            'bundle_pricing' => 'nullable|in:sum_discount,fixed',
            'bundle_fixed_price' => 'nullable|numeric|min:0',
            'bundle_discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $data['price'] = CurrencyHelper::toStorageValue($data['price']);
        if (isset($data['sale_price']) && $data['sale_price'] !== '' && $data['sale_price'] !== null) {
            $data['sale_price'] = CurrencyHelper::toStorageValue($data['sale_price']);
        } else {
            $data['sale_price'] = null;
        }
        if (isset($data['bundle_fixed_price']) && $data['bundle_fixed_price'] !== '' && $data['bundle_fixed_price'] !== null) {
            $data['bundle_fixed_price'] = CurrencyHelper::toStorageValue($data['bundle_fixed_price']);
        } else {
            $data['bundle_fixed_price'] = null;
        }
        $data['is_new']       = $request->boolean('is_new');
        $data['early_access'] = $request->boolean('early_access');
        $data['allow_backorder'] = $request->boolean('allow_backorder');
        $data['is_bundle'] = $request->boolean('is_bundle');
        $data['min_order_qty'] = max(1, (int) ($data['min_order_qty'] ?? 1));
        $data['faq'] = null;
        $faqItems = (array) ($request->input('faq_items', []));
        $faq = [];
        foreach ($faqItems as $row) {
            if (! is_array($row)) {
                continue;
            }
            $q = trim((string) ($row['q'] ?? ''));
            $a = trim((string) ($row['a'] ?? ''));
            if ($q === '' && $a === '') {
                continue;
            }
            $faq[] = ['q' => $q, 'a' => $a];
        }
        if (count($faq) > 0) {
            $data['faq'] = $faq;
        }
        unset($data['faq_items']);

        $data['sale_ends_at'] = ! empty($data['sale_ends_at']) ? $data['sale_ends_at'] : null;

        return $data;
    }
}

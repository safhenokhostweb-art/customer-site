<?php

namespace App\Http\Controllers\Front;

use App\Helpers\JsonLd;
use App\Helpers\ShopCartHelper;
use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductStockNotification;
use App\Models\ProductVariant;
use App\Models\ShopCartEvent;
use App\Services\CaptchaService;
use App\Services\MembershipBenefitService;
use App\Services\ShopWishlistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        if (! SiteHelper::shopEnabled()) {
            abort(404);
        }
        $query = Product::active()->with(['category', 'variants', 'tags', 'attributeValues.attribute']);

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($qb) => $qb->where('slug', $request->category));
        }
        if ($request->filled('tag')) {
            $query->whereHas('tags', fn ($qb) => $qb->where('slug', $request->tag));
        }
        foreach ($request->input('attr', []) as $attrSlug => $valSlug) {
            if ($attrSlug === '' || $valSlug === '' || $valSlug === null) {
                continue;
            }
            $query->whereHas('attributeValues', function ($q) use ($attrSlug, $valSlug) {
                $q->where(function ($qq) use ($valSlug) {
                    $qq->where('slug', $valSlug)->orWhere('value', $valSlug);
                })->whereHas('attribute', fn ($a) => $a->where('slug', $attrSlug));
            });
        }
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($qb) use ($search) {
                $qb->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $minPrice = $request->filled('min_price') ? max(0, (float) $request->input('min_price')) : null;
        $maxPrice = $request->filled('max_price') ? max(0, (float) $request->input('max_price')) : null;
        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }
        if ($minPrice !== null) {
            $query->where(function ($q) use ($minPrice) {
                $q->whereRaw('EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = products.id AND COALESCE(pv.sale_price, COALESCE(pv.price_override, products.price)) >= ?)', [$minPrice])
                    ->orWhere(function ($q2) use ($minPrice) {
                        $q2->whereRaw('NOT EXISTS (SELECT 1 FROM product_variants pv2 WHERE pv2.product_id = products.id)')
                            ->whereRaw('COALESCE(sale_price, price) >= ?', [$minPrice]);
                    });
            });
        }
        if ($maxPrice !== null) {
            $query->where(function ($q) use ($maxPrice) {
                $q->whereRaw('EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = products.id AND COALESCE(pv.sale_price, COALESCE(pv.price_override, products.price)) <= ?)', [$maxPrice])
                    ->orWhere(function ($q2) use ($maxPrice) {
                        $q2->whereRaw('NOT EXISTS (SELECT 1 FROM product_variants pv2 WHERE pv2.product_id = products.id)')
                            ->whereRaw('COALESCE(sale_price, price) <= ?', [$maxPrice]);
                    });
            });
        }

        $sort = (string) $request->input('sort', 'recommended');
        $sort = in_array($sort, ['recommended', 'newest', 'price_asc', 'price_desc'], true) ? $sort : 'recommended';
        if ($sort === 'price_asc') {
            $query->orderByRaw('(
                SELECT MIN(COALESCE(pv.sale_price, COALESCE(pv.price_override, products.price)))
                FROM product_variants pv WHERE pv.product_id = products.id
            ) ASC')
                ->orderByRaw('CASE WHEN EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = products.id) THEN 1 ELSE 0 END ASC')
                ->orderByRaw('COALESCE(sale_price, price) ASC');
        } elseif ($sort === 'price_desc') {
            $query->orderByRaw('(
                SELECT MIN(COALESCE(pv.sale_price, COALESCE(pv.price_override, products.price)))
                FROM product_variants pv WHERE pv.product_id = products.id
            ) DESC')
                ->orderByRaw('CASE WHEN EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = products.id) THEN 1 ELSE 0 END ASC')
                ->orderByRaw('COALESCE(sale_price, price) DESC');
        } elseif ($sort === 'newest') {
            $query->orderByDesc('id');
        } else {
            $query->orderBy('order')->orderByDesc('id');
        }

        $products = $query->paginate(12)->withQueryString();
        $categories = \App\Models\ProductCategory::orderBy('order')->get();
        $attributeFilters = \App\Models\ProductAttribute::query()->where('filterable', true)->orderBy('sort_order')->with('values')->get();
        $tags = \App\Models\ProductTag::orderBy('name')->get();
        $pages = Page::published()->menu()->get();
        $wishlist = app(ShopWishlistService::class);

        return view(SiteHelper::view('shop.index'), compact('products', 'categories', 'pages', 'sort', 'attributeFilters', 'tags', 'wishlist'));
    }

    public function show(Request $request, string $slug)
    {
        if (! SiteHelper::shopEnabled()) {
            abort(404);
        }
        $product = Product::active()
            ->with([
                'category', 'variants', 'images', 'tags', 'attributeValues.attribute',
                'bundleLines.child',
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        $sessionKey = 'shop_viewed_' . $product->id;
        if (! $request->session()->has($sessionKey)) {
            $product->increment('views_count');
            $request->session()->put($sessionKey, true);
        }

        $related = $product->relatedLinked('related')->active()->with('variants')->orderBy('order')->take(8)->get();
        if ($related->isEmpty()) {
            $related = Product::active()
                ->with('variants')
                ->where('id', '!=', $product->id)
                ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
                ->orderBy('order')
                ->take(8)
                ->get();
        }
        $upsells = $product->relatedLinked('upsell')->active()->with('variants')->orderBy('order')->take(6)->get();
        $crossSells = $product->relatedLinked('cross_sell')->active()->with('variants')->orderBy('order')->take(6)->get();

        $reviewAvg = ProductReview::query()->where('product_id', $product->id)->approved()->avg('rating');
        $reviews = ProductReview::query()->where('product_id', $product->id)->approved()->latest()->take(20)->get();
        $reviewCount = ProductReview::query()->where('product_id', $product->id)->approved()->count();
        $jsonLdProduct = JsonLd::product(
            $product,
            (float) $product->display_price,
            $this->productSchemaAvailability($product),
            $reviewAvg !== null ? (float) $reviewAvg : null,
            $reviewCount
        );

        $pages = Page::published()->menu()->get();
        $wishlist = app(ShopWishlistService::class);

        return view(SiteHelper::view('shop.show'), compact(
            'product', 'pages', 'related', 'upsells', 'crossSells', 'reviews', 'reviewAvg', 'wishlist', 'jsonLdProduct'
        ));
    }

    private function productSchemaAvailability(Product $product): string
    {
        if ($product->allow_backorder) {
            return 'https://schema.org/PreOrder';
        }
        if ($product->usesVariants()) {
            $any = $product->variants->contains(function (ProductVariant $v) {
                if ($v->allow_backorder) {
                    return true;
                }
                if ($v->stock === null) {
                    return true;
                }

                return (int) $v->stock > 0;
            });

            return $any ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
        }
        if ($product->stock === null || (int) $product->stock > 0) {
            return 'https://schema.org/InStock';
        }

        return 'https://schema.org/OutOfStock';
    }

    public function addToCart(Request $request)
    {
        if (! SiteHelper::shopEnabled()) {
            abort(404);
        }
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'nullable|integer|min:1',
        ]);
        $product = Product::active()->with(['variants', 'bundleLines.child'])->findOrFail($request->product_id);
        $variantId = $request->filled('product_variant_id') ? (int) $request->product_variant_id : null;
        $variant = null;
        if ($variantId) {
            $variant = $product->variants->firstWhere('id', $variantId);
            if (! $variant) {
                return back()->with('error', 'تنوع انتخاب‌شده معتبر نیست.');
            }
        } elseif ($product->usesVariants()) {
            return back()->with('error', 'لطفاً تنوع کالا را انتخاب کنید.');
        }

        $qty = (int) ($request->quantity ?? 1);
        $minQ = $variant ? max(1, (int) $variant->min_order_qty) : max(1, (int) $product->min_order_qty);
        $maxQ = $variant ? $variant->max_order_qty : $product->max_order_qty;
        if ($qty < $minQ) {
            return back()->with('error', 'حداقل تعداد سفارش ' . $minQ . ' است.');
        }
        if ($maxQ !== null && $qty > (int) $maxQ) {
            return back()->with('error', 'حداکثر تعداد سفارش ' . $maxQ . ' است.');
        }

        $cart = ShopCartHelper::normalizeCart(session()->get('cart', []));
        $lineKey = ShopCartHelper::lineKey($product->id, $variantId);
        $currentQty = (int) ($cart[$lineKey]['quantity'] ?? 0);
        $nextQty = $currentQty + $qty;

        if ($product->is_bundle) {
            $stockOk = $this->validateBundleStock($product, $nextQty);
            if ($stockOk !== true) {
                return back()->with('error', $stockOk);
            }
            $unitPrice = $this->bundleUnitPrice($product);
        } else {
            if (! $this->canFulfillQty($product, $variant, $nextQty)) {
                return back()->with('error', 'موجودی کافی نیست.');
            }
            $unitPrice = $variant ? $variant->effective_price : $product->effective_price;
        }

        $image = $variant && $variant->image ? $variant->image : $product->primaryImagePath();
        $title = $product->title . ($variant && $variant->label ? ' — ' . $variant->label : '');

        $cart[$lineKey] = [
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'id' => $product->id,
            'title' => $title,
            'price' => $unitPrice,
            'quantity' => $nextQty,
            'image' => $image,
            'is_bundle' => $product->is_bundle,
        ];
        session()->put('cart', $cart);

        ShopCartEvent::query()->create([
            'product_id' => $product->id,
            'event_type' => 'add_to_cart',
            'session_id' => substr(session()->getId(), 0, 64),
        ]);

        return back()->with('success', 'به سبد اضافه شد.');
    }

    protected function canFulfillQty(Product $product, ?ProductVariant $variant, int $qty): bool
    {
        $allowBack = $product->allow_backorder || ($variant && $variant->allow_backorder);
        if ($allowBack) {
            return true;
        }
        if ($variant) {
            if ($variant->stock === null) {
                return true;
            }

            return (int) $variant->stock >= $qty;
        }
        if ($product->stock === null) {
            return true;
        }

        return (int) $product->stock >= $qty;
    }

    /**
     * @return true|string error message
     */
    protected function validateBundleStock(Product $bundleProduct, int $lineQty): bool|string
    {
        foreach ($bundleProduct->bundleLines as $line) {
            $child = $line->child;
            if (! $child || $child->status !== Product::STATUS_PUBLISHED) {
                return 'یکی از اجزای بسته در دسترس نیست.';
            }
            $need = (int) $line->quantity * $lineQty;
            if ($child->allow_backorder) {
                continue;
            }
            if ($child->stock === null) {
                continue;
            }
            if ((int) $child->stock < $need) {
                return 'موجودی بسته کافی نیست (' . $child->title . ').';
            }
        }

        return true;
    }

    protected function bundleUnitPrice(Product $product): float
    {
        if ($product->bundle_pricing === 'fixed' && $product->bundle_fixed_price !== null) {
            return (float) $product->bundle_fixed_price;
        }
        $sum = 0;
        foreach ($product->bundleLines as $line) {
            $child = $line->child;
            if (! $child) {
                continue;
            }
            $sum += $child->effective_price * (int) $line->quantity;
        }
        $pct = (float) ($product->bundle_discount_percent ?? 0);
        if ($pct > 0) {
            $sum *= max(0, 1 - ($pct / 100));
        }

        return (float) $sum;
    }

    public function cart()
    {
        if (! SiteHelper::shopEnabled()) {
            abort(404);
        }
        $cart     = ShopCartHelper::normalizeCart(session()->get('cart', []));
        session()->put('cart', $cart);
        $pages    = Page::published()->menu()->get();
        $subtotal = (int) array_sum(array_map(
            fn ($item) => (int) $item['price'] * (int) $item['quantity'],
            $cart
        ));
        $user       = auth()->user();
        $membership = app(MembershipBenefitService::class)
            ->calculate($user, $cart, $subtotal);

        $baseShipping = (int) config('site.shop_shipping_cost', 0);
        $shippingCost = $membership->freeShipping ? 0 : $baseShipping;

        $pointsBalance  = 0;
        $maxSpendPoints = 0;
        if ($user) {
            $pointsSvc      = app(\App\Services\MembershipPointsService::class);
            $pointsBalance  = $pointsSvc->getBalance($user);
            $maxSpendPoints = $pointsSvc->maxSpendablePoints($user, $subtotal);
        }

        return view(SiteHelper::view('shop.cart'), compact(
            'cart', 'pages', 'membership', 'subtotal', 'shippingCost',
            'pointsBalance', 'maxSpendPoints'
        ));
    }

    public function updateCart(Request $request)
    {
        if (! SiteHelper::shopEnabled()) {
            abort(404);
        }
        $request->validate([
            'line_key' => 'required|string',
            'quantity' => 'required|integer|min:0',
        ]);
        $cart = ShopCartHelper::normalizeCart(session()->get('cart', []));
        $lineKey = $request->line_key;
        $qty = (int) $request->quantity;
        if (! isset($cart[$lineKey])) {
            return back()->with('error', 'ردیف سبد یافت نشد.');
        }
        if ($qty <= 0) {
            unset($cart[$lineKey]);
        } else {
            [$pid, $vid] = ShopCartHelper::parseLineKey($lineKey);
            $product = Product::active()->with(['variants', 'bundleLines.child'])->find($pid);
            if (! $product) {
                unset($cart[$lineKey]);
            } else {
                $variant = $vid ? $product->variants->firstWhere('id', $vid) : null;
                if ($vid && ! $variant) {
                    return back()->with('error', 'تنوع نامعتبر است.');
                }
                if (! $vid && $product->usesVariants()) {
                    return back()->with('error', 'این کالا نیاز به انتخاب تنوع دارد.');
                }
                $minQ = $variant ? max(1, (int) $variant->min_order_qty) : max(1, (int) $product->min_order_qty);
                $maxQ = $variant ? $variant->max_order_qty : $product->max_order_qty;
                if ($qty < $minQ) {
                    return back()->with('error', 'حداقل تعداد سفارش ' . $minQ . ' است.');
                }
                if ($maxQ !== null && $qty > (int) $maxQ) {
                    return back()->with('error', 'حداکثر تعداد سفارش ' . $maxQ . ' است.');
                }
                if ($product->is_bundle) {
                    $stockOk = $this->validateBundleStock($product, $qty);
                    if ($stockOk !== true) {
                        return back()->with('error', $stockOk);
                    }
                } elseif (! $this->canFulfillQty($product, $variant, $qty)) {
                    return back()->with('error', 'تعداد درخواستی از موجودی بیشتر است.');
                }
                $cart[$lineKey]['quantity'] = $qty;
            }
        }
        session()->put('cart', $cart);

        return back()->with('success', 'سبد به‌روزرسانی شد.');
    }

    public function removeFromCart(Request $request)
    {
        if (! SiteHelper::shopEnabled()) {
            abort(404);
        }
        $request->validate(['line_key' => 'required|string']);
        $cart = ShopCartHelper::normalizeCart(session()->get('cart', []));
        unset($cart[$request->line_key]);
        session()->put('cart', $cart);

        return back()->with('success', 'محصول از سبد حذف شد.');
    }

    public function checkout(Request $request)
    {
        if (! SiteHelper::shopEnabled()) {
            abort(404);
        }
        $cart = ShopCartHelper::normalizeCart(session()->get('cart', []));
        if (empty($cart)) {
            return redirect()->route('shop.cart')->with('error', 'سبد خرید خالی است.');
        }
        // Phase 5 / Wave 5.3 / P1-5: کپچا قبل از validate تا اگر معتبر نیست، order نسازد.
        // برای کاربر authenticated همیشه true برمی‌گردد (bypass).
        if (! app(CaptchaService::class)->validateShopCaptcha($request)) {
            return back()
                ->withInput()
                ->withErrors(['captcha_answer' => 'پاسخ کپچا نادرست است. لطفاً دوباره تلاش کنید.']);
        }
        $request->validate([
            'customer_name'    => 'required|string|max:255',
            'customer_mobile'  => 'required|string|max:20',
            'customer_phone'   => 'nullable|string|max:50',
            'customer_address' => 'required|string',
            'note'             => 'nullable|string|max:1000',
            'coupon_code'      => 'nullable|string|max:50',
            'points_to_spend'  => 'nullable|integer|min:0',
        ]);
        try {
            $order = DB::transaction(function () use ($cart, $request) {
                $subtotal = 0;
                $items = [];

                // Wave 6.1 / P1-S1 — stable lock ordering. Collect every row
                // that affects stock (parent products, requested variants,
                // bundle child products) and lock them ascending by primary
                // key BEFORE any validation. This eliminates the race where
                // bundle/variant stock could be approved against stale data
                // and then oversold by a parallel checkout.
                $productIds = [];
                $variantIds = [];
                foreach ($cart as $lineKey => $item) {
                    [$pid, $vid] = ShopCartHelper::parseLineKey($lineKey);
                    if ($pid > 0) {
                        $productIds[$pid] = true;
                    }
                    if ($vid > 0) {
                        $variantIds[$vid] = true;
                    }
                }
                $parentIds = array_keys($productIds);
                $childIds = [];
                if ($parentIds) {
                    $childIds = \App\Models\ProductBundle::query()
                        ->whereIn('parent_product_id', $parentIds)
                        ->pluck('child_product_id')
                        ->all();
                }
                $allProductIds = array_values(array_unique(array_merge($parentIds, $childIds)));
                sort($allProductIds);
                $allVariantIds = array_keys($variantIds);
                sort($allVariantIds);

                if ($allProductIds) {
                    Product::query()->whereIn('id', $allProductIds)->lockForUpdate()->get();
                }
                if ($allVariantIds) {
                    ProductVariant::query()->whereIn('id', $allVariantIds)->lockForUpdate()->get();
                }

                foreach ($cart as $lineKey => $item) {
                    [$pid, $vid] = ShopCartHelper::parseLineKey($lineKey);
                    $product = Product::query()
                        ->where('status', Product::STATUS_PUBLISHED)
                        ->whereKey($pid)
                        ->with(['variants', 'bundleLines.child'])
                        ->first();

                    if (! $product) {
                        throw new \RuntimeException('یکی از محصولات در دسترس نیست.');
                    }

                    $qty = (int) $item['quantity'];
                    $variant = $vid ? $product->variants->firstWhere('id', $vid) : null;
                    if ($vid && ! $variant) {
                        throw new \RuntimeException('تنوع یکی از کالاها دیگر موجود نیست.');
                    }
                    if (! $vid && $product->usesVariants()) {
                        throw new \RuntimeException('سبد نیاز به به‌روزرسانی دارد (تنوع کالا).');
                    }

                    if ($product->is_bundle) {
                        $stockOk = $this->validateBundleStock($product, $qty);
                        if ($stockOk !== true) {
                            throw new \RuntimeException((string) $stockOk);
                        }
                        $unitPrice = $this->bundleUnitPrice($product);
                    } else {
                        if (! $this->canFulfillQty($product, $variant, $qty)) {
                            throw new \RuntimeException('موجودی برخی کالاها تغییر کرده است. سبد را بروزرسانی کنید.');
                        }
                        $unitPrice = $variant ? $variant->effective_price : $product->effective_price;
                    }

                    $total = $unitPrice * $qty;
                    $subtotal += $total;
                    $title = $product->title . ($variant && $variant->label ? ' — ' . $variant->label : '');
                    $items[] = [
                        'product' => $product,
                        'variant' => $variant,
                        'payload' => [
                            'product_id' => $product->id,
                            'product_variant_id' => $variant?->id,
                            'variant_label' => $variant?->label,
                            'product_title' => $title,
                            'price' => $unitPrice,
                            'quantity' => $qty,
                            'total' => $total,
                        ],
                    ];
                }

                $user = auth()->user();

                // ── 1. Membership discount + shipping ────────────────────────
                $membership = app(MembershipBenefitService::class)
                    ->calculate($user, $cart, (int) $subtotal);
                $membershipDiscount = $membership->discountAmount;
                $shippingCost = $membership->freeShipping
                    ? 0
                    : (int) config('site.shop_shipping_cost', 0);

                // ── 2. Coupon discount (applied after membership) ─────────────
                $coupon = null;
                $discountAmount = 0;
                $baseForCoupon  = max(0, $subtotal - $membershipDiscount);
                if ($request->filled('coupon_code') && SiteHelper::moduleEnabled('coupon')) {
                    $coupon = Coupon::where('code', strtoupper(trim($request->coupon_code)))->lockForUpdate()->first();
                    if (! $coupon) {
                        throw new \InvalidArgumentException('کد تخفیف نامعتبر است.');
                    }
                    $userId = $user?->id;
                    if (! $coupon->isValidForAmount((int) $subtotal, $cart, $userId)) {
                        throw new \InvalidArgumentException($coupon->min_order_amount
                            ? 'حداقل مبلغ سفارش برای این کوپن ' . number_format($coupon->min_order_amount) . ' تومان است.'
                            : 'این کوپن منقضی شده، به حد استفاده رسیده، یا برای سبد فعلی شما اعتبار ندارد.');
                    }
                    $discountAmount = $coupon->calculateDiscount((int) $baseForCoupon);
                }

                // ── 3. Points discount ────────────────────────────────────────
                $pointsService   = app(\App\Services\MembershipPointsService::class);
                $pointsToSpend   = 0;
                $pointsDiscount  = 0;
                $requestedPoints = (int) ($request->input('points_to_spend', 0));

                if ($requestedPoints > 0 && $user) {
                    $maxSpendable = $pointsService->maxSpendablePoints($user, (int) $subtotal);
                    $pointsToSpend = min($requestedPoints, $maxSpendable);
                    $pointsDiscount = $pointsService->pointsToToman($pointsToSpend);
                }

                $orderTotal = max(0, $subtotal + $shippingCost - $membershipDiscount - $discountAmount - $pointsDiscount);

                // ── 4. Calculate points to earn from this purchase ────────────
                $pointsToEarn = $user ? $pointsService->calculatePurchasePoints($user, (int) $subtotal) : 0;

                $order = Order::create([
                    'number'                    => Order::generateNumber(),
                    'user_id'                   => $user?->id,
                    'customer_name'             => $request->customer_name,
                    'customer_mobile'           => $request->customer_mobile,
                    'customer_phone'            => $request->customer_phone,
                    'customer_address'          => $request->customer_address,
                    'note'                      => $request->note,
                    'subtotal'                  => $subtotal,
                    'shipping_cost'             => $shippingCost,
                    'total'                     => $orderTotal,
                    'discount_amount'           => $discountAmount,
                    'membership_discount_amount'=> $membershipDiscount,
                    'membership_plan_name'      => $membership->planName ?: null,
                    'points_discount_amount'    => $pointsDiscount,
                    'points_spent'              => $pointsToSpend,
                    'points_earned'             => $pointsToEarn,
                    'coupon_id'                 => $coupon?->id,
                    'status'                    => 'pending',
                ]);

                // ── 5. Spend points (inside transaction — atomic) ─────────────
                if ($pointsToSpend > 0 && $user) {
                    $pointsService->spendAtCheckout($user, $pointsToSpend, $order);
                }

                foreach ($items as $item) {
                    $order->items()->create($item['payload']);
                    /** @var Product $product */
                    $product = $item['product'];
                    $variant = $item['variant'];
                    $qty = (int) $item['payload']['quantity'];
                    if ($product->is_bundle) {
                        foreach ($product->bundleLines as $line) {
                            $child = Product::query()->whereKey($line->child_product_id)->lockForUpdate()->first();
                            if ($child && $child->stock !== null && ! $child->allow_backorder) {
                                $child->decrement('stock', (int) $line->quantity * $qty);
                            }
                        }
                    } elseif ($variant) {
                        $v = ProductVariant::query()->whereKey($variant->id)->lockForUpdate()->first();
                        if ($v && $v->stock !== null && ! $v->allow_backorder && ! $product->allow_backorder) {
                            $v->decrement('stock', $qty);
                        }
                    } elseif ($product->stock !== null && ! $product->allow_backorder) {
                        $product->decrement('stock', $qty);
                    }
                }

                if ($coupon) {
                    $coupon->incrementUsage();
                }

                return $order;
            }, 3);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return redirect()->route('shop.cart')->withInput()->with('error', $e->getMessage());
        }

        session()->forget('cart');

        // ── Earn points for this purchase (outside transaction) ───────────
        if (auth()->check() && $order->points_earned > 0) {
            try {
                app(\App\Services\MembershipPointsService::class)
                    ->earnFromPurchase(auth()->user(), $order);
            } catch (\Throwable) {
                // امتیاز کسب نشد اما سفارش ثبت شد — بدون خطا
            }
        }

        $successMsg = 'سفارش شما ثبت شد. شماره: ' . $order->number;
        if ($order->points_earned > 0) {
            $successMsg .= ' | ' . \App\Helpers\NumberHelper::formatFa($order->points_earned) . ' امتیاز به حسابتان اضافه شد 🎉';
        }

        return redirect()->route('shop.index')->with('success', $successMsg);
    }

    public function wishlist(Request $request)
    {
        if (! SiteHelper::shopEnabled()) {
            abort(404);
        }
        $svc = app(ShopWishlistService::class);
        $w = $svc->wishlist();
        $items = $w->items()->with(['product.category', 'product.variants'])->get();
        $pages = Page::published()->menu()->get();

        return view(SiteHelper::view('shop.wishlist'), compact('items', 'pages'));
    }

    public function wishlistToggle(Request $request, Product $product)
    {
        if (! SiteHelper::shopEnabled()) {
            abort(404);
        }
        $product = Product::active()->whereKey($product->id)->firstOrFail();
        $on = app(ShopWishlistService::class)->toggle($product);

        return back()->with('success', $on ? 'به علاقه‌مندی‌ها اضافه شد.' : 'از علاقه‌مندی‌ها حذف شد.');
    }

    public function compare(Request $request)
    {
        if (! SiteHelper::shopEnabled()) {
            abort(404);
        }
        $ids = array_slice(array_unique(array_map('intval', (array) session('shop_compare', []))), 0, 4);
        $loaded = Product::active()->with(['attributeValues.attribute', 'variants', 'category'])->whereIn('id', $ids)->get()->keyBy('id');
        $products = collect($ids)->map(fn ($id) => $loaded->get($id))->filter();
        $pages = Page::published()->menu()->get();

        return view(SiteHelper::view('shop.compare'), compact('products', 'pages', 'ids'));
    }

    public function compareToggle(Request $request, Product $product)
    {
        if (! SiteHelper::shopEnabled()) {
            abort(404);
        }
        $product = Product::active()->whereKey($product->id)->firstOrFail();
        $ids = (array) session('shop_compare', []);
        $key = array_search($product->id, $ids, true);
        if ($key !== false) {
            unset($ids[$key]);
            $ids = array_values($ids);
        } else {
            $ids[] = $product->id;
            $ids = array_slice(array_unique($ids), -4);
        }
        session(['shop_compare' => $ids]);

        return back()->with('success', 'لیست مقایسه به‌روز شد.');
    }

    public function stockNotify(Request $request)
    {
        if (! SiteHelper::shopEnabled()) {
            abort(404);
        }
        // Phase 5 / Wave 5.3 / P1-5: کپچا برای جلوگیری از spam درخواست‌های موجودی.
        if (! app(CaptchaService::class)->validateShopCaptcha($request)) {
            return back()
                ->withInput()
                ->withErrors(['captcha_answer' => 'پاسخ کپچا نادرست است. لطفاً دوباره تلاش کنید.']);
        }
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'email' => 'nullable|email',
        ]);
        $product = Product::active()->findOrFail($data['product_id']);
        $vid = $data['product_variant_id'] ?? null;
        if ($vid) {
            $v = $product->variants()->whereKey($vid)->first();
            if (! $v) {
                return back()->with('error', 'تنوع نامعتبر است.');
            }
        }
        ProductStockNotification::query()->create([
            'product_id' => $product->id,
            'product_variant_id' => $vid,
            'email' => $data['email'] ?? null,
            'session_id' => substr(session()->getId(), 0, 64),
        ]);

        return back()->with('success', 'درخواست شما ثبت شد؛ هنگام موجود شدن اطلاع می‌دهیم.');
    }

    public function storeReview(Request $request, string $slug)
    {
        if (! SiteHelper::shopEnabled()) {
            abort(404);
        }
        $product = Product::active()->where('slug', $slug)->firstOrFail();
        // Phase 5 / Wave 5.3 / P1-5: کپچا برای جلوگیری از spam ریویوی محصول.
        if (! app(CaptchaService::class)->validateShopCaptcha($request)) {
            return back()
                ->withInput()
                ->withErrors(['captcha_answer' => 'پاسخ کپچا نادرست است. لطفاً دوباره تلاش کنید.']);
        }
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:5000',
            'guest_name' => 'nullable|string|max:120',
        ]);
        ProductReview::query()->create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'guest_name' => auth()->check() ? null : ($data['guest_name'] ?? 'مهمان'),
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
            'approved' => false,
        ]);

        return back()->with('success', 'نظر شما پس از بازبینی نمایش داده می‌شود.');
    }
}

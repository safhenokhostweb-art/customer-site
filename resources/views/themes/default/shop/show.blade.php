@extends('themes.default.layout')

@php
    $canonical = $product->canonical_url ? (str_starts_with($product->canonical_url, 'http') ? $product->canonical_url : url($product->canonical_url)) : route('shop.show', $product->slug);
    $ogImage = $product->og_image ? \App\Helpers\ImageHelper::url($product->og_image) : \App\Helpers\Seo::imageUrl($product);
@endphp
@section('title', \App\Helpers\Seo::title($product))
@section('meta_description', \App\Helpers\Seo::description($product))
@section('meta_image', $ogImage)
@section('canonical', $canonical)
@section('og_type', 'product')
@push('json-ld')
<script type="application/ld+json">@json($jsonLdProduct ?? \App\Helpers\JsonLd::product($product))</script>
@endpush

@section('breadcrumb')
    <a href="{{ route('home') }}">خانه</a>
    <span style="margin: 0 0.5rem;">/</span>
    <a href="{{ route('shop.index') }}">فروشگاه</a>
    <span style="margin: 0 0.5rem;">/</span>
    <span>{{ Str::limit($product->title, 30) }}</span>
@endsection

@section('content')
@php
    $gallery = $product->images->isNotEmpty() ? $product->images : collect();
    if ($gallery->isEmpty() && $product->image) {
        $gallery = collect([(object)['path' => $product->image]]);
    }
    $variantsPayload = $product->variants->map(fn ($v) => [
        'id' => $v->id,
        'label' => $v->label,
        'sku' => $v->sku,
        'price' => $v->effective_price,
        'regular' => $v->regular_price,
        'sale' => $v->sale_price,
        'price_html' => \App\Helpers\CurrencyHelper::formatPrice($v->effective_price) . ' ' . \App\Helpers\CurrencyHelper::unitLabel(),
        'regular_html' => \App\Helpers\CurrencyHelper::formatPrice($v->regular_price) . ' ' . \App\Helpers\CurrencyHelper::unitLabel(),
        'stock' => $v->stock,
        'image' => $v->image ? \App\Helpers\ImageHelper::url($v->image) : null,
        'allow_backorder' => $v->allow_backorder,
        'min_q' => max(1, (int)$v->min_order_qty),
        'max_q' => $v->max_order_qty,
    ])->values();
@endphp
<div style="max-width: 72rem; margin: 0 auto;" id="product-show" data-variants='@json($variantsPayload)'>
    <div style="display: flex; flex-wrap: wrap; gap: 2rem; align-items: flex-start; margin-bottom: 2.5rem;">
        <div style="flex: 1 1 260px; max-width: 400px;">
            <div id="product-gallery-main" style="aspect-ratio: 1; background: #f3f4f6; border-radius: 0.75rem; overflow: hidden; border: 1px solid #e5e7eb; margin-bottom: 0.75rem;">
                @if($gallery->isNotEmpty())
                    <img id="product-main-img" src="{{ \App\Helpers\ImageHelper::url($gallery->first()->path) }}" alt="{{ $product->title }}" style="width: 100%; height: 100%; object-fit: cover; cursor: zoom-in;">
                @else
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 4rem;">📦</div>
                @endif
            </div>
            @if($gallery->count() > 1)
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    @foreach($gallery as $gi => $gim)
                        <button type="button" class="product-thumb-btn" data-src="{{ \App\Helpers\ImageHelper::url($gim->path) }}" style="width: 64px; height: 64px; padding: 0; border: 2px solid {{ $gi === 0 ? 'var(--platform-primary, #4f46e5)' : '#e5e7eb' }}; border-radius: 0.5rem; overflow: hidden; cursor: pointer; background: none;">
                            <img src="{{ \App\Helpers\ImageHelper::url($gim->path) }}" alt="" style="width:100%;height:100%;object-fit:cover;">
                        </button>
                    @endforeach
                </div>
            @endif
            @if($product->video_url)
                <p style="margin-top: 1rem;"><a href="{{ $product->video_url }}" target="_blank" rel="noopener" style="font-weight: 600; color: var(--platform-link-color);">مشاهده ویدیو</a></p>
            @endif
        </div>

        <div style="flex: 1 1 320px; min-width: 0;">
            <div class="card" style="padding: 1.75rem; border-radius: 0.75rem; border: 1px solid #e5e7eb; background: #ffffff;">
                <div style="display:flex; flex-wrap:wrap; gap:0.35rem; margin-bottom:0.75rem;">
                    @if($product->is_new)<span style="padding: 0.25rem 0.6rem; border-radius:999px; background:#dbeafe; color:#1d4ed8; font-size:0.8rem;">جدید</span>@endif
                    @if($product->early_access)<span style="display:inline-flex;align-items:center;gap:.2rem;padding:0.25rem 0.6rem;border-radius:999px;background:#fef3c7;color:#b45309;font-size:0.8rem;font-weight:700"><x-core-ui::icon name="star" class="" /> دسترسی زودهنگام</span>@endif
                    @if($product->sale_price && $product->sale_price < $product->price)<span style="padding: 0.25rem 0.6rem; border-radius:999px; background:#fef3c7; color:#92400e; font-size:0.8rem;">حراج</span>@endif
                    @foreach($product->tags as $tg)
                        <span style="padding: 0.25rem 0.6rem; border-radius:999px; background:#f3f4f6; font-size:0.8rem;">{{ $tg->name }}</span>
                    @endforeach
                </div>
                <h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--platform-title-color, #111827);">{{ $product->title }}</h1>

                <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.9rem;">
                    کد محصول: <span id="product-sku-display" style="font-weight: 600;">{{ $product->sku ?? '-' }}</span>
                </p>
                @if($product->category)
                    <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.9rem;">دسته‌بندی: <span style="font-weight: 600;">{{ $product->category->name }}</span></p>
                @endif

                @if($product->sale_ends_at)
                    <p id="sale-countdown-wrap" style="font-size:0.9rem; color:#b45309; margin-bottom:0.75rem;">
                        پایان حراج (شمسی): <span dir="ltr">{{ \App\Helpers\Jalali::formatFa($product->sale_ends_at, 'Y/m/d H:i') }}</span>
                        — باقیمانده: <span id="sale-countdown" data-end="{{ $product->sale_ends_at->toIso8601String() }}">—</span>
                    </p>
                @endif

                <div style="margin-bottom: 1.25rem;">
                    <span id="product-price-display" style="font-size: 1.4rem; font-weight: 700; color: var(--platform-primary, #4f46e5);">
                        {!! \App\Helpers\CurrencyHelper::formatPrice($product->display_price) !!} {{ \App\Helpers\CurrencyHelper::unitLabel() }}
                    </span>
                    <span id="product-price-old" style="margin-right: 0.5rem; color: #9ca3af; text-decoration: line-through; font-size: 0.95rem; display:none;"></span>
                </div>

                <p id="stock-msg" style="font-size: 0.85rem; margin-bottom: 1rem;"></p>

                <form method="post" action="{{ route('shop.cart.add') }}" class="flex flex-wrap items-center gap-4" style="margin-bottom: 0.5rem;" id="add-cart-form">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="product_variant_id" id="input-variant-id" value="">
                    <div id="variant-picker-wrap" style="{{ $product->variants->isEmpty() ? 'display:none;' : '' }} width:100%; margin-bottom:0.5rem;">
                        <label class="form-label">تنوع</label>
                        <select id="variant-select" class="form-select" style="max-width:100%;">
                            @foreach($product->variants as $v)
                                <option value="{{ $v->id }}" data-label="{{ $v->label }}" data-sku="{{ $v->sku }}">{{ $v->label ?: $v->sku ?: '#'.$v->id }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="flex items-center gap-2">
                        <span class="form-label mb-0">تعداد:</span>
                        <input type="number" name="quantity" id="qty-input" value="1" min="1" class="form-input w-24">
                    </label>
                    <button type="submit" id="add-cart-btn" class="px-6 py-3 font-semibold rounded-lg text-white transition" style="background: var(--platform-button-primary-bg, #4f46e5); border: none; cursor: pointer;">
                        افزودن به سبد
                    </button>
                </form>

                {{-- خرید قسطی --}}
                @if(!empty($installmentPlans) && $installmentPlans->isNotEmpty())
                <div style="margin-top:1.5rem; border:1.5px solid #e0e7ff; border-radius:0.75rem; padding:1rem; background:#f5f3ff;">
                    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem;">
                        <svg style="width:18px;height:18px;color:#6366f1;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        <span style="font-size:0.85rem; font-weight:700; color:#4f46e5;">امکان خرید قسطی</span>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:0.5rem;">
                        @foreach($installmentPlans as $plan)
                        @php
                            $calc = $plan['calc'];
                        @endphp
                        <a href="{{ route('installments.apply', ['plan_id' => $plan['id'], 'product_id' => $product->id]) }}"
                           style="display:flex; align-items:center; justify-content:space-between; padding:0.6rem 0.85rem; border-radius:0.5rem; background:#fff; border:1px solid #c7d2fe; text-decoration:none; transition:background 0.15s;"
                           onmouseover="this.style.background='#eef2ff'" onmouseout="this.style.background='#fff'">
                            <div>
                                <span style="font-weight:700; color:#3730a3; font-size:0.85rem;">{{ $plan['name'] }}</span>
                                <span style="font-size:0.75rem; color:#6b7280; margin-right:0.4rem;">{{ $plan['period_months'] }} ماهه</span>
                            </div>
                            <div style="text-align:left;">
                                <span style="font-size:0.8rem; font-weight:700; color:#4f46e5;">{{ number_format($calc['monthly_payment']) }} ت/ماه</span>
                                @if($calc['down_payment'] > 0)
                                <span style="font-size:0.7rem; color:#9ca3af; display:block;">پیش‌پرداخت: {{ number_format($calc['down_payment']) }} ت</span>
                                @endif
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                <div style="display:flex; flex-wrap:wrap; gap:0.5rem; margin-top:1rem;">
                    <form method="post" action="{{ route('shop.wishlist.toggle', $product) }}">@csrf
                        <button type="submit" class="btn" style="padding:0.5rem 1rem;border:1px solid #e5e7eb;border-radius:0.5rem;background:#fff;cursor:pointer;">{{ $wishlist->has($product->id) ? 'حذف از علاقه‌مندی' : 'افزودن به علاقه‌مندی' }}</button>
                    </form>
                    <form method="post" action="{{ route('shop.compare.toggle', $product) }}">@csrf
                        <button type="submit" style="padding:0.5rem 1rem;border:1px solid #e5e7eb;border-radius:0.5rem;background:#fff;cursor:pointer;">مقایسه</button>
                    </form>
                </div>

                @if((!$product->usesVariants() && $product->stock !== null && (int)$product->stock < 1 && !$product->allow_backorder) || ($product->usesVariants() && $product->variants->every(fn($v) => !$v->allow_backorder && $v->stock !== null && (int)$v->stock < 1) && !$product->allow_backorder))
                    <form method="post" action="{{ route('shop.stock-notify') }}" style="margin-top:1rem;padding:1rem;background:#f9fafb;border-radius:0.5rem;">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="product_variant_id" id="notify-variant-id" value="">
                        <p class="text-sm mb-2">موجود شد خبرم کنید</p>
                        <input type="email" name="email" placeholder="ایمیل (اختیاری)" class="form-input mb-2" style="max-width:16rem;">
                        @include('partials.shop-captcha')
                        <button type="submit" class="btn btn-secondary btn-sm">ثبت درخواست</button>
                    </form>
                @endif

                @if($product->warranty_text || $product->guarantee_text)
                    <div style="margin-top:1rem;font-size:0.85rem;color:#4b5563;">
                        @if($product->warranty_text)<p class="product-html-block"><strong>گارانتی:</strong> <span>{!! $product->warranty_text !!}</span></p>@endif
                        @if($product->guarantee_text)<p class="product-html-block"><strong>تضمین:</strong> <span>{!! $product->guarantee_text !!}</span></p>@endif
                    </div>
                @endif

                <p style="margin-top: 0.75rem; font-size: 0.9rem;">
                    <a href="{{ route('shop.index') }}" style="font-weight: 600; color: var(--platform-link-color, #4f46e5); text-decoration: none;">← بازگشت به فروشگاه</a>
                </p>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 2rem; border: 1px solid #e5e7eb; border-radius: 0.75rem; overflow: hidden;">
        <div style="display:flex; border-bottom:1px solid #e5e7eb; flex-wrap:wrap;">
            <button type="button" class="product-tab-btn px-4 py-3 font-semibold" data-tab="desc" style="border:none;background:#f3f4f6;cursor:pointer;">توضیحات</button>
            <button type="button" class="product-tab-btn px-4 py-3" data-tab="specs" style="border:none;background:#fff;cursor:pointer;">مشخصات فنی</button>
            <button type="button" class="product-tab-btn px-4 py-3" data-tab="ship" style="border:none;background:#fff;cursor:pointer;">ارسال و بازگشت</button>
            <button type="button" class="product-tab-btn px-4 py-3" data-tab="faq" style="border:none;background:#fff;cursor:pointer;">سوالات</button>
        </div>
        <div id="tab-desc" class="product-tab-panel p-4" style="display:block;">
            @if($product->description)
                <div class="product-description-html" style="line-height: 1.8; color: #4b5563;">{!! $product->description !!}</div>
            @else
                <p class="text-gray-500">توضیحی ثبت نشده.</p>
            @endif
        </div>
        <div id="tab-specs" class="product-tab-panel p-4" style="display:none;">
            @if($product->attributeValues->isNotEmpty())
                <table style="width:100%; border-collapse:collapse;">
                    @foreach($product->attributeValues->groupBy(fn($v) => $v->attribute?->name ?? '—') as $aname => $vals)
                        <tr style="border-bottom:1px solid #e5e7eb;">
                            <td style="padding:0.5rem; font-weight:600; width:35%;">{{ $aname }}</td>
                            <td style="padding:0.5rem;">{{ $vals->pluck('value')->implode('، ') }}</td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p class="text-gray-500">مشخصاتی ثبت نشده.</p>
            @endif
            @if($product->size_chart_url)
                <p class="mt-3"><a href="{{ $product->size_chart_url }}" target="_blank" rel="noopener" style="font-weight:600;">جدول سایز</a></p>
            @endif
            @if($product->size_chart_html)
                <div class="mt-3 prose" style="overflow:auto;">{!! $product->size_chart_html !!}</div>
            @endif
        </div>
        <div id="tab-ship" class="product-tab-panel p-4" style="display:none;">
            @if($product->shipping_text)<div class="mb-3"><strong>ارسال</strong><div class="mt-1 text-gray-700 product-html-block">{!! $product->shipping_text !!}</div></div>@endif
            @if($product->returns_text)<div><strong>بازگشت</strong><div class="mt-1 text-gray-700 product-html-block">{!! $product->returns_text !!}</div></div>@endif
            @if(!$product->shipping_text && !$product->returns_text)<p class="text-gray-500">اطلاعاتی ثبت نشده.</p>@endif
        </div>
        <div id="tab-faq" class="product-tab-panel p-4" style="display:none;">
            @if(is_array($product->faq) && count($product->faq) > 0)
                @foreach($product->faq as $fq)
                    @if(is_array($fq) && isset($fq['q']))
                        <div class="mb-3 pb-3 border-b border-gray-100"><strong>{{ $fq['q'] }}</strong><div class="mt-1 text-gray-700 product-html-block">{!! $fq['a'] ?? '' !!}</div></div>
                    @endif
                @endforeach
            @else
                <p class="text-gray-500">سوالی ثبت نشده.</p>
            @endif
        </div>
    </div>

    <section style="margin-bottom: 2.5rem;" class="card p-4">
        <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem;">نظر خریداران @if($reviewAvg)<span style="font-size:0.9rem;color:#6b7280;">(میانگین {{ number_format($reviewAvg, 1) }}/۵)</span>@endif</h2>
        @foreach($reviews as $rev)
            <div style="border-bottom:1px solid #e5e7eb; padding:0.75rem 0;">
                <div style="font-weight:600;">امتیاز {{ $rev->rating }}/۵</div>
                @if($rev->title)<div style="font-weight:600;margin-top:0.25rem;">{{ $rev->title }}</div>@endif
                @if($rev->body)<p style="color:#4b5563;margin-top:0.25rem;">{{ $rev->body }}</p>@endif
            </div>
        @endforeach
        <form method="post" action="{{ route('shop.reviews.store', $product->slug) }}" style="margin-top:1.5rem; max-width:28rem;">
            @csrf
            <h3 class="font-bold mb-2">ثبت نظر</h3>
            <div class="form-group">
                <label class="form-label">امتیاز</label>
                <select name="rating" class="form-select" required>@for($i=5;$i>=1;$i--)<option value="{{ $i }}">{{ $i }}</option>@endfor</select>
            </div>
            @guest
            <div class="form-group"><label class="form-label">نام</label><input type="text" name="guest_name" class="form-input" placeholder="مهمان"></div>
            @endguest
            <div class="form-group"><label class="form-label">عنوان</label><input type="text" name="title" class="form-input"></div>
            <div class="form-group"><label class="form-label">متن</label><textarea name="body" class="form-textarea" rows="3"></textarea></div>
            @include('partials.shop-captcha')
            <button type="submit" class="btn btn-primary">ارسال برای بازبینی</button>
        </form>
    </section>

    @foreach(['upsell' => $upsells ?? collect(), 'cross_sell' => $crossSells ?? collect(), 'related' => $related ?? collect()] as $blockLabel => $plist)
        @php $titles = ['upsell' => 'پیشنهاد ویژه', 'cross_sell' => 'محصولات مکمل', 'related' => 'محصولات مرتبط']; @endphp
        @if($plist->isNotEmpty())
        <section style="margin-bottom: 2.5rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem;">{{ $titles[$blockLabel] ?? $blockLabel }}</h2>
            <div class="card-grid card-grid-4">
                @foreach($plist as $rel)
                    <article class="card" style="display: flex; flex-direction: column; overflow: hidden;">
                        <a href="{{ route('shop.show', $rel->slug) }}" style="flex: 1; text-decoration: none; color: inherit;">
                            <div style="aspect-ratio: 1; background: #f3f4f6; overflow: hidden; margin: -1.5rem -1.5rem 1rem -1.5rem;">
                                @if($rel->image)
                                    <img src="{{ \App\Helpers\ImageHelper::url($rel->image) }}" alt="{{ $rel->title }}" style="width: 100%; height: 100%; object-fit: cover;">
                                @else
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 2.5rem;">📦</div>
                                @endif
                            </div>
                            <h3 style="font-weight: 700; font-size: 1rem; margin-bottom: 0.5rem;">{{ $rel->title }}</h3>
                            <p style="font-weight: 600; margin-bottom: 0.25rem; color: var(--platform-primary, #4f46e5);">
                                {!! \App\Helpers\CurrencyHelper::formatPrice($rel->display_price) !!} {{ \App\Helpers\CurrencyHelper::unitLabel() }}
                            </p>
                        </a>
                    </article>
                @endforeach
            </div>
        </section>
        @endif
    @endforeach
</div>

<script>
(function () {
    var root = document.getElementById('product-show');
    if (!root) return;
    var variants = [];
    try { variants = JSON.parse(root.getAttribute('data-variants') || '[]'); } catch (e) {}
    function applyVariant(id) {
        var v = variants.find(function (x) { return String(x.id) === String(id); });
        var priceEl = document.getElementById('product-price-display');
        var oldEl = document.getElementById('product-price-old');
        var skuEl = document.getElementById('product-sku-display');
        var stockEl = document.getElementById('stock-msg');
        var mainImg = document.getElementById('product-main-img');
        var vidInput = document.getElementById('input-variant-id');
        var notifyVid = document.getElementById('notify-variant-id');
        if (!v) {
            @if(!$product->variants->isEmpty())
            return;
            @endif
        }
        if (v && priceEl) {
            priceEl.innerHTML = v.price_html || '';
            if (v.sale != null && Number(v.sale) < Number(v.regular)) {
                oldEl.style.display = 'inline';
                oldEl.innerHTML = v.regular_html || '';
            } else { oldEl.style.display = 'none'; }
        }
        if (v && skuEl) skuEl.textContent = v.sku || '—';
        if (v && mainImg && v.image) mainImg.src = v.image;
        if (v && vidInput) vidInput.value = v.id;
        if (v && notifyVid) notifyVid.value = v.id;
        if (v && stockEl) {
            var allow = v.allow_backorder || @json($product->allow_backorder);
            var st = v.stock;
            if (allow) { stockEl.textContent = 'قابل پیش‌سفارش'; stockEl.style.color = '#15803d'; }
            else if (st === null) { stockEl.textContent = 'موجود'; stockEl.style.color = '#15803d'; }
            else if (Number(st) < 1) { stockEl.textContent = 'ناموجود'; stockEl.style.color = '#b91c1c'; }
            else { stockEl.textContent = 'موجودی: ' + st; stockEl.style.color = '#15803d'; }
        }
        var qty = document.getElementById('qty-input');
        if (v && qty) { qty.min = v.min_q || 1; qty.max = v.max_q || 999; }
    }
    var sel = document.getElementById('variant-select');
    if (sel) {
        sel.addEventListener('change', function () { applyVariant(sel.value); });
        applyVariant(sel.value);
    } else {
        @if(!$product->variants->isEmpty())
        @else
        var stockEl = document.getElementById('stock-msg');
        if (stockEl) {
            @if($product->allow_backorder)
            stockEl.textContent = 'قابل پیش‌سفارش'; stockEl.style.color = '#15803d';
            @elseif($product->stock === null)
            stockEl.textContent = 'موجود'; stockEl.style.color = '#15803d';
            @elseif((int)$product->stock < 1)
            stockEl.textContent = 'ناموجود'; stockEl.style.color = '#b91c1c';
            @else
            stockEl.textContent = 'موجودی: {{ $product->stock }}'; stockEl.style.color = '#15803d';
            @endif
        }
        @if($product->isLowStock())
        if (stockEl) stockEl.textContent += ' — تنها چند عدد باقی مانده است.';
        @endif
        @endif
    }
    document.querySelectorAll('.product-thumb-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var src = btn.getAttribute('data-src');
            var m = document.getElementById('product-main-img');
            if (m && src) m.src = src;
            document.querySelectorAll('.product-thumb-btn').forEach(function (b) { b.style.borderColor = '#e5e7eb'; });
            btn.style.borderColor = 'var(--platform-primary, #4f46e5)';
        });
    });
    document.querySelectorAll('.product-tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var t = btn.getAttribute('data-tab');
            document.querySelectorAll('.product-tab-panel').forEach(function (p) { p.style.display = 'none'; });
            var panel = document.getElementById('tab-' + t);
            if (panel) panel.style.display = 'block';
            document.querySelectorAll('.product-tab-btn').forEach(function (b) { b.style.background = '#fff'; });
            btn.style.background = '#f3f4f6';
        });
    });
    var cd = document.getElementById('sale-countdown');
    if (cd) {
        var end = new Date(cd.getAttribute('data-end')).getTime();
        function tick() {
            var now = Date.now();
            var s = Math.max(0, Math.floor((end - now) / 1000));
            if (s <= 0) { cd.textContent = 'پایان یافته'; return; }
            var d = Math.floor(s / 86400), h = Math.floor((s % 86400) / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
            cd.textContent = d + ' روز ' + h + ':' + String(m).padStart(2,'0') + ':' + String(sec).padStart(2,'0');
        }
        tick(); setInterval(tick, 1000);
    }
    var main = document.getElementById('product-main-img');
    if (main) main.addEventListener('click', function () {
        window.open(main.src, '_blank', 'noopener');
    });
})();
</script>
@endsection

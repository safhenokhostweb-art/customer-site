@php
    $p = $product;
    $saleEndsIso = old('sale_ends_at');
    if ($saleEndsIso === null && $p->sale_ends_at) {
        $saleEndsIso = $p->sale_ends_at->format('Y-m-d H:i:s');
    }
    $saleEndsIso = $saleEndsIso ?? '';
    $saleEndsDisplay = '';
    if ($saleEndsIso !== '') {
        try {
            $saleEndsDisplay = \App\Helpers\Jalali::formatFa(\Carbon\Carbon::parse($saleEndsIso), 'Y/m/d H:i');
        } catch (\Throwable $e) {
        }
    }
    $faqRows = old('faq_items');
    if (! is_array($faqRows)) {
        $faqRows = (isset($p->faq) && is_array($p->faq) && count($p->faq) > 0)
            ? $p->faq
            : [['q' => '', 'a' => '']];
    }
    $faqRows = array_values(array_map(function ($row) {
        if (! is_array($row)) {
            return ['q' => '', 'a' => ''];
        }

        return [
            'q' => (string) ($row['q'] ?? ''),
            'a' => (string) ($row['a'] ?? ''),
        ];
    }, $faqRows));
@endphp
@if($isEdit ?? false)
    <div class="text-sm text-slate-600 mb-4 p-3 rounded-lg" style="background: var(--am-surface-muted, #f1f5f9); border: 1px solid var(--am-border, #e2e8f0); display:flex; flex-wrap:wrap; gap:0.5rem 1.25rem;">
        <span>ثبت: <strong>{{ \App\Helpers\Jalali::formatFa($p->created_at, 'Y/m/d H:i') }}</strong></span>
        <span>آخرین ویرایش: <strong>{{ \App\Helpers\Jalali::formatFa($p->updated_at, 'Y/m/d H:i') }}</strong></span>
    </div>
@endif
<fieldset class="admin-fieldset">
    <legend class="admin-fieldset__legend">اطلاعات اصلی</legend>
    <div class="form-group">
        <label class="form-label" for="product-title">عنوان</label>
        <input id="product-title" type="text" name="title" value="{{ old('title', $p->title) }}" required>
    </div>
    <div class="form-group">
        <label class="form-label" for="product-slug">نامک</label>
        <input id="product-slug" type="text" name="slug" value="{{ old('slug', $p->slug) }}" autocomplete="off">
        <span class="admin-field-hint">آدرس URL محصول؛ پس از انتشار، تغییر آن تأثیر منفی روی SEO می‌گذارد.</span>
    </div>
    <div class="form-group">
        <label class="form-label" for="product-status">وضعیت انتشار</label>
        <select id="product-status" name="status">
            <option value="draft" @selected(old('status', $p->status ?? 'published') === 'draft')>پیش‌نویس</option>
            <option value="published" @selected(old('status', $p->status ?? 'published') === 'published')>منتشر شده</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="product-category">دسته‌بندی</label>
        <select id="product-category" name="category_id">
            <option value="">بدون دسته</option>
            @foreach($categories ?? [] as $cat)
                <option value="{{ $cat->id }}" @selected(old('category_id', $p->category_id) == $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>
</fieldset>

<fieldset class="admin-fieldset">
    <legend class="admin-fieldset__legend">قیمت و موجودی</legend>
    <div class="form-group">
        <label class="form-label" for="product-price">قیمت پایه ({{ \App\Helpers\CurrencyHelper::unitLabel() }})</label>
        <input id="product-price" type="number" name="price" value="{{ old('price', \App\Helpers\CurrencyHelper::toInputValue($p->price ?? 0)) }}" min="0" step="any" required>
    </div>
    <div class="form-group">
        <label class="form-label" for="product-sale-price">قیمت فروش ویژه</label>
        <input id="product-sale-price" type="number" name="sale_price" value="{{ old('sale_price', $p->sale_price ? \App\Helpers\CurrencyHelper::toInputValue($p->sale_price) : '') }}" min="0" step="any">
        <span class="admin-field-hint">اگر کمتر از قیمت پایه باشد، به‌عنوان قیمت ویژه نمایش داده می‌شود.</span>
    </div>
    <div class="form-group">
        <label class="form-label" for="product-stock">موجودی</label>
        <input id="product-stock" type="number" name="stock" value="{{ old('stock', $p->stock) }}" min="0">
        <span class="admin-field-hint">برای محصول بدون تنوع؛ خالی = نامحدود.</span>
    </div>
    <div class="form-group">
        <label class="form-label">حداقل / حداکثر سفارش</label>
        <div style="display:flex; gap:1rem; flex-wrap:wrap;">
            <input type="number" name="min_order_qty" value="{{ old('min_order_qty', $p->min_order_qty ?? 1) }}" min="1" placeholder="حداقل" aria-label="حداقل سفارش" style="max-width:8rem;">
            <input type="number" name="max_order_qty" value="{{ old('max_order_qty', $p->max_order_qty) }}" min="1" placeholder="حداکثر (خالی=بدون سقف)" aria-label="حداکثر سفارش" style="max-width:12rem;">
        </div>
    </div>
    <div class="form-group">
        <label class="admin-form-inline-check"><input type="checkbox" name="allow_backorder" value="1" @checked(old('allow_backorder', $p->allow_backorder ?? false))> پیش‌فروش / سفارش در صورت اتمام موجودی</label>
    </div>
    <div class="form-group">
        <label class="admin-form-inline-check"><input type="checkbox" name="is_new" value="1" @checked(old('is_new', $p->is_new ?? false))> برچسب «جدید»</label>
    </div>
    <div class="form-group">
        <label class="admin-form-inline-check"><input type="checkbox" name="early_access" value="1" @checked(old('early_access', $p->early_access ?? false))> دسترسی زودهنگام — فقط اعضای با پلن early_access می‌توانند ببینند</label>
    </div>
    <div class="form-group">
        <label class="form-label" for="product-sale-ends-display">پایان حراج (شمسی، اختیاری)</label>
        <input type="text" id="product-sale-ends-display" class="form-input w-full max-w-md" value="{{ $saleEndsDisplay }}" placeholder="۱۴۰۴/۰۱/۰۱ ۱۴:۳۰" autocomplete="off" dir="ltr">
        <input type="hidden" id="product-sale-ends-hidden" name="sale_ends_at" value="{{ $saleEndsIso }}">
        <span class="admin-field-hint">برای حراج بدون مهلت، خالی بگذارید. زمان در سرور به‌صورت استاندارد (میلادی) ذخیره می‌شود.</span>
    </div>
    <div class="form-group">
        <label class="form-label" for="product-low-stock">آستانهٔ هشدار کم‌بودن موجودی</label>
        <input id="product-low-stock" type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $p->low_stock_threshold) }}" min="0" placeholder="مثلاً ۵">
    </div>
</fieldset>

@include('admin.partials.ai-seo-widget', [
    'aiWidgetId'   => 'product-form',
    'aiType'       => 'product_content',
    'aiTitleId'    => 'product-title',
    'aiBodyId'     => 'product-description',
    'aiUseTinymce' => true,
])

<fieldset class="admin-fieldset">
    <legend class="admin-fieldset__legend">توضیحات و سیاست فروش</legend>
    <div class="form-group">
        <label class="form-label" for="product-description">توضیح کوتاه / بلند</label>
        <textarea id="product-description" name="description" rows="6">{{ old('description', $p->description) }}</textarea>
    </div>
    <div class="form-group">
        <label class="form-label" for="product-shipping">ارسال</label>
        <textarea id="product-shipping" name="shipping_text" rows="3">{{ old('shipping_text', $p->shipping_text) }}</textarea>
    </div>
    <div class="form-group">
        <label class="form-label" for="product-returns">بازگشت کالا</label>
        <textarea id="product-returns" name="returns_text" rows="3">{{ old('returns_text', $p->returns_text) }}</textarea>
    </div>
    <div class="form-group">
        <label class="form-label" for="product-warranty">گارانتی</label>
        <textarea id="product-warranty" name="warranty_text" rows="2">{{ old('warranty_text', $p->warranty_text) }}</textarea>
    </div>
    <div class="form-group">
        <label class="form-label" for="product-guarantee">تضمین / اعتماد</label>
        <textarea id="product-guarantee" name="guarantee_text" rows="2">{{ old('guarantee_text', $p->guarantee_text) }}</textarea>
    </div>
    <div class="form-group">
        <label class="form-label" for="product-sizechart-url">جدول سایز (آدرس)</label>
        <input id="product-sizechart-url" type="text" name="size_chart_url" value="{{ old('size_chart_url', $p->size_chart_url) }}" placeholder="https://..." dir="ltr">
    </div>
    <div class="form-group">
        <label class="form-label" for="product-sizechart-html">جدول سایز (HTML)</label>
        <textarea id="product-sizechart-html" name="size_chart_html" rows="4">{{ old('size_chart_html', $p->size_chart_html) }}</textarea>
    </div>
    <div class="form-group">
        <div class="flex justify-between items-center flex-wrap gap-2 mb-2">
            <label class="form-label mb-0" for="faq-items-root">سؤالات متداول</label>
            <button type="button" class="btn btn-secondary btn-sm" id="faq-add-row">افزودن سؤال</button>
        </div>
        <span class="admin-field-hint block mb-3">برای هر مورد، متن سؤال و پاسخ غنی (HTML) را وارد کنید.</span>
        <div id="faq-items-root">
            @foreach($faqRows as $i => $fq)
                <div class="faq-row card mb-3 p-3" data-faq-row>
                    <div class="flex justify-between items-center mb-2 gap-2">
                        <span class="font-semibold text-sm">سؤال {{ $loop->iteration }}</span>
                        @if($loop->count > 1)
                            <button type="button" class="btn btn-danger btn-sm faq-remove-row">حذف</button>
                        @endif
                    </div>
                    <div class="form-group mb-2">
                        <label class="form-label">سؤال</label>
                        <input type="text" class="w-full" name="faq_items[{{ $i }}][q]" value="{{ $fq['q'] }}" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">پاسخ</label>
                        <textarea id="faq_answer_{{ $i }}" class="faq-answer-editor w-full" name="faq_items[{{ $i }}][a]" rows="6">{{ $fq['a'] }}</textarea>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</fieldset>

<fieldset class="admin-fieldset">
    <legend class="admin-fieldset__legend">رسانه و گالری</legend>
    <div class="form-group">
        <label class="form-label" for="product-video">ویدیو (URL)</label>
        <input id="product-video" type="text" name="video_url" value="{{ old('video_url', $p->video_url) }}" dir="ltr" placeholder="https://...">
    </div>
    <x-smart-image-picker name="image" :value="old('image', $p->image)" context="product" label="تصویر اصلی" />
    <div class="form-group">
        <label class="form-label" for="product-gallery-paths">تصاویر گالری (مسیر هر خط)</label>
        @php
            $paths = old('gallery_paths_text');
            if ($paths === null) {
                $paths = $p->relationLoaded('images') ? $p->images->pluck('path')->implode("\n") : '';
            }
        @endphp
        <textarea id="product-gallery-paths" name="gallery_paths_text" rows="4" dir="ltr" class="font-mono text-sm" placeholder="/uploads/...">{{ $paths }}</textarea>
        <span class="admin-field-hint">هر مسیر تصویر را در یک خط جداگانه وارد کنید.</span>
    </div>
</fieldset>
<fieldset class="admin-fieldset">
    <legend class="admin-fieldset__legend">برچسب‌ها، ویژگی‌ها و محصولات مرتبط</legend>
    <div class="form-group">
        <label class="form-label" for="product-tags-select">برچسب‌ها و لیبل‌ها</label>
        <select id="product-tags-select" name="tag_ids[]" multiple size="6" style="min-height:8rem;">
            @foreach($tags ?? [] as $tag)
                <option value="{{ $tag->id }}" @selected(in_array($tag->id, old('tag_ids', $p->relationLoaded('tags') ? $p->tags->pluck('id')->all() : []), true))>{{ $tag->name }} ({{ $tag->tag_type }})</option>
            @endforeach
        </select>
        <span class="admin-field-hint">برای انتخاب چندتایی از کلید Ctrl/Cmd استفاده کنید.</span>
        <label class="form-label mt-2" for="product-new-tags">برچسب جدید (با ویرگول)</label>
        <input id="product-new-tags" type="text" name="new_tags" value="{{ old('new_tags') }}" class="w-full" placeholder="برند الف، پرفروش">
    </div>
    <div class="form-group">
        <label class="form-label">ویژگی‌های فنی (مقادیر)</label>
        <div style="max-height:14rem; overflow:auto; border:1px solid var(--am-border); padding:0.75rem; border-radius:0.5rem; background:#fff;">
            @foreach($attributes ?? [] as $attr)
                <div class="mb-3">
                    <div class="font-semibold text-sm mb-1">{{ $attr->name }}</div>
                    @foreach($attr->values as $val)
                        <label class="admin-form-inline-check mr-2">
                            <input type="checkbox" name="attribute_value_ids[]" value="{{ $val->id }}"
                                @checked(in_array($val->id, old('attribute_value_ids', $p->relationLoaded('attributeValues') ? $p->attributeValues->pluck('id')->all() : []), true))>
                            {{ $val->value }}
                        </label>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">محصولات مرتبط / فروش مکمل / متقابل</label>
        @foreach(['related' => 'مرتبط', 'upsell' => 'فروش ارتقایی', 'cross_sell' => 'متقابل'] as $key => $label)
            <div class="mb-2">
                <label class="text-sm font-medium" for="product-related-{{ $key }}">{{ $label }}</label>
                <select id="product-related-{{ $key }}" name="related_{{ $key }}[]" multiple size="4" class="w-full mt-1">
                    @foreach($allProducts ?? [] as $op)
                        <option value="{{ $op->id }}" @selected(in_array($op->id, old('related_'.$key, $relatedIds[$key] ?? []), true))>{{ $op->title }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
    </div>
</fieldset>
<fieldset class="admin-fieldset">
    <legend class="admin-fieldset__legend">بسته (Bundle)</legend>
    <div class="form-group">
        <label class="admin-form-inline-check"><input type="checkbox" name="is_bundle" value="1" @checked(old('is_bundle', $p->is_bundle ?? false))> این محصول بسته (Bundle) است</label>
    </div>
    <div class="grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
        <div>
            <label class="form-label" for="bundle-pricing">استراتژی قیمت</label>
            <select id="bundle-pricing" name="bundle_pricing">
                <option value="">—</option>
                <option value="sum_discount" @selected(old('bundle_pricing', $p->bundle_pricing) === 'sum_discount')>جمع اجزا با تخفیف درصد</option>
                <option value="fixed" @selected(old('bundle_pricing', $p->bundle_pricing) === 'fixed')>قیمت ثابت بسته</option>
            </select>
        </div>
        <div>
            <label class="form-label" for="bundle-fixed-price">قیمت ثابت بسته</label>
            <input id="bundle-fixed-price" type="number" name="bundle_fixed_price" value="{{ old('bundle_fixed_price', $p->bundle_fixed_price ? \App\Helpers\CurrencyHelper::toInputValue($p->bundle_fixed_price) : '') }}" min="0" step="any">
        </div>
        <div>
            <label class="form-label" for="bundle-discount">تخفیف از جمع اجزا (%)</label>
            <input id="bundle-discount" type="number" name="bundle_discount_percent" value="{{ old('bundle_discount_percent', $p->bundle_discount_percent) }}" min="0" max="100" step="0.01">
        </div>
    </div>
    <p class="text-sm mt-2 mb-1 font-semibold">اجزای بسته:</p>
    @php $lines = old('bundle_lines', $p->relationLoaded('bundleLines') ? $p->bundleLines->map(fn($l) => ['child_product_id' => $l->child_product_id, 'quantity' => $l->quantity])->values()->all() : [['child_product_id' => '', 'quantity' => 1]]); @endphp
    @foreach($lines as $i => $line)
        <div class="flex flex-wrap gap-2 items-end mb-2">
            <select name="bundle_lines[{{ $i }}][child_product_id]" aria-label="محصول فرعی" class="flex-1 min-w-[12rem]">
                <option value="">— محصول فرعی —</option>
                @foreach($allProducts ?? [] as $op)
                    <option value="{{ $op->id }}" @selected((int)($line['child_product_id'] ?? 0) === $op->id)>{{ $op->title }}</option>
                @endforeach
            </select>
            <input type="number" name="bundle_lines[{{ $i }}][quantity]" value="{{ $line['quantity'] ?? 1 }}" min="1" class="w-24" aria-label="تعداد" placeholder="تعداد">
        </div>
    @endforeach
    <span class="admin-field-hint">برای افزودن ردیف، ذخیره و سپس ردیف خالی اضافه کنید (در نسخه بعدی تکرار خودکار).</span>
</fieldset>

<fieldset class="admin-fieldset">
    <legend class="admin-fieldset__legend">سئو و اشتراک‌گذاری</legend>
    <div class="form-group">
        <label class="form-label" for="product-meta-title">عنوان متا</label>
        <input id="product-meta-title" type="text" name="meta_title" value="{{ old('meta_title', $p->meta_title) }}">
    </div>
    <div class="form-group">
        <label class="form-label" for="product-meta-desc">توضیح متا</label>
        <textarea id="product-meta-desc" name="meta_description" rows="2">{{ old('meta_description', $p->meta_description) }}</textarea>
    </div>
    <div class="form-group">
        <label class="form-label" for="product-canonical">Canonical (اختیاری)</label>
        <input id="product-canonical" type="text" name="canonical_url" value="{{ old('canonical_url', $p->canonical_url) }}" dir="ltr">
    </div>
    <x-smart-image-picker name="og_image" :value="old('og_image', $p->og_image)" context="og_image" label="تصویر Open Graph" />
    <div class="form-group">
        <label class="form-label" for="product-order">ترتیب نمایش</label>
        <input id="product-order" type="number" name="order" value="{{ old('order', $p->order ?? 0) }}" min="0">
    </div>
</fieldset>

@if($isEdit ?? false)
<fieldset class="admin-fieldset">
    <legend class="admin-fieldset__legend">تنوع‌ها (رنگ، سایز، ...)</legend>
    @php $variants = old('variants', $p->relationLoaded('variants') ? $p->variants->map(fn($v) => $v->toArray())->values()->all() : []); @endphp
    @if(empty($variants))
        @php $variants = [['id' => '', 'sku' => '', 'label' => '', 'price_override' => '', 'sale_price' => '', 'stock' => '', 'image' => '', 'sort_order' => 0, 'min_order_qty' => 1, 'max_order_qty' => '', 'allow_backorder' => false]]; @endphp
    @endif
    @foreach($variants as $i => $v)
        <div class="card mb-3 p-3" style="background:#fff;">
            <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $v['id'] ?? '' }}">
            <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));">
                <div><label class="text-xs">برچسب نمایشی</label><input type="text" name="variants[{{ $i }}][label]" value="{{ $v['label'] ?? '' }}" aria-label="برچسب نمایشی تنوع {{ $i + 1 }}"></div>
                <div><label class="text-xs">SKU</label><input type="text" name="variants[{{ $i }}][sku]" value="{{ $v['sku'] ?? '' }}" aria-label="SKU تنوع {{ $i + 1 }}"></div>
                <div><label class="text-xs">قیمت اختصاصی</label><input type="number" step="any" name="variants[{{ $i }}][price_override]" value="{{ isset($v['price_override']) && $v['price_override'] !== '' ? \App\Helpers\CurrencyHelper::toInputValue($v['price_override']) : '' }}" aria-label="قیمت اختصاصی تنوع {{ $i + 1 }}"></div>
                <div><label class="text-xs">حراج واریانت</label><input type="number" step="any" name="variants[{{ $i }}][sale_price]" value="{{ isset($v['sale_price']) && $v['sale_price'] !== '' ? \App\Helpers\CurrencyHelper::toInputValue($v['sale_price']) : '' }}" aria-label="حراج تنوع {{ $i + 1 }}"></div>
                <div><label class="text-xs">موجودی</label><input type="number" name="variants[{{ $i }}][stock]" value="{{ $v['stock'] ?? '' }}" aria-label="موجودی تنوع {{ $i + 1 }}"></div>
                <div><label class="text-xs">ترتیب</label><input type="number" name="variants[{{ $i }}][sort_order]" value="{{ $v['sort_order'] ?? $i }}" aria-label="ترتیب تنوع {{ $i + 1 }}"></div>
                <div><label class="text-xs">حداقل سفارش</label><input type="number" name="variants[{{ $i }}][min_order_qty]" value="{{ $v['min_order_qty'] ?? 1 }}" min="1" aria-label="حداقل سفارش تنوع {{ $i + 1 }}"></div>
                <div><label class="text-xs">حداکثر</label><input type="number" name="variants[{{ $i }}][max_order_qty]" value="{{ $v['max_order_qty'] ?? '' }}" aria-label="حداکثر سفارش تنوع {{ $i + 1 }}"></div>
            </div>
            <div class="form-group">
                <label class="text-xs">مسیر تصویر تنوع</label>
                <input type="text" name="variants[{{ $i }}][image]" value="{{ $v['image'] ?? '' }}" dir="ltr" class="font-mono text-sm w-full" placeholder="/uploads/..." aria-label="تصویر تنوع {{ $i + 1 }}">
            </div>
            <label class="admin-form-inline-check"><input type="checkbox" name="variants[{{ $i }}][allow_backorder]" value="1" @checked(!empty($v['allow_backorder']))> پیش‌فروش برای این تنوع</label>
        </div>
    @endforeach
</fieldset>
@endif

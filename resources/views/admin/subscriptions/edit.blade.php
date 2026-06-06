@extends('admin.layout')
@section('title', 'ویرایش پلن: ' . $subscription->name)
@section('content')

<div class="plnfrm" style="font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif">

<div class="plnfrm__head">
    <div>
        <h1 class="plnfrm__title">ویرایش پلن: {{ $subscription->name }}</h1>
        <p class="plnfrm__sub">تغییرات روی این پلن برای مشترکین فعال هم اعمال می‌شود.</p>
    </div>
    <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary btn-sm">← بازگشت</a>
</div>

@if($errors->any())
<div class="alert alert-danger">
    <ul class="list-disc list-inside text-sm space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form action="{{ route('admin.subscriptions.update', $subscription) }}" method="POST" enctype="multipart/form-data" id="plan-form">
@csrf @method('PUT')

<div class="plnfrm__grid">

    {{-- ═══ ستون اصلی ═══ --}}
    <div class="plnfrm__main">

        <div class="plnfrm__card">
            <div class="plnfrm__card-head"><span class="plnfrm__step">۱</span> اطلاعات پایه</div>
            <div class="plnfrm__card-body">
                <div class="plnfrm__field">
                    <label class="plnfrm__label">نام پلن <span class="plnfrm__req">*</span></label>
                    <input type="text" name="name" id="plan-name" value="{{ old('name', $subscription->name) }}" required class="plnfrm__input">
                </div>
                <div class="plnfrm__field">
                    <label class="plnfrm__label">اسلاگ <span class="plnfrm__hint">اختیاری</span></label>
                    <input type="text" name="slug" value="{{ old('slug', $subscription->slug) }}" class="plnfrm__input plnfrm__input--mono" dir="ltr">
                </div>
                <div class="plnfrm__field">
                    <label class="plnfrm__label">توضیحات</label>
                    <textarea id="subscription-description-editor" name="description" class="plnfrm__textarea" rows="3">{{ old('description', $subscription->description) }}</textarea>
                </div>
            </div>
        </div>

        <div class="plnfrm__card">
            <div class="plnfrm__card-head"><span class="plnfrm__step">۲</span> قیمت‌گذاری</div>
            <div class="plnfrm__card-body">
                <div class="plnfrm__row-3">
                    <div class="plnfrm__field">
                        <label class="plnfrm__label">قیمت اصلی (تومان) <span class="plnfrm__req">*</span></label>
                        <input type="number" name="price" value="{{ old('price', $subscription->price) }}" required min="0" class="plnfrm__input" id="price-main">
                    </div>
                    <div class="plnfrm__field">
                        <label class="plnfrm__label">قیمت ویژه <span class="plnfrm__badge-sale">تخفیف</span></label>
                        <input type="number" name="special_price" value="{{ old('special_price', $subscription->special_price) }}" min="0" placeholder="خالی = بدون تخفیف" class="plnfrm__input">
                    </div>
                    <div class="plnfrm__field">
                        <label class="plnfrm__label">محدودیت کاربر</label>
                        <input type="number" name="max_users" value="{{ old('max_users', $subscription->max_users) }}" min="1" placeholder="خالی = نامحدود" class="plnfrm__input">
                    </div>
                </div>
                <div class="plnfrm__divider"></div>
                <div class="plnfrm__row-2">
                    <div class="plnfrm__field">
                        <label class="plnfrm__label">کد تخفیف</label>
                        <input type="text" name="discount_code" value="{{ old('discount_code', $subscription->discount_code) }}" class="plnfrm__input plnfrm__input--mono" dir="ltr" maxlength="50">
                    </div>
                    <div class="plnfrm__field">
                        <label class="plnfrm__label">درصد تخفیف با کد</label>
                        <div class="plnfrm__input-group">
                            <input type="number" name="discount_percent" value="{{ old('discount_percent', $subscription->discount_percent) }}" min="0" max="100" step="0.5" class="plnfrm__input">
                            <span class="plnfrm__input-suffix">٪</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="plnfrm__card">
            <div class="plnfrm__card-head"><span class="plnfrm__step">۳</span> مدت و دوره آزمایشی</div>
            <div class="plnfrm__card-body">
                <div class="plnfrm__row-3">
                    <div class="plnfrm__field">
                        <label class="plnfrm__label">نوع مدت <span class="plnfrm__req">*</span></label>
                        <select name="duration" required class="plnfrm__select">
                            <option value="day"   {{ old('duration', $subscription->duration) === 'day'   ? 'selected' : '' }}>روزانه (تعداد روز)</option>
                            <option value="month" {{ old('duration', $subscription->duration) === 'month' ? 'selected' : '' }}>ماهانه</option>
                            <option value="year"  {{ old('duration', $subscription->duration) === 'year'  ? 'selected' : '' }}>سالانه</option>
                        </select>
                    </div>
                    <div class="plnfrm__field">
                        <label class="plnfrm__label">تعداد واحد <span class="plnfrm__req">*</span></label>
                        <input type="number" name="duration_count" value="{{ old('duration_count', $subscription->duration_count) }}" required min="1" class="plnfrm__input">
                    </div>
                    <div class="plnfrm__field">
                        <label class="plnfrm__label">روزهای آزمایشی</label>
                        <input type="number" name="trial_days" value="{{ old('trial_days', $subscription->trial_days) }}" min="0" class="plnfrm__input">
                    </div>
                </div>
            </div>
        </div>

        <div class="plnfrm__card">
            <div class="plnfrm__card-head"><span class="plnfrm__step">۴</span> امکانات پلن</div>
            <div class="plnfrm__card-body">
                <div id="features-container" class="plnfrm__feats">
                    @php $feats = old('features', $subscription->getFeaturesArray() ?: ['']); @endphp
                    @foreach($feats as $f)
                    <div class="plnfrm__feat-row">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" class="plnfrm__feat-icon" style="color:#4f46e5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <input type="text" name="features[]" value="{{ $f }}" placeholder="امکان..." class="plnfrm__feat-input">
                        <button type="button" onclick="this.closest('.plnfrm__feat-row').remove()" class="plnfrm__feat-del">×</button>
                    </div>
                    @endforeach
                </div>
                <button type="button" id="add-feature" class="plnfrm__feat-add">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    افزودن امکان
                </button>
            </div>
        </div>

        {{-- مزایای اشتراک --}}
        <div class="plnfrm__card">
            <div class="plnfrm__card-head"><span class="plnfrm__step">۵</span> مزایای عضویت</div>
            <div class="plnfrm__card-body">
                <div class="plnfrm__row-2">
                    <div class="plnfrm__field">
                        <label class="plnfrm__label">تخفیف روی محصولات (%)</label>
                        <div class="plnfrm__input-group">
                            <input type="number" name="product_discount_percent"
                                   value="{{ old('product_discount_percent', $subscription->product_discount_percent ?? 0) }}"
                                   min="0" max="100" class="plnfrm__input">
                            <span class="plnfrm__input-suffix">٪</span>
                        </div>
                        <span class="plnfrm__field-note">تخفیف اتوماتیک روی خرید محصولات مرتبط</span>
                    </div>
                    <div class="plnfrm__field">
                        <label class="plnfrm__label">ضریب امتیاز وفاداری</label>
                        <input type="number" name="loyalty_multiplier"
                               value="{{ old('loyalty_multiplier', $subscription->loyalty_multiplier ?? 1) }}"
                               min="1" max="10" class="plnfrm__input">
                        <span class="plnfrm__field-note">۱ = عادی، ۲ = دو برابر امتیاز</span>
                    </div>
                </div>
                <div class="plnfrm__checks" style="margin-top:.5rem">
                    <label class="plnfrm__check">
                        <input type="checkbox" name="free_shipping" value="1"
                               {{ old('free_shipping', $subscription->free_shipping ?? false) ? 'checked' : '' }}>
                        <span>🚚 ارسال رایگان برای سفارشات</span>
                    </label>
                    <label class="plnfrm__check">
                        <input type="checkbox" name="vip_support" value="1"
                               {{ old('vip_support', $subscription->vip_support ?? false) ? 'checked' : '' }}>
                        <span>🎧 پشتیبانی VIP اولویت‌دار</span>
                    </label>
                    <label class="plnfrm__check">
                        <input type="checkbox" name="early_access" value="1"
                               {{ old('early_access', $subscription->early_access ?? false) ? 'checked' : '' }}>
                        <span>⭐ دسترسی زودهنگام به محصولات جدید</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- محصولات مرتبط --}}
        <div class="plnfrm__card">
            <div class="plnfrm__card-head">
                <span class="plnfrm__step">۶</span>
                محصولات مرتبط با این اشتراک
                <span class="plnfrm__hint">مشترکین تخفیف ویژه روی محصولات انتخاب‌شده دریافت می‌کنند</span>
            </div>
            <div class="plnfrm__card-body">

                {{-- همه محصولات toggle --}}
                <div class="prod-scope-row">
                    <label class="prod-scope-toggle">
                        <input type="hidden" name="apply_to_all_products" value="0">
                        <input type="checkbox" name="apply_to_all_products" value="1" id="apply_all_cb"
                               {{ old('apply_to_all_products', $subscription->apply_to_all_products) ? 'checked' : '' }}
                               onchange="toggleAllScope(this.checked)">
                        <span class="prod-scope-track"></span>
                        <div class="prod-scope-label">
                            <strong>شامل همه محصولات</strong>
                            <small>تخفیف اشتراک به‌صورت خودکار روی تمام محصولات فعال اعمال می‌شود</small>
                        </div>
                    </label>
                    <label class="prod-scope-toggle" style="margin-top:.625rem">
                        <input type="hidden" name="coupon_override" value="0">
                        <input type="checkbox" name="coupon_override" value="1" id="coupon_override_cb"
                               {{ old('coupon_override', $subscription->coupon_override) ? 'checked' : '' }}>
                        <span class="prod-scope-track"></span>
                        <div class="prod-scope-label">
                            <strong>اعمال تخفیف حتی با کوپن فعال</strong>
                            <small>اگر فعال نباشد، محصولاتی که کوپن تخفیف دارند از تخفیف اشتراک محروم می‌شوند</small>
                        </div>
                    </label>
                </div>

                {{-- انتخاب محصولات خاص --}}
                <div id="products-selector" style="{{ old('apply_to_all_products', $subscription->apply_to_all_products) ? 'display:none' : '' }}">
                    <div class="prod-search-wrap">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="15" height="15" class="prod-search-icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" id="prod-search" placeholder="جستجوی محصول..." class="prod-search-input" autocomplete="off">
                        <span id="prod-selected-count" class="prod-count-badge" style="display:none"></span>
                    </div>

                    @php $linkedProducts = $subscription->products->keyBy('id'); @endphp
                    <div class="prod-list" id="prod-list">
                        @forelse($allProducts as $prod)
                        @php
                            $linked       = $linkedProducts->get($prod->id);
                            $isLinked     = $linked !== null;
                            $discPct      = $linked?->pivot->discount_percent ?? $subscription->product_discount_percent ?? 0;
                            $couponOvr    = $linked?->pivot->override_coupon_exclusion ?? false;
                        @endphp
                        <div class="prod-item {{ $isLinked ? 'prod-item--sel' : '' }}" id="pi-{{ $prod->id }}" data-name="{{ mb_strtolower($prod->title) }}">
                            <label class="prod-item__check-label">
                                <input type="checkbox" name="product_ids[]" value="{{ $prod->id }}"
                                       class="prod-item__cb"
                                       onchange="onProdCheck({{ $prod->id }}, this.checked)"
                                       {{ $isLinked ? 'checked' : '' }}>
                                <span class="prod-item__check-box"></span>
                                <span class="prod-item__name">{{ $prod->title }}</span>
                            </label>
                            <div class="prod-item__extras" id="pex-{{ $prod->id }}" style="{{ $isLinked ? '' : 'display:none' }}">
                                <div class="prod-item__disc-wrap">
                                    <input type="number" name="product_discounts[{{ $prod->id }}]"
                                           value="{{ $discPct }}"
                                           min="0" max="100" class="prod-item__disc-input" placeholder="٪ تخفیف">
                                    <span class="prod-item__disc-unit">٪</span>
                                </div>
                                <label class="prod-item__coupon-label" title="اعمال تخفیف حتی با کوپن فعال برای این محصول">
                                    <input type="checkbox" name="product_coupon_overrides[{{ $prod->id }}]" value="1"
                                           {{ $couponOvr ? 'checked' : '' }}>
                                    <span>تخفیف با کوپن</span>
                                </label>
                            </div>
                        </div>
                        @empty
                        <p class="prod-empty">هنوز محصولی ایجاد نشده.</p>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>

    </div>

    {{-- ═══ ستون کناری ═══ --}}
    <div class="plnfrm__side">

        <div class="plnfrm__preview" id="preview-card">
            <div class="plnfrm__preview-top" id="prev-top" style="border-top:4px solid {{ $subscription->color ?? '#4f46e5' }}">
                @if($subscription->image)
                <div id="prev-img-wrap">
                    <img id="prev-img" src="{{ asset('storage/' . $subscription->image) }}" alt="{{ $subscription->name }}" class="plnfrm__preview-img">
                </div>
                @else
                <div id="prev-img-wrap" style="display:none">
                    <img id="prev-img" src="" alt="" class="plnfrm__preview-img">
                </div>
                @endif
                <p class="plnfrm__preview-name" id="prev-name">{{ $subscription->name }}</p>
                <div class="plnfrm__preview-price">
                    <span class="plnfrm__preview-amount" id="prev-price" style="color:{{ $subscription->color ?? '#4f46e5' }}">
                        {{ $subscription->getFormattedPrice() }}
                    </span>
                </div>
            </div>
            <div class="plnfrm__preview-feats" id="prev-feats">
                @foreach($subscription->getFeaturesArray() as $f)
                <div style="display:flex;align-items:center;gap:.375rem">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>{{ $f }}
                </div>
                @endforeach
            </div>
            <div class="plnfrm__preview-footer">
                <div class="plnfrm__preview-btn" id="prev-top-color" style="background:{{ $subscription->color ?? '#4f46e5' }}">خرید اشتراک</div>
            </div>
        </div>

        <div class="plnfrm__card">
            <div class="plnfrm__card-head">ظاهر و نمایش</div>
            <div class="plnfrm__card-body">
                <div class="plnfrm__field">
                    <label class="plnfrm__label">تصویر کارت پلن</label>
                    @if($subscription->image)
                    <div class="plnfrm__current-img">
                        <img src="{{ asset('storage/' . $subscription->image) }}" alt="" class="plnfrm__current-img__preview">
                        <label class="plnfrm__check" style="margin-top:.5rem">
                            <input type="checkbox" name="remove_image" value="1">
                            <span style="color:#be123c">حذف تصویر فعلی</span>
                        </label>
                    </div>
                    @endif
                    <div class="plnfrm__upload-area">
                        <input type="file" name="image" id="img-input" accept="image/*" class="plnfrm__upload-input">
                        <label for="img-input" class="plnfrm__upload-label">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24" style="color:#94a3b8"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>{{ $subscription->image ? 'تغییر تصویر' : 'انتخاب تصویر' }}</span>
                            <small>PNG, JPG تا ۲ مگابایت</small>
                        </label>
                        <img id="img-preview" src="" alt="" style="display:none;width:70px;height:70px;object-fit:cover;border-radius:.5rem;margin:.5rem auto 0">
                    </div>
                </div>
                <div class="plnfrm__field">
                    <label class="plnfrm__label">رنگ کارت</label>
                    <div class="plnfrm__color-row">
                        <input type="color" name="color" id="color-picker" value="{{ old('color', $subscription->color ?? '#4f46e5') }}" class="plnfrm__color-input">
                        <input type="text" id="color-text" value="{{ old('color', $subscription->color ?? '#4f46e5') }}" class="plnfrm__input plnfrm__input--mono" dir="ltr" maxlength="9">
                    </div>
                    <div class="plnfrm__swatches">
                        @foreach(['#4f46e5','#7c3aed','#0284c7','#059669','#d97706','#e11d48','#475569','#1e293b'] as $sw)
                        <button type="button" class="plnfrm__swatch" style="background:{{ $sw }}" onclick="setColor('{{ $sw }}')" title="{{ $sw }}"></button>
                        @endforeach
                    </div>
                </div>
                <div class="plnfrm__field">
                    <label class="plnfrm__label">متن بج</label>
                    <input type="text" name="badge_text" value="{{ old('badge_text', $subscription->badge_text) }}" placeholder="پیشنهادی" class="plnfrm__input" maxlength="50">
                </div>
                <div class="plnfrm__field">
                    <label class="plnfrm__label">ترتیب نمایش</label>
                    <input type="number" name="order" value="{{ old('order', $subscription->order) }}" min="0" class="plnfrm__input">
                </div>
                <div class="plnfrm__checks">
                    <label class="plnfrm__check">
                        <input type="checkbox" name="highlighted" value="1" {{ old('highlighted', $subscription->highlighted) ? 'checked' : '' }}>
                        <span>پیشنهاد ویژه</span>
                    </label>
                    <label class="plnfrm__check">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $subscription->is_active) ? 'checked' : '' }}>
                        <span>پلن فعال است</span>
                    </label>
                </div>
            </div>
        </div>

        <button type="submit" class="plnfrm__submit">ذخیره تغییرات</button>
        <a href="{{ route('admin.subscriptions.index') }}" class="plnfrm__cancel">انصراف</a>

    </div>

</div>
</form>
</div>

@include('admin.partials.tinymce-field-init', ['selector' => '#subscription-description-editor', 'uploadContext' => 'featured', 'height' => 180])

@push('head')
@include('admin.subscriptions._plan_form_styles')
<style>
/* ── Product scope section ── */
.prod-scope-row{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.75rem;padding:1rem 1.125rem;display:flex;flex-direction:column;gap:.25rem;margin-bottom:1rem}
.prod-scope-toggle{display:flex;align-items:center;gap:.75rem;cursor:pointer}
.prod-scope-toggle input[type="checkbox"]{position:absolute;opacity:0;width:0;height:0}
.prod-scope-track{width:40px;height:22px;background:#e2e8f0;border-radius:9999px;position:relative;flex-shrink:0;transition:background .2s}
.prod-scope-track::after{content:'';position:absolute;top:3px;right:3px;width:16px;height:16px;background:#fff;border-radius:50%;transition:right .2s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.prod-scope-toggle input:checked~.prod-scope-track{background:#4f46e5}
.prod-scope-toggle input:checked~.prod-scope-track::after{right:calc(100% - 19px)}
.prod-scope-label{display:flex;flex-direction:column;gap:.1rem}
.prod-scope-label strong{font-size:.85rem;color:#1e293b;font-weight:700}
.prod-scope-label small{font-size:.75rem;color:#64748b}

/* ── Product list ── */
.prod-search-wrap{position:relative;display:flex;align-items:center;margin-bottom:.75rem}
.prod-search-icon{position:absolute;right:.75rem;color:#94a3b8;pointer-events:none}
.prod-search-input{width:100%;box-sizing:border-box;padding:.5rem .75rem .5rem 2.25rem;border:1.5px solid #e2e8f0;border-radius:.625rem;font-size:.875rem;font-family:Vazirmatn,Tahoma,sans-serif;outline:none}
.prod-search-input:focus{border-color:#4f46e5;box-shadow:0 0 0 3px rgba(79,70,229,.1)}
.prod-count-badge{position:absolute;left:.625rem;background:#4f46e5;color:#fff;font-size:.7rem;font-weight:700;padding:.15rem .5rem;border-radius:9999px;white-space:nowrap}
.prod-list{display:flex;flex-direction:column;gap:.35rem;max-height:320px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:.625rem;padding:.5rem}
.prod-list::-webkit-scrollbar{width:4px}.prod-list::-webkit-scrollbar-track{background:#f1f5f9}.prod-list::-webkit-scrollbar-thumb{background:#c7d2fe;border-radius:2px}
.prod-item{border:1.5px solid #e2e8f0;border-radius:.5rem;padding:.5rem .75rem;transition:border-color .15s,background .15s}
.prod-item--sel{border-color:#4f46e5;background:#eef2ff}
.prod-item__check-label{display:flex;align-items:center;gap:.5rem;cursor:pointer;width:100%}
.prod-item__cb{position:absolute;opacity:0;width:0;height:0}
.prod-item__check-box{width:16px;height:16px;border:1.5px solid #cbd5e1;border-radius:.3rem;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:background .15s,border-color .15s}
.prod-item__cb:checked~.prod-item__check-box{background:#4f46e5;border-color:#4f46e5}
.prod-item__cb:checked~.prod-item__check-box::after{content:'✓';color:#fff;font-size:.6rem;font-weight:900}
.prod-item__name{font-size:.85rem;color:#334155;font-weight:500;flex:1}
.prod-item__extras{display:flex;align-items:center;gap:.625rem;margin-top:.375rem;padding-right:1.375rem;flex-wrap:wrap}
.prod-item__disc-wrap{display:flex;align-items:center;gap:.25rem}
.prod-item__disc-input{width:60px;padding:.25rem .4rem;border:1px solid #e2e8f0;border-radius:.375rem;font-size:.8rem;text-align:center;font-family:Vazirmatn,Tahoma,sans-serif;outline:none}
.prod-item__disc-input:focus{border-color:#4f46e5}
.prod-item__disc-unit{font-size:.75rem;color:#64748b}
.prod-item__coupon-label{display:flex;align-items:center;gap:.3rem;font-size:.75rem;color:#64748b;cursor:pointer}
.prod-item__coupon-label input{width:13px;height:13px;accent-color:#4f46e5}
.prod-empty{font-size:.8rem;color:#94a3b8;padding:.5rem}
</style>
@endpush

@push('scripts')
@include('admin.subscriptions._plan_form_scripts')
<script>
function toggleAllScope(allSelected) {
    var sel = document.getElementById('products-selector');
    if (sel) sel.style.display = allSelected ? 'none' : '';
}
function onProdCheck(id, checked) {
    var item = document.getElementById('pi-' + id);
    var extras = document.getElementById('pex-' + id);
    if (item) item.classList.toggle('prod-item--sel', checked);
    if (extras) extras.style.display = checked ? '' : 'none';
    updateProdCount();
}
function updateProdCount() {
    var total = document.querySelectorAll('.prod-item__cb:checked').length;
    var badge = document.getElementById('prod-selected-count');
    if (badge) {
        if (total > 0) {
            badge.textContent = total + ' انتخاب‌شده';
            badge.style.display = '';
        } else {
            badge.style.display = 'none';
        }
    }
}
document.addEventListener('DOMContentLoaded', function () {
    updateProdCount();
    var searchInput = document.getElementById('prod-search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var q = this.value.trim().toLowerCase();
            document.querySelectorAll('.prod-item[data-name]').forEach(function (el) {
                el.style.display = (!q || el.dataset.name.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    }
});
</script>
@endpush

@endsection

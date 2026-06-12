@extends('admin.layout')
@section('title', 'پلن اشتراک جدید')
@section('content')

<div class="plnfrm" style="font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif">

<div class="plnfrm__head">
    <div>
        <h1 class="plnfrm__title">ایجاد پلن اشتراک جدید</h1>
        <p class="plnfrm__sub">اطلاعات کامل پلن را وارد کنید.</p>
    </div>
    <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary btn-sm">← بازگشت</a>
</div>

@if($errors->any())
<div class="alert alert-danger">
    <ul class="list-disc list-inside text-sm space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form action="{{ route('admin.subscriptions.store') }}" method="POST" enctype="multipart/form-data" id="plan-form">
@csrf

<div class="plnfrm__grid">

    {{-- ═══ ستون اصلی ═══ --}}
    <div class="plnfrm__main">

        {{-- اطلاعات اصلی --}}
        <div class="plnfrm__card">
            <div class="plnfrm__card-head">
                <span class="plnfrm__step">۱</span> اطلاعات پایه
            </div>
            <div class="plnfrm__card-body">
                <div class="plnfrm__field">
                    <label class="plnfrm__label">نام پلن <span class="plnfrm__req">*</span></label>
                    <input type="text" name="name" id="plan-name" value="{{ old('name') }}" required
                           placeholder="مثلاً: پلن طلایی" class="plnfrm__input">
                </div>
                <div class="plnfrm__field">
                    <label class="plnfrm__label">اسلاگ (URL) <span class="plnfrm__hint">اختیاری — از نام خودکار ساخته می‌شود</span></label>
                    <input type="text" name="slug" id="plan-slug" value="{{ old('slug') }}"
                           placeholder="plan-slug" class="plnfrm__input plnfrm__input--mono" dir="ltr">
                </div>
                <div class="plnfrm__field">
                    <label class="plnfrm__label">توضیحات</label>
                    <textarea id="subscription-description-editor" name="description"
                              class="plnfrm__textarea" rows="3"
                              placeholder="توضیح کوتاه این پلن برای کاربر...">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        {{-- قیمت‌گذاری --}}
        <div class="plnfrm__card">
            <div class="plnfrm__card-head">
                <span class="plnfrm__step">۲</span> قیمت‌گذاری
            </div>
            <div class="plnfrm__card-body">
                <div class="plnfrm__row-3">
                    <div class="plnfrm__field">
                        <label class="plnfrm__label">قیمت اصلی (تومان) <span class="plnfrm__req">*</span></label>
                        <input type="number" name="price" value="{{ old('price', 0) }}" required min="0"
                               placeholder="۰ = رایگان" class="plnfrm__input" id="price-main">
                        <span class="plnfrm__field-note">۰ = رایگان</span>
                    </div>
                    <div class="plnfrm__field">
                        <label class="plnfrm__label">
                            قیمت ویژه (تومان)
                            <span class="plnfrm__badge-sale">تخفیف</span>
                        </label>
                        <input type="number" name="special_price" value="{{ old('special_price') }}" min="0"
                               placeholder="خالی = بدون تخفیف" class="plnfrm__input">
                        <span class="plnfrm__field-note">کمتر از قیمت اصلی وارد کنید</span>
                    </div>
                    <div class="plnfrm__field">
                        <label class="plnfrm__label">محدودیت کاربر</label>
                        <input type="number" name="max_users" value="{{ old('max_users') }}" min="1"
                               placeholder="خالی = نامحدود" class="plnfrm__input">
                    </div>
                </div>
                <div class="plnfrm__divider"></div>
                <div class="plnfrm__row-2">
                    <div class="plnfrm__field">
                        <label class="plnfrm__label">کد تخفیف</label>
                        <input type="text" name="discount_code" value="{{ old('discount_code') }}"
                               placeholder="SUMMER30" class="plnfrm__input plnfrm__input--mono" dir="ltr" maxlength="50">
                        <span class="plnfrm__field-note">وقتی کاربر این کد را وارد کند، تخفیف اعمال می‌شود</span>
                    </div>
                    <div class="plnfrm__field">
                        <label class="plnfrm__label">درصد تخفیف با کد</label>
                        <div class="plnfrm__input-group">
                            <input type="number" name="discount_percent" value="{{ old('discount_percent') }}"
                                   min="0" max="100" step="0.5" placeholder="۲۰" class="plnfrm__input">
                            <span class="plnfrm__input-suffix">٪</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- مدت و آزمایشی --}}
        <div class="plnfrm__card">
            <div class="plnfrm__card-head">
                <span class="plnfrm__step">۳</span> مدت و دوره آزمایشی
            </div>
            <div class="plnfrm__card-body">
                <div class="plnfrm__row-3">
                    <div class="plnfrm__field">
                        <label class="plnfrm__label">نوع مدت <span class="plnfrm__req">*</span></label>
                        <select name="duration" required class="plnfrm__select">
                            <option value="day"   {{ old('duration') === 'day'   ? 'selected' : '' }}>روزانه (تعداد روز)</option>
                            <option value="month" {{ old('duration', 'month') === 'month' ? 'selected' : '' }}>ماهانه</option>
                            <option value="year"  {{ old('duration') === 'year'  ? 'selected' : '' }}>سالانه</option>
                        </select>
                    </div>
                    <div class="plnfrm__field">
                        <label class="plnfrm__label">تعداد واحد <span class="plnfrm__req">*</span></label>
                        <input type="number" name="duration_count" value="{{ old('duration_count', 1) }}" required min="1"
                               class="plnfrm__input">
                    </div>
                    <div class="plnfrm__field">
                        <label class="plnfrm__label">روزهای آزمایشی رایگان</label>
                        <input type="number" name="trial_days" value="{{ old('trial_days', 0) }}" min="0"
                               class="plnfrm__input">
                        <span class="plnfrm__field-note">۰ = بدون دوره آزمایشی</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- امکانات --}}
        <div class="plnfrm__card">
            <div class="plnfrm__card-head">
                <span class="plnfrm__step">۴</span> لیست امکانات پلن
            </div>
            <div class="plnfrm__card-body">
                <div id="features-container" class="plnfrm__feats">
                    @foreach(old('features', ['']) as $f)
                    <div class="plnfrm__feat-row">
                        <x-core-ui::icon name="check" class="plnfrm__feat-icon w-3.5 h-3.5" style="color:#4f46e5" />
                        <input type="text" name="features[]" value="{{ $f }}"
                               placeholder="مثلاً: دسترسی کامل به محتوا" class="plnfrm__feat-input">
                        <button type="button" onclick="this.closest('.plnfrm__feat-row').remove()"
                                class="plnfrm__feat-del" title="حذف">×</button>
                    </div>
                    @endforeach
                </div>
                <button type="button" id="add-feature" class="plnfrm__feat-add">
                    <x-core-ui::icon name="plus" class="w-3.5 h-3.5" />
                    افزودن امکان
                </button>
            </div>
        </div>

    </div>{{-- /main --}}

    {{-- ═══ ستون کناری ═══ --}}
    <div class="plnfrm__side">

        {{-- پیش‌نمایش --}}
        <div class="plnfrm__preview" id="preview-card">
            <div class="plnfrm__preview-top" id="prev-top" style="border-top:4px solid #4f46e5">
                <div id="prev-img-wrap" style="display:none">
                    <img id="prev-img" src="" alt="" class="plnfrm__preview-img">
                </div>
                <p class="plnfrm__preview-name" id="prev-name">نام پلن</p>
                <div class="plnfrm__preview-price">
                    <span class="plnfrm__preview-amount" id="prev-price" style="color:#4f46e5">۰ تومان</span>
                </div>
            </div>
            <div class="plnfrm__preview-feats" id="prev-feats"></div>
            <div class="plnfrm__preview-footer">
                <div class="plnfrm__preview-btn" id="prev-top-color" style="background:#4f46e5">خرید اشتراک</div>
            </div>
        </div>

        {{-- ظاهر --}}
        <div class="plnfrm__card">
            <div class="plnfrm__card-head">ظاهر و نمایش</div>
            <div class="plnfrm__card-body">
                <div class="plnfrm__field">
                    <label class="plnfrm__label">تصویر کارت پلن</label>
                    <div class="plnfrm__upload-area" id="img-upload-area">
                        <input type="file" name="image" id="img-input" accept="image/*" class="plnfrm__upload-input">
                        <label for="img-input" class="plnfrm__upload-label">
                            <x-core-ui::icon name="image" class="w-7 h-7" style="color:#94a3b8" />
                            <span>کلیک برای انتخاب تصویر</span>
                            <small>PNG, JPG تا ۲ مگابایت</small>
                        </label>
                        <img id="img-preview" src="" alt="" style="display:none;width:80px;height:80px;object-fit:cover;border-radius:.5rem;margin:0 auto .5rem">
                    </div>
                </div>
                <div class="plnfrm__field">
                    <label class="plnfrm__label">رنگ کارت</label>
                    <div class="plnfrm__color-row">
                        <input type="color" name="color" id="color-picker" value="{{ old('color', '#4f46e5') }}"
                               class="plnfrm__color-input">
                        <input type="text" id="color-text" value="{{ old('color', '#4f46e5') }}"
                               class="plnfrm__input plnfrm__input--mono" dir="ltr" maxlength="9"
                               placeholder="#4f46e5">
                    </div>
                    {{-- رنگ‌های پیش‌فرض --}}
                    <div class="plnfrm__swatches">
                        @foreach(['#4f46e5','#7c3aed','#0284c7','#059669','#d97706','#e11d48','#475569','#1e293b'] as $sw)
                        <button type="button" class="plnfrm__swatch" style="background:{{ $sw }}"
                                onclick="setColor('{{ $sw }}')" title="{{ $sw }}"></button>
                        @endforeach
                    </div>
                </div>
                <div class="plnfrm__field">
                    <label class="plnfrm__label">متن بج (اختیاری)</label>
                    <input type="text" name="badge_text" value="{{ old('badge_text') }}"
                           placeholder="پیشنهادی / محبوب‌ترین" class="plnfrm__input" maxlength="50">
                </div>
                <div class="plnfrm__field">
                    <label class="plnfrm__label">ترتیب نمایش</label>
                    <input type="number" name="order" value="{{ old('order', 0) }}" min="0" class="plnfrm__input">
                </div>
                <div class="plnfrm__checks">
                    <label class="plnfrm__check">
                        <input type="checkbox" name="highlighted" value="1" {{ old('highlighted') ? 'checked' : '' }}>
                        <span>نمایش به عنوان پیشنهاد ویژه</span>
                    </label>
                    <label class="plnfrm__check">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <span>پلن فعال است</span>
                    </label>
                </div>
            </div>
        </div>

        <button type="submit" class="plnfrm__submit">ذخیره پلن</button>
        <a href="{{ route('admin.subscriptions.index') }}" class="plnfrm__cancel">انصراف</a>

    </div>{{-- /side --}}

</div>{{-- /grid --}}
</form>
</div>

@include('admin.partials.tinymce-field-init', ['selector' => '#subscription-description-editor', 'uploadContext' => 'featured', 'height' => 180])


@push('head')
@include('admin.subscriptions._plan_form_styles')
@endpush

@push('scripts')
@include('admin.subscriptions._plan_form_scripts')
@endpush

@endsection

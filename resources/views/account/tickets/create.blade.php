@extends('account.layout')
@section('title', 'تیکت جدید')
@section('header', 'ثبت تیکت جدید')

@section('content')
<div class="max-w-2xl">

    <div class="mb-4">
        <a href="{{ route('account.tickets.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-indigo-600 transition-colors">
            <x-core-ui::icon name="chevron-left" class="w-4 h-4" />
            بازگشت به تیکت‌ها
        </a>
    </div>

    <div class="account-card overflow-hidden"
         x-data="{ chars: {{ strlen(old('description', '')) }}, maxChars: 5000 }">

        {{-- Gradient header --}}
        <div style="background:linear-gradient(135deg,#4338ca,#6366f1);padding:1.25rem 1.5rem;">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" style="background:rgba(255,255,255,0.2);">
                    <x-core-ui::icon name="edit" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-white font-bold text-base">تیکت پشتیبانی جدید</div>
                    <div class="text-indigo-200 text-sm">تیم پشتیبانی در کوتاه‌ترین زمان پاسخ می‌دهد</div>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-5">
            <form method="POST" action="{{ route('account.tickets.store') }}">
                @csrf

                {{-- Subject --}}
                <div class="form-group mb-0">
                    <label class="form-label">موضوع تیکت <span class="text-red-500">*</span></label>
                    <input type="text" name="subject" value="{{ old('subject') }}" required maxlength="255"
                           class="form-input"
                           placeholder="موضوع مشکل یا سوال خود را به‌صورت خلاصه بنویسید...">
                </div>

                {{-- Priority + Category --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group mb-0">
                        <label class="form-label">اولویت</label>
                        <select name="priority" class="form-input">
                            <option value="normal" @selected(old('priority','normal')==='normal')>عادی</option>
                            <option value="high"   @selected(old('priority')==='high')>بالا (فوری)</option>
                            <option value="low"    @selected(old('priority')==='low')>کم</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">دسته‌بندی</label>
                        <select name="category" class="form-input">
                            <option value="">— انتخاب کنید —</option>
                            <option value="technical"  @selected(old('category')==='technical')>فنی</option>
                            <option value="billing"    @selected(old('category')==='billing')>مالی و پرداخت</option>
                            <option value="product"    @selected(old('category')==='product')>محصول و سفارش</option>
                            <option value="other"      @selected(old('category')==='other')>سایر</option>
                        </select>
                    </div>
                </div>

                {{-- Description --}}
                <div class="form-group mb-0">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="form-label mb-0">توضیحات <span class="text-red-500">*</span></label>
                        <span class="text-xs transition-colors"
                              :class="chars > maxChars * 0.9 ? 'text-red-500 font-medium' : 'text-slate-400'">
                            <span x-text="chars.toLocaleString('fa-IR')"></span> / <span x-text="maxChars.toLocaleString('fa-IR')"></span>
                        </span>
                    </div>
                    <textarea name="description" rows="7" required maxlength="5000"
                              @input="chars = $event.target.value.length"
                              class="form-input resize-y"
                              style="min-height:160px;"
                              placeholder="جزئیات کامل مشکل یا سوال خود را بنویسید. هر چه دقیق‌تر توضیح دهید، سریع‌تر پاسخ می‌گیرید...">{{ old('description') }}</textarea>
                </div>

                <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
                    <button type="submit" class="btn btn-primary">
                        <x-core-ui::icon name="arrow-right" class="w-4 h-4" />
                        ارسال تیکت
                    </button>
                    <a href="{{ route('account.tickets.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tip --}}
    <div class="mt-4 p-4 rounded-xl bg-indigo-50 border border-indigo-100 text-sm text-indigo-700 flex items-start gap-2.5">
        <x-core-ui::icon name="info-circle" class="w-4 h-4 text-indigo-400 shrink-0 mt-0.5" />
        <span>برای پاسخ سریع‌تر، شماره سفارش یا خطاهای دریافتی را در توضیحات ذکر کنید.</span>
    </div>
</div>
@endsection

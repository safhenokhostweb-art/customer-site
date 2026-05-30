@extends('themes.default.layout')

@section('title', 'سبد خرید')

@section('breadcrumb')
    <a href="{{ route('home') }}">خانه</a>
    <span style="margin: 0 0.5rem;">/</span>
    <a href="{{ route('shop.index') }}">فروشگاه</a>
    <span style="margin: 0 0.5rem;">/</span>
    <span>سبد خرید</span>
@endsection

@section('content')
<h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">سبد خرید</h1>

@if(count($cart) > 0)
    <div style="display: grid; gap: 1.5rem; margin-bottom: 2rem;">
        @php $total = 0; @endphp
        @foreach($cart as $lineKey => $item)
            @php
                $sub = $item['price'] * $item['quantity'];
                $total += $sub;
                $lineKey = is_string($lineKey) ? $lineKey : \App\Helpers\ShopCartHelper::lineKey((int)($item['product_id'] ?? $item['id']), $item['product_variant_id'] ?? null);
            @endphp
            <div class="card" style="display: flex; flex-wrap: wrap; align-items: center; gap: 1rem;">
                <div style="flex-shrink: 0; width: 80px; height: 80px; border-radius: 0.5rem; overflow: hidden; background: #f3f4f6;">
                    @if(!empty($item['image']))
                        <img src="{{ \App\Helpers\ImageHelper::url($item['image']) }}" alt="{{ $item['title'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 2rem;">📦</div>
                    @endif
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <h3 style="font-weight: 700; margin-bottom: 0.25rem;">{{ $item['title'] }}</h3>
                    <p style="font-weight: 600; color: var(--platform-primary, #4f46e5);">{!! \App\Helpers\CurrencyHelper::formatPrice($item['price']) !!} {{ \App\Helpers\CurrencyHelper::unitLabel() }}</p>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <form method="post" action="{{ route('shop.cart.update') }}" class="inline" style="display: flex; align-items: center; gap: 0.25rem;">
                        @csrf
                        <input type="hidden" name="line_key" value="{{ $lineKey }}">
                        <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" max="999" style="width: 4rem; padding: 0.375rem; border: 1px solid #d1d5db; border-radius: 0.375rem; text-align: center;">
                        <button type="submit" style="padding: 0.375rem 0.75rem; font-size: 0.875rem; border-radius: 0.375rem; background: #f3f4f6; border: 1px solid #e5e7eb; cursor: pointer;">بروزرسانی</button>
                    </form>
                    <form method="post" action="{{ route('shop.cart.remove') }}" class="inline" onsubmit="return confirm('حذف از سبد؟');">
                        @csrf
                        <input type="hidden" name="line_key" value="{{ $lineKey }}">
                        <button type="submit" style="padding: 0.375rem; color: #dc2626; background: transparent; border: none; cursor: pointer;" title="حذف" aria-label="حذف از سبد">✕</button>
                    </form>
                </div>
                <div style="font-weight: 700;">{!! \App\Helpers\CurrencyHelper::formatPrice($sub) !!} {{ \App\Helpers\CurrencyHelper::unitLabel() }}</div>
            </div>
        @endforeach
    </div>

    <div style="margin-bottom: 2rem; padding: 1.5rem; background: #f9fafb; border-radius: 0.75rem; border: 1px solid #e5e7eb;">
        <p style="font-size: 1.25rem; font-weight: 700;">جمع کل: {!! \App\Helpers\CurrencyHelper::formatPrice($total) !!} {{ \App\Helpers\CurrencyHelper::unitLabel() }}</p>
    </div>

    @if(\App\Helpers\SiteHelper::moduleEnabled('coupon'))
    <div style="margin-bottom: 1.5rem; padding: 1rem; background: #eff6ff; border-radius: 0.5rem; border: 1px solid #bfdbfe;">
        <label for="coupon_code" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">کد تخفیف</label>
        <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;">کد تخفیف خود را در مرحلهٔ تکمیل سفارش وارد کنید.</p>
    </div>
    @endif

    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem;">تکمیل سفارش</h2>
    <div style="display: grid; gap: 1.5rem; max-width: 36rem;">
        @if(session('error'))
            <div style="margin-bottom: 1rem; padding: 0.75rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: 0.5rem; color: #991b1b;">{{ session('error') }}</div>
        @endif
        <form method="post" action="{{ route('shop.checkout') }}">
            @csrf
            @if(\App\Helpers\SiteHelper::moduleEnabled('coupon'))
            <div style="margin-bottom: 1rem;">
                <label for="coupon_code" class="form-label">کد تخفیف</label>
                <input type="text" id="coupon_code" name="coupon_code" class="form-input font-mono" value="{{ old('coupon_code') }}" placeholder="مثلاً WELCOME20">
            </div>
            @endif
            <div style="margin-bottom: 1rem;">
                <label for="customer_name" class="form-label">نام</label>
                <input type="text" id="customer_name" name="customer_name" class="form-input" value="{{ old('customer_name') }}" required>
                @error('customer_name')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div style="margin-bottom: 1rem;">
                <label for="customer_mobile" class="form-label">موبایل</label>
                <input type="text" id="customer_mobile" name="customer_mobile" class="form-input" value="{{ old('customer_mobile') }}" required>
                @error('customer_mobile')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div style="margin-bottom: 1rem;">
                <label for="customer_phone" class="form-label">تلفن</label>
                <input type="text" id="customer_phone" name="customer_phone" class="form-input" value="{{ old('customer_phone') }}">
            </div>
            <div style="margin-bottom: 1rem;">
                <label for="customer_address" class="form-label">آدرس</label>
                <textarea id="customer_address" name="customer_address" class="form-textarea" rows="3" required>{{ old('customer_address') }}</textarea>
                @error('customer_address')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div style="margin-bottom: 1rem;">
                <label for="note" class="form-label">یادداشت (اختیاری)</label>
                <textarea id="note" name="note" class="form-textarea" rows="2">{{ old('note') }}</textarea>
            </div>
            @include('partials.shop-captcha')
            <button type="submit" style="padding: 0.75rem 1.5rem; font-weight: 600; border-radius: 0.5rem; color: white; border: none; cursor: pointer; background: var(--platform-button-primary-bg, #4f46e5);">ثبت سفارش</button>
        </form>
    </div>
@else
    <div class="card" style="text-align: center; padding: 3rem;">
        <p style="font-size: 1.125rem; color: #6b7280; margin-bottom: 1.5rem;">سبد خرید شما خالی است.</p>
        <a href="{{ route('shop.index') }}" style="display: inline-block; padding: 0.75rem 1.5rem; font-weight: 600; border-radius: 0.5rem; color: white; text-decoration: none; background: var(--platform-button-primary-bg, #4f46e5);">مشاهده فروشگاه</a>
    </div>
@endif
@endsection

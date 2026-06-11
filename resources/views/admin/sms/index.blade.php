@extends('admin.layout')
@section('title', 'تنظیمات پیامک')
@section('header', 'پیامک')
@push('head')
<style>
.sms-provider-card{padding:1rem 1.25rem;border-radius:.875rem;border:2px solid #e2e8f0;background:#fff;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:.75rem}
.sms-provider-card:hover{border-color:#a5b4fc;box-shadow:0 4px 12px rgba(99,102,241,.1)}
.sms-provider-card.selected{border-color:#6366f1;background:#f5f3ff}
</style>
@endpush
@section('content')

@include('admin.partials.breadcrumb', ['crumbs' => [
    ['label' => 'داشبورد', 'url' => route('admin.dashboard')],
    ['label' => 'پیامک', 'url' => null],
]])

@if(session('success'))
    <div class="alert alert-success mb-4">{{ session('success') }}</div>
@endif

<div class="admin-page" style="max-width:700px">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">تنظیمات سرویس پیامک</h1>
            <p class="admin-section-lead" style="margin-top:.3rem">ارتباط با کاربران از طریق پیامک — برای OTP، اطلاع‌رسانی و تأیید سفارش.</p>
        </div>
    </div>

    {{-- Current Status --}}
    <div class="card" style="padding:1rem 1.25rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:1rem">
        @if($provider)
            <div style="width:2.5rem;height:2.5rem;border-radius:.625rem;background:#dcfce7;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0"/></svg>
            </div>
            <div>
                <div style="font-size:.88rem;font-weight:700;color:#166534">سرویس فعال: {{ strtoupper($provider) }}</div>
                <div style="font-size:.75rem;color:#64748b">
                    @if($lineNumber) خط ارسال: <span dir="ltr">{{ $lineNumber }}</span> @endif
                    @if($apiKey) | کلید API تنظیم شده @endif
                </div>
            </div>
        @else
            <div style="width:2.5rem;height:2.5rem;border-radius:.625rem;background:#fef9c3;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div style="font-size:.88rem;font-weight:700;color:#92400e">سرویس پیامک تنظیم نشده</div>
        @endif
    </div>

    {{-- Provider Cards --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1.5rem">
        @foreach([
            ['kavenegar',   'کاوه‌نگار',    '📱', 'پرکاربردترین سرویس ایرانی'],
            ['ghasedak',    'قاصدک',         '🕊️', 'قیمت رقابتی'],
            ['melipayamak', 'ملی پیامک',     '📨', 'بانک ملی'],
            ['other',       'سایر',          '⚙️', 'سرویس دلخواه'],
        ] as [$val, $name, $icon, $desc])
            <div class="sms-provider-card {{ $provider === $val ? 'selected' : '' }}"
                 onclick="document.getElementById('sms_provider').value='{{ $val }}'">
                <div style="width:2.25rem;height:2.25rem;border-radius:.5rem;background:#ede9fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem">{{ $icon }}</div>
                <div>
                    <div style="font-size:.9rem;font-weight:700;color:#1e293b">{{ $name }}</div>
                    <div style="font-size:.72rem;color:#64748b">{{ $desc }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Settings Form --}}
    <div class="card">
        <div style="padding:.875rem 1.25rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-weight:700;font-size:.88rem;color:#1e293b">
            پارامترهای اتصال
        </div>
        <div style="padding:1.25rem">
            <form action="{{ route('admin.sms.update') }}" method="post">
                @csrf
                <div class="form-group">
                    <label class="form-label">سرویس</label>
                    <input type="text" id="sms_provider" name="sms_provider"
                           value="{{ old('sms_provider', $provider) }}"
                           placeholder="kavenegar / ghasedak / melipayamak" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">کلید API</label>
                    <input type="password" name="sms_api_key"
                           value="{{ old('sms_api_key', $apiKey) }}"
                           placeholder="کلید API" class="form-control" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label class="form-label">شماره خط ارسال</label>
                    <input type="text" name="sms_line_number"
                           value="{{ old('sms_line_number', $lineNumber) }}"
                           placeholder="مثال: 10008566" class="form-control" dir="ltr">
                </div>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    ذخیره تنظیمات
                </button>
            </form>
        </div>
    </div>

    <div class="card" style="padding:1rem 1.25rem;margin-top:1rem;background:#f8fafc">
        <div style="font-size:.8rem;color:#64748b;line-height:1.8">
            <strong style="color:#1e293b">راهنما:</strong>
            کلید API و شماره خط را از پنل کاربری سرویس پیامک خود دریافت کنید.
            سرویس‌های پشتیبانی‌شده: کاوه‌نگار، قاصدک، ملی‌پیامک.
        </div>
    </div>
</div>
@endsection

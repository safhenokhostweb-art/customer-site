@extends('admin.layout')
@section('title', 'تنظیمات پیامک')
@section('header', 'یکپارچه‌سازی پیامک')
@section('content')
<div class="max-w-2xl">
    <div class="card">
        <div class="card-header">تنظیمات سرویس پیامک</div>
        <div class="card-body">
            <p class="text-slate-600 text-sm mb-6">پیکربندی سرویس پیامک (کاوه‌نگار، قاصدک، ملی‌پیامک و …) برای ارسال پیامک از طریق سایت.</p>
            <form action="{{ route('admin.sms.update') }}" method="post">
                @csrf
                <div class="form-group">
                    <label class="form-label">سرویس</label>
                    <input type="text" name="sms_provider" value="{{ old('sms_provider', $provider) }}" placeholder="kavenegar">
                </div>
                <div class="form-group">
                    <label class="form-label">کلید API</label>
                    <input type="text" name="sms_api_key" value="{{ old('sms_api_key', $apiKey) }}" placeholder="کلید API سرویس">
                </div>
                <div class="form-group">
                    <label class="form-label">شماره خط</label>
                    <input type="text" name="sms_line_number" value="{{ old('sms_line_number', $lineNumber) }}" placeholder="شماره خط ارسال">
                </div>
                <button type="submit" class="btn btn-primary mt-4">ذخیره تنظیمات</button>
            </form>
        </div>
    </div>
</div>
@endsection

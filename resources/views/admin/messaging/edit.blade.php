@extends('admin.layout')
@section('title', 'ویرایش کانال')
@section('header', 'ویرایش کانال')
@section('content')
<div class="admin-page max-w-xl">
    <p class="admin-section-lead">تنظیمات این کانال ارتباطی را ویرایش کنید. تغییرات بلافاصله روی ویجت ارتباطی سایت اعمال می‌شود.</p>
    <div class="card">
        <div class="card-header">{{ $platforms[$channel->platform]['name'] ?? $channel->platform }}</div>
        <div class="card-body">
            <form action="{{ route('admin.messaging.update', $channel) }}" method="post" class="admin-form-stack">
                @csrf
                @method('PUT')
                @if($channel->platform === 'whatsapp')
                    <div class="form-group">
                        <label class="form-label">شماره (با کد کشور)</label>
                        <input type="text" name="phone" value="{{ old('phone', $channel->phone) }}" placeholder="989123456789" dir="ltr">
                    </div>
                    <div class="form-group">
                        <label class="form-label">پیام پیش‌فرض</label>
                        <input type="text" name="message" value="{{ old('message', $channel->message) }}">
                    </div>
                @else
                    <div class="form-group">
                        <label class="form-label">لینک یا آیدی</label>
                        <input type="text" name="url" value="{{ old('url', $channel->url) }}" placeholder="https:// یا آیدی (مثلاً @username برای تلگرام)">
                    </div>
                @endif
                @if($channel->platform === 'custom')
                    <div class="form-group">
                        <label class="form-label">عنوان نمایشی</label>
                        <input type="text" name="custom_label" value="{{ old('custom_label', $channel->custom_label) }}" placeholder="نام شبکهٔ سفارشی">
                    </div>
                @endif
                <div class="form-group">
                    <label class="form-label">لوگوی سفارشی (اختیاری)</label>
                    <p class="admin-muted-note mb-2">در صورت خالی بودن، لوگوی پیش‌فرض شبکه نمایش داده می‌شود.</p>
                    @include('components.image-picker', ['name' => 'custom_image', 'value' => old('custom_image', $channel->custom_image ?? ''), 'context' => 'icon', 'label' => 'تصویر'])
                </div>
                <div class="form-group">
                    <label class="admin-form-inline-check cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $channel->is_active) ? 'checked' : '' }}>
                        <span>فعال</span>
                    </label>
                </div>
                <div class="admin-page-tools mt-4">
                    <button type="submit" class="btn btn-primary">ذخیره</button>
                    <a href="{{ route('admin.whatsapp.index') }}" class="btn btn-secondary">بازگشت</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

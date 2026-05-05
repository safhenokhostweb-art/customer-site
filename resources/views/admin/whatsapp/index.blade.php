@extends('admin.layout')
@section('title', 'تنظیمات واتساپ')
@section('header', 'ویجت واتساپ')
@section('content')
<div class="max-w-2xl">
    <div class="card">
        <div class="card-header">تنظیمات دکمه تماس واتساپ</div>
        <div class="card-body">
            <p class="text-slate-600 text-sm mb-6">دکمه شناور واتساپ در گوشهٔ سایت نمایش داده می‌شود و کاربران با کلیک به چت واتساپ هدایت می‌شوند.</p>
            <form action="{{ route('admin.whatsapp.update') }}" method="post">
                @csrf
                <div class="form-group">
                    <label class="form-label">شماره واتساپ (با کد کشور، بدون +)</label>
                    <input type="text" name="whatsapp_phone" value="{{ old('whatsapp_phone', $phone) }}" placeholder="989123456789" dir="ltr">
                </div>
                <div class="form-group">
                    <label class="form-label">پیام پیش‌فرض</label>
                    <input type="text" name="whatsapp_message" value="{{ old('whatsapp_message', $message) }}" placeholder="سلام، می‌خواستم در مورد...">
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="whatsapp_enabled" value="1" {{ $enabled ? 'checked' : '' }}>
                        <span>نمایش دکمه واتساپ در سایت</span>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary mt-4">ذخیره تنظیمات</button>
            </form>
        </div>
    </div>
</div>
@endsection

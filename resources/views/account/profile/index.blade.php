@extends('account.layout')
@section('title', 'پروفایل')
@section('header', 'پروفایل من')

@section('content')
<div class="space-y-6 max-w-3xl">
    {{-- Avatar --}}
    <div class="account-card">
        <div class="account-card-header">تصویر پروفایل</div>
        <div class="account-card-body flex flex-col sm:flex-row items-start gap-6">
            <div class="shrink-0">
                @if($user->avatar)
                    <img src="{{ $user->avatar_url }}" alt="پروفایل" class="w-24 h-24 rounded-full object-cover border-2 border-slate-200">
                @else
                    <div class="w-24 h-24 rounded-full bg-slate-200 flex items-center justify-center text-3xl text-slate-500 font-bold">{{ mb_substr($user->name, 0, 1) }}</div>
                @endif
            </div>
            <div class="flex-1">
                <form method="POST" action="{{ route('account.profile.avatar.update') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" class="form-input" required>
                        <p class="text-xs text-slate-500 mt-1">فرمت‌های مجاز: JPG, PNG, GIF, WebP. حداکثر ۲ مگابایت.</p>
                    </div>
                    <button type="submit" class="btn btn-primary">آپلود تصویر</button>
                </form>
                @if($user->avatar)
                    <form method="POST" action="{{ route('account.profile.avatar.destroy') }}" class="mt-3 inline" onsubmit="return confirm('آیا از حذف تصویر اطمینان دارید؟');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-secondary btn-sm text-red-600">حذف تصویر</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Personal & Contact --}}
    <div class="account-card">
        <div class="account-card-header">اطلاعات شخصی و تماس</div>
        <div class="account-card-body">
            <form method="POST" action="{{ route('account.profile.update') }}">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group md:col-span-2">
                        <label class="form-label">نام *</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">ایمیل *</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">موبایل</label>
                        <input type="text" name="mobile" value="{{ old('mobile', $user->mobile) }}" class="form-input">
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">درباره من</label>
                        <textarea name="bio" rows="3" class="form-input">{{ old('bio', $user->bio) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">تاریخ تولد (شمسی)</label>
                        <x-persian-date-input name="birth_date" :value="old('birth_date', $user->birth_date ? \App\Helpers\Jalali::format($user->birth_date, 'Y/m/d') : '')" placeholder="۱۴۰۳/۰۱/۰۱" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">تلفن</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">فکس</label>
                        <input type="text" name="fax" value="{{ old('fax', $user->fax) }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">وب‌سایت</label>
                        <input type="url" name="website" value="{{ old('website', $user->website) }}" class="form-input">
                    </div>
                </div>
                <hr class="my-6 border-slate-200">
                <h3 class="font-semibold text-slate-800 mb-4">اطلاعات شرکت (اختیاری)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">نام شرکت</label>
                        <input type="text" name="company_name" value="{{ old('company_name', $user->company_name) }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">سمت</label>
                        <input type="text" name="company_role" value="{{ old('company_role', $user->company_role) }}" class="form-input">
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">آدرس شرکت</label>
                        <textarea name="company_address" rows="2" class="form-input">{{ old('company_address', $user->company_address) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">تلفن شرکت</label>
                        <input type="text" name="company_phone" value="{{ old('company_phone', $user->company_phone) }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">ایمیل شرکت</label>
                        <input type="email" name="company_email" value="{{ old('company_email', $user->company_email) }}" class="form-input">
                    </div>
                </div>
                <hr class="my-6 border-slate-200">
                <h3 class="font-semibold text-slate-800 mb-4">تنظیمات اعلان</h3>
                <div class="space-y-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="notify_email" value="0">
                        <input type="checkbox" name="notify_email" value="1" {{ $user->getNotificationPreference('notify_email', true) ? 'checked' : '' }}>
                        <span>اعلان‌های ایمیلی</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="notify_sms" value="0">
                        <input type="checkbox" name="notify_sms" value="1" {{ $user->getNotificationPreference('notify_sms') ? 'checked' : '' }}>
                        <span>اعلان‌های پیامکی</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="marketing" value="0">
                        <input type="checkbox" name="marketing" value="1" {{ $user->getNotificationPreference('marketing') ? 'checked' : '' }}>
                        <span>ایمیل‌های تبلیغاتی</span>
                    </label>
                </div>
                <hr class="my-6 border-slate-200">
                <h3 class="font-semibold text-slate-800 mb-4">تنظیمات حریم خصوصی</h3>
                <div class="space-y-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="show_email" value="0">
                        <input type="checkbox" name="show_email" value="1" {{ $user->getPrivacySetting('show_email') ? 'checked' : '' }}>
                        <span>نمایش ایمیل به دیگران</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="show_phone" value="0">
                        <input type="checkbox" name="show_phone" value="1" {{ $user->getPrivacySetting('show_phone') ? 'checked' : '' }}>
                        <span>نمایش شماره تماس به دیگران</span>
                    </label>
                </div>
                <div class="mt-6">
                    <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Quick links --}}
    <div class="account-card">
        <div class="account-card-header">بخش‌های مرتبط</div>
        <div class="account-card-body flex flex-wrap gap-3">
            <a href="{{ route('account.profile.addresses.index') }}" class="btn btn-secondary">مدیریت آدرس‌ها</a>
            <a href="{{ route('account.profile.activity.index') }}" class="btn btn-secondary">فعالیت‌های اخیر</a>
            <a href="{{ route('account.security.index') }}" class="btn btn-secondary">رمز عبور و امنیت</a>
            <a href="{{ route('account.profile.deletion.index') }}" class="btn btn-secondary text-red-600">درخواست حذف حساب</a>
        </div>
    </div>
</div>

@push('head')
<link rel="stylesheet" href="https://unpkg.com/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
@endpush
@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://unpkg.com/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://unpkg.com/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.persian-date-input') && typeof jQuery !== 'undefined' && jQuery.fn.persianDatepicker) {
        jQuery('.persian-date-input').persianDatepicker({ format: 'YYYY/MM/DD', persianDigit: true, initialValueType: 'persian' });
    }
});
</script>
@endpush
@endsection

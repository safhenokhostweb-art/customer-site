@extends('account.layout')
@section('title', 'امنیت')
@section('header', 'تنظیمات امنیتی')

@section('content')
<div class="space-y-6 max-w-2xl">
    <div class="account-card">
        <div class="account-card-header">تغییر رمز عبور</div>
        <div class="account-card-body">
            <form method="POST" action="{{ route('account.security.password') }}">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">رمز عبور فعلی *</label>
                    <input type="password" name="current_password" required class="form-input" autocomplete="current-password">
                    @error('current_password')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">رمز عبور جدید *</label>
                    <input type="password" name="password" required class="form-input" autocomplete="new-password">
                    @error('password')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-slate-500 mt-1">حداقل ۸ نویسه.</p>
                </div>
                <div class="form-group">
                    <label class="form-label">تکرار رمز عبور جدید *</label>
                    <input type="password" name="password_confirmation" required class="form-input" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary">ذخیره رمز عبور</button>
            </form>
        </div>
    </div>

    <div class="account-card">
        <div class="account-card-header">احراز هویت دو مرحله‌ای (۲FA)</div>
        <div class="account-card-body">
            <p class="text-slate-600">احراز هویت دو مرحله‌ای در نسخهٔ فعلی فعال نیست. در به‌روزرسانی‌های بعدی اضافه خواهد شد.</p>
        </div>
    </div>

    <div class="account-card">
        <div class="account-card-header">نشست‌های فعال</div>
        <div class="account-card-body">
            @if(empty($sessions))
                <p class="text-slate-600">نشست‌های فعال با درایور دیتابیس نمایش داده می‌شوند. در حال حاضر از درایور فایل استفاده می‌شود.</p>
            @else
                <div class="space-y-3">
                    @foreach($sessions as $s)
                        <div class="flex justify-between items-center p-3 rounded-lg border border-slate-200 {{ $s->is_current ? 'bg-indigo-50 border-indigo-200' : '' }}">
                            <div>
                                <div class="font-medium">{{ $s->ip_address ?? 'نامشخص' }}</div>
                                <div class="text-sm text-slate-500">{{ Str::limit($s->user_agent, 50) }}</div>
                                <div class="text-xs text-slate-400">{{ \App\Helpers\Jalali::formatFa(\Carbon\Carbon::createFromTimestamp($s->last_activity), 'Y/m/d H:i') }}</div>
                            </div>
                            @if($s->is_current)
                                <span class="badge badge-info">نشست فعلی</span>
                            @else
                                <form method="POST" action="{{ route('account.security.sessions.destroy', $s->id) }}" class="inline" onsubmit="return confirm('آیا از خروج این دستگاه اطمینان دارید؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">خروج</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

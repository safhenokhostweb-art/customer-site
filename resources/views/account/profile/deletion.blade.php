@extends('account.layout')
@section('title', 'حذف حساب')
@section('header', 'درخواست حذف حساب')

@section('content')
<div class="space-y-6 max-w-2xl">
    <div class="account-card">
        <div class="account-card-body">
            @if($pendingRequest)
                <div class="p-4 rounded-lg bg-amber-50 border border-amber-200 mb-6">
                    <p class="font-medium text-amber-800">درخواست حذف حساب شما در حال بررسی است.</p>
                    <p class="text-sm text-amber-700 mt-1">تاریخ درخواست: {{ \App\Helpers\Jalali::formatFa($pendingRequest->requested_at, 'Y/m/d H:i') }}</p>
                    @if($pendingRequest->expires_at)
                        <p class="text-sm text-amber-700 mt-1">مهلت لغو: {{ \App\Helpers\Jalali::formatFa($pendingRequest->expires_at, 'Y/m/d') }}</p>
                    @endif
                    <form method="POST" action="{{ route('account.profile.deletion.cancel') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="btn btn-secondary">لغو درخواست حذف</button>
                    </form>
                </div>
            @else
                <div class="space-y-4">
                    <p class="text-slate-600">با حذف حساب، تمام اطلاعات شما از جمله پروفایل، سفارشات و اشتراک‌ها حذف خواهد شد. این عمل قابل بازگشت نیست.</p>
                    <form method="POST" action="{{ route('account.profile.deletion.store') }}">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">دلیل حذف (اختیاری)</label>
                            <textarea name="reason" rows="3" class="form-input" placeholder="در صورت تمایل دلیل خود را بنویسید"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">رمز عبور *</label>
                            <input type="password" name="password" required class="form-input" autocomplete="current-password" placeholder="رمز عبور فعلی">
                            @error('password')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-slate-500 mt-1">برای تأیید حذف حساب، رمز عبور خود را وارد کنید.</p>
                        </div>
                        <button type="submit" class="btn btn-danger" onclick="return confirm('آیا از حذف حساب خود اطمینان دارید؟ این عمل قابل بازگشت نیست.');">ثبت درخواست حذف حساب</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <a href="{{ route('account.profile.index') }}" class="btn btn-secondary">بازگشت به پروفایل</a>
</div>
@endsection

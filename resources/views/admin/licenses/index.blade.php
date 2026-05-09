@extends('admin.layout')
@section('title', 'لایسنس')
@section('header', 'مدیریت لایسنس')
@section('content')
<div class="admin-page">
    @if(\App\Helpers\SiteHelper::isOwnerSite())
        <div class="admin-flash admin-flash--success">
            <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-7V6a1 1 0 112 0v5a1 1 0 01-2 0zm1 5a1.25 1.25 0 100-2.5A1.25 1.25 0 0010 16z" clip-rule="evenodd"/></svg>
            <span><strong>سایت مالک:</strong> این نصب به‌عنوان سایت فروش تنظیم شده و لایسنس اختیاری است. با ثبت لایسنس، بروزرسانی و ظاهر از پلتفرم دریافت می‌شود.</span>
        </div>
    @endif

    @if(session('update_available'))
        @php $ua = session('update_available'); @endphp
        <div class="admin-flash admin-flash--warning">
            <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <span>
                <strong>بروزرسانی جدید موجود است:</strong> نسخه {{ $ua['latest_version'] ?? '—' }}
                @if(!empty($ua['changelog'])) — {{ $ua['changelog'] }} @endif
            </span>
        </div>
    @endif

    <div class="card">
        <div class="card-header">وضعیت فعلی لایسنس</div>
        <div class="card-body">
            @if($license)
                <div class="table-responsive">
                    <table>
                        <tbody>
                            <tr><th style="width: 180px;">وضعیت</th><td><strong>{{ $license->statusLabel() }}</strong></td></tr>
                            <tr><th>کلید (نمایش)</th><td><code>{{ $license->maskedKey() }}</code></td></tr>
                            <tr><th>دامنه</th><td>{{ $license->domain ?: '—' }}</td></tr>
                            <tr><th>تاریخ انقضا</th><td>{{ $license->expires_at ? \App\Helpers\Jalali::formatFa($license->expires_at, 'Y/m/d') : '—' }}</td></tr>
                            @if($license->support_expires_at)
                                <tr><th>انقضای پشتیبانی</th><td>{{ \App\Helpers\Jalali::formatFa($license->support_expires_at, 'Y/m/d') }}</td></tr>
                            @endif
                            @if(!empty($license->modules))
                                <tr><th>ماژول‌های فعال</th><td>{{ implode('، ', $license->modules) }}</td></tr>
                            @else
                                <tr><th>ماژول‌های فعال</th><td><span class="admin-muted-note">خالی — در پلتفرم ماژول‌ها را به این سایت تخصیص دهید و سپس «بروزرسانی ماژول‌ها» را بزنید.</span></td></tr>
                            @endif
                            <tr><th>دامنه فعلی درخواست</th><td><code>{{ request()->getHost() }}</code></td></tr>
                        </tbody>
                    </table>
                </div>
            @else
                <p class="admin-muted-note">هنوز لایسنس ثبت نشده است. کلید را در فرم زیر وارد کرده و «فعال‌سازی» را بزنید.</p>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">فعال‌سازی / بروزرسانی لایسنس</div>
        <div class="card-body">
            <p class="admin-muted-note" style="margin-bottom:1rem;">
                کلید لایسنس را از پلتفرم دریافت کرده و اینجا وارد کنید. اعتبار از طریق سرور پلتفرم بررسی می‌شود.
                @if(\App\Helpers\SiteHelper::isOwnerSite()) در حالت سایت مالک این فیلد اختیاری است. @endif
            </p>

            @if($license)
                <div class="admin-page-tools" style="margin-bottom:1rem;">
                    <form action="{{ route('admin.license.check-update') }}" method="post">
                        @csrf
                        <button type="submit" class="btn btn-secondary">بررسی بروزرسانی</button>
                    </form>
                    <form action="{{ route('admin.license.refresh-config') }}" method="post">
                        @csrf
                        <button type="submit" class="btn btn-secondary">بازخوانی تنظیمات و اعلان‌ها</button>
                    </form>
                    <form action="{{ route('admin.license.refresh-modules') }}" method="post">
                        @csrf
                        <button type="submit" class="btn btn-secondary">بروزرسانی ماژول‌ها</button>
                    </form>
                    <a href="{{ route('admin.modules.changelog') }}" class="btn btn-secondary">تاریخچه نسخه ماژول‌ها</a>
                    <a href="{{ route('admin.license.test-config') }}" target="_blank" class="btn btn-secondary">تست API اعلان‌ها</a>
                </div>
            @endif

            <form action="{{ route('admin.license.update') }}" method="post" style="max-width: 560px;">
                @csrf
                <div class="form-group">
                    <label class="form-label">کلید لایسنس @if(\App\Helpers\SiteHelper::isOwnerSite())(اختیاری)@endif</label>
                    <input type="text" name="license_key" value="{{ old('license_key') }}" placeholder="XXXX-XXXX-XXXX-XXXX" autocomplete="off" @if(!\App\Helpers\SiteHelper::isOwnerSite()) required @endif>
                </div>
                <div class="form-group">
                    <label class="form-label">دامنه این سایت (خالی = دامنه فعلی)</label>
                    <input type="text" name="domain" value="{{ old('domain', $license?->domain ?? request()->getHost()) }}" placeholder="{{ request()->getHost() }}">
                </div>
                <button type="submit" class="btn btn-primary">فعال‌سازی لایسنس</button>
            </form>
        </div>
    </div>
</div>
@endsection

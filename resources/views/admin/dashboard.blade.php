@extends('admin.layout')
@section('title', 'داشبورد')
@section('header', 'داشبورد')
@section('content')
<div class="admin-dashboard-page">

@php
    $license = $license ?? null;
    $moduleUpdates = [];
    if ($license && $license->license_key && $license->isValid()) {
        $result = app(\App\Services\UpdateCheckService::class)->checkModuleUpdates();
        if (!empty($result['success']) && !empty($result['modules'])) {
            $moduleUpdates = $result['modules'];
        }
    }
@endphp

{{-- هشدارهای مهم از پلتفرم (پس‌زمینه مشخص و چشمک‌زن) --}}
@if(isset($importantAlerts) && $importantAlerts->isNotEmpty())
<div class="mb-6 rounded-xl overflow-hidden border-2 border-amber-400 shadow-lg" id="dashboard-important-alerts">
    <style>
        @keyframes dashboard-alert-pulse {
            0%, 100% { opacity: 1; background-color: #fef3c7; }
            50% { opacity: 0.92; background-color: #fde68a; }
        }
        .dashboard-important-alert-item {
            animation: dashboard-alert-pulse 2s ease-in-out infinite;
        }
        .dashboard-important-alert-item:nth-child(even) { animation-delay: 0.5s; }
    </style>
    <div class="px-4 py-3 bg-amber-500 text-amber-950 font-bold text-lg flex items-center gap-2">
        <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        هشدارهای مهم
    </div>
    <ul class="divide-y divide-amber-200/80">
        @foreach($importantAlerts as $alert)
        <li class="dashboard-important-alert-item px-4 py-4 bg-amber-100/90 hover:bg-amber-100">
            <div class="font-semibold text-amber-900">{{ $alert['title'] ?? 'هشدار' }}</div>
            @if(!empty($alert['body']))
                <p class="text-sm text-amber-800 mt-1">{{ $alert['body'] }}</p>
            @endif
            @if(!empty($alert['link']))
                <a href="{{ $alert['link'] }}" target="_blank" rel="noopener" class="inline-block mt-2 text-sm font-medium text-amber-700 hover:text-amber-900 underline">{{ $alert['link_text'] ?? 'مشاهده' }}</a>
            @endif
        </li>
        @endforeach
    </ul>
</div>
@endif

@if(!empty($moduleUpdates))
    <div class="card p-4 mb-6 bg-indigo-50 border-indigo-200">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <div class="flex-1">
                <h3 class="font-semibold text-indigo-900 mb-1">بروزرسانی ماژول‌ها موجود است</h3>
                <p class="text-sm text-indigo-800 mb-2">{{ count($moduleUpdates) }} ماژول بروزرسانی دارد.</p>
                <a href="{{ route('admin.modules.index') }}" class="btn btn-primary btn-sm">مشاهده و دریافت</a>
            </div>
        </div>
    </div>
@endif

<p class="text-slate-600 text-sm mb-4">خلاصهٔ محتوا و وضعیت سایت — از کاشی‌ها برای ورود سریع استفاده کنید.</p>

<div class="admin-dashboard-tiles">
    <a href="{{ route('admin.pages.index') }}" class="admin-dashboard-tile admin-dashboard-tile--slate">
        <svg class="admin-dashboard-tile__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span class="admin-dashboard-tile__label">صفحات</span>
        <span class="admin-dashboard-tile__value">{{ number_format($stats['pages']) }}</span>
        <span class="admin-dashboard-tile__hint">مدیریت صفحات ثابت</span>
    </a>
    <a href="{{ route('admin.posts.index') }}" class="admin-dashboard-tile admin-dashboard-tile--indigo">
        <svg class="admin-dashboard-tile__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
        <span class="admin-dashboard-tile__label">مطالب وبلاگ</span>
        <span class="admin-dashboard-tile__value">{{ number_format($stats['posts']) }}</span>
        <span class="admin-dashboard-tile__hint">نوشته‌ها و دسته‌ها</span>
    </a>
    @if(\App\Helpers\SiteHelper::shopEnabled())
        <a href="{{ route('admin.products.index') }}" class="admin-dashboard-tile admin-dashboard-tile--emerald">
            <svg class="admin-dashboard-tile__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <span class="admin-dashboard-tile__label">محصولات</span>
            <span class="admin-dashboard-tile__value">{{ number_format($stats['products']) }}</span>
            <span class="admin-dashboard-tile__hint">فروشگاه</span>
        </a>
        <a href="{{ route('admin.orders.index') }}" class="admin-dashboard-tile admin-dashboard-tile--amber">
            <svg class="admin-dashboard-tile__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            <span class="admin-dashboard-tile__label">سفارشات</span>
            <span class="admin-dashboard-tile__value">{{ number_format($stats['orders']) }}</span>
            <span class="admin-dashboard-tile__hint">پیگیری سفارش</span>
        </a>
    @endif
    <a href="{{ route('admin.settings.index') }}" class="admin-dashboard-tile admin-dashboard-tile--sky">
        <svg class="admin-dashboard-tile__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span class="admin-dashboard-tile__label">تنظیمات سایت</span>
        <span class="admin-dashboard-tile__value" style="font-size:1rem;font-weight:700">پیکربندی</span>
        <span class="admin-dashboard-tile__hint">نام سایت، تم، دربارهٔ ما</span>
    </a>
    <a href="{{ route('admin.license.index') }}" class="admin-dashboard-tile admin-dashboard-tile--violet">
        <svg class="admin-dashboard-tile__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
        <span class="admin-dashboard-tile__label">لایسنس</span>
        <span class="admin-dashboard-tile__value" style="font-size:1rem;font-weight:700">کلید و ماژول</span>
        <span class="admin-dashboard-tile__hint">اعتبارسنجی و بروزرسانی</span>
    </a>
    <a href="{{ route('admin.modules.index') }}" class="admin-dashboard-tile admin-dashboard-tile--rose">
        <svg class="admin-dashboard-tile__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
        <span class="admin-dashboard-tile__label">ماژول‌ها</span>
        <span class="admin-dashboard-tile__value" style="font-size:1rem;font-weight:700">دریافت و وضعیت</span>
        <span class="admin-dashboard-tile__hint">همگام با پلتفرم</span>
    </a>
</div>

@if(\App\Helpers\SiteHelper::shopEnabled() && $recentOrders->isNotEmpty())
    <h2 class="text-lg font-semibold mb-4">آخرین سفارشات</h2>
    <div class="card">
        <div class="table-responsive">
            <table>
        <thead>
            <tr><th>شماره</th><th>مشتری</th><th>مبلغ</th><th>وضعیت</th><th></th></tr>
        </thead>
        <tbody>
            @foreach($recentOrders as $order)
                <tr>
                    <td>{{ $order->number }}</td>
                    <td>{{ $order->customer_name }}</td>
                    <td>{{ number_format($order->total) }} تومان</td>
                    <td>{{ $order->status }}</td>
                    <td><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary">مشاهده</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
        </div>
    </div>
@endif

{{-- وضعیت ماژول‌ها و امکانات از پلتفرم: عنوان و توضیح در یک سمت، جدول محتوا روبرو (در RTL کنار هم) --}}
<div class="dashboard-modules-block">
    <div class="dashboard-modules-shell">
        <div class="dashboard-modules-header">
            <h2 class="dashboard-modules-title">ماژول‌ها و امکانات از پلتفرم</h2>
            <p class="dashboard-modules-lead">
                مقایسهٔ ماژول‌های دریافتی از پلتفرم با وضعیت فعال بودن در این سایت. ماژول‌های پلتفرم از صفحهٔ
                <a href="{{ route('admin.license.index') }}">لایسنس</a>
                یا
                <a href="{{ route('admin.modules.index') }}">ماژول‌ها</a>
                با «بروزرسانی ماژول‌ها» به‌روز می‌شوند. اگر ماژولی از پلتفرم نیامد، در saas-platform آن را به سایت تخصیص دهید.
                برای ماژول‌های محلی می‌توانید در <code>.env</code> فعال کنید. اعلان‌های ویژهٔ بازدیدکنندگان و کاربران این سایت
                از طریق ماژول «اعلان‌ها» در همین پنل مدیریت تنظیم می‌شوند؛ اعلان‌های ادمین برای این سایت همچنان از پلتفرم اصلی (saas-platform) ارسال می‌شوند.
            </p>
            @php
                $license = $license ?? null;
                $modulesFromPlatform = $modulesFromPlatform ?? [];
                $localModules = $localModules ?? [];
            @endphp
            <div class="dashboard-license-state">
                <strong>وضعیت لایسنس:</strong>
                @if(!$license || empty($license->license_key))
                    <span class="dashboard-license-license-warning">تنظیم نشده</span> — در <a href="{{ route('admin.license.index') }}">صفحهٔ لایسنس</a> کلید را وارد کنید.
                @elseif(!$license->isValid())
                    <span class="dashboard-license-license-danger">نامعتبر یا منقضی</span> — دامنه یا انقضا را بررسی کنید.
                @else
                    <span class="dashboard-license-license-success">فعال</span>
                    @if(!empty($license->modules))
                        — ماژول‌های دریافتی: {{ implode('، ', $license->modules) }}
                    @endif
                @endif
            </div>
        </div>
        <div class="dashboard-modules-content">
            @if(count($localModules) > 0)
                <table class="dashboard-modules-table">
                    <thead>
                        <tr>
                            <th>ماژول</th>
                            <th>از پلتفرم</th>
                            <th>فعال در این سایت</th>
                            <th>وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($localModules as $key => $info)
                            @php
                                $fromPlatform = in_array($key, $modulesFromPlatform ?? [], true)
                                    || ($key === 'whatsapp' && in_array('messaging', $modulesFromPlatform ?? [], true));
                                $enabledLocally = !empty($info['enabled']);
                                $ok = $fromPlatform && $enabledLocally;
                                $needEnable = $fromPlatform && !$enabledLocally;
                            @endphp
                            <tr>
                                <td>{{ $info['name'] ?? $key }}</td>
                                <td>
                                    @if($fromPlatform)
                                        <span class="dashboard-pill dashboard-pill-success">✓ فعال</span>
                                    @else
                                        <span class="dashboard-pill dashboard-pill-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($enabledLocally)
                                        <span class="dashboard-pill dashboard-pill-success">✓ فعال</span>
                                    @else
                                        <span class="dashboard-pill dashboard-pill-muted">غیرفعال</span>
                                    @endif
                                </td>
                                <td>
                                    @if($ok)
                                        <span class="dashboard-license-license-success">هماهنگ</span>
                                    @elseif($needEnable)
                                        <span class="dashboard-license-license-warning">پلتفرم داده، در سایت غیرفعال است</span>
                                    @else
                                        <span class="dashboard-license-license-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="dashboard-license-license-muted">ماژولی در تنظیمات این نصب تعریف نشده است.</p>
            @endif
        </div>
    </div>
</div>
 </div>
@endsection

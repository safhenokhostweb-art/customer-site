@extends('admin.layout')
@section('title', 'داشبورد')
@section('header', 'داشبورد')
@section('content')

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

<style>
@media (max-width: 768px) {
    .dashboard-modules-block .dashboard-modules-header { max-width: none; width: 100%; border-left: none; border-bottom: 1px solid #e5e7eb; }
    .dashboard-modules-block .dashboard-modules-content { width: 100%; }
}
</style>
<p class="text-slate-600 text-sm mb-6">خلاصهٔ محتوا و وضعیت سایت.</p>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="card p-6">
        <div class="text-slate-500 text-sm">صفحات</div>
        <div class="text-2xl font-bold text-slate-800 mt-1">{{ $stats['pages'] }}</div>
    </div>
    <div class="card p-6">
        <div class="text-slate-500 text-sm">مطالب وبلاگ</div>
        <div class="text-2xl font-bold text-slate-800 mt-1">{{ $stats['posts'] }}</div>
    </div>
    @if(\App\Helpers\SiteHelper::shopEnabled())
        <div class="card p-6">
            <div class="text-slate-500 text-sm">محصولات</div>
            <div class="text-2xl font-bold text-slate-800 mt-1">{{ $stats['products'] }}</div>
        </div>
        <div class="card p-6">
            <div class="text-slate-500 text-sm">سفارشات</div>
            <div class="text-2xl font-bold text-slate-800 mt-1">{{ $stats['orders'] }}</div>
        </div>
    @endif
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
<div class="dashboard-modules-block" style="margin-top:2rem; background:#fff; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,.08); border:1px solid #e5e7eb; overflow:hidden;">
    <div style="display:flex; flex-wrap:wrap; width:100%; min-height:0;">
        <div class="dashboard-modules-header" style="flex:0 0 auto; width:100%; max-width:320px; padding:1.25rem; border-left:1px solid #e5e7eb;">
            <h2 style="font-size:1.1rem; font-weight:700; margin-bottom:0.5rem;">ماژول‌ها و امکانات از پلتفرم</h2>
            <p style="color:#64748b; font-size:.875rem; margin-bottom:1rem;">
                مقایسهٔ ماژول‌های دریافتی از پلتفرم با وضعیت فعال بودن در این سایت. ماژول‌های پلتفرم از صفحهٔ
                <a href="{{ route('admin.license.index') }}" class="text-blue-600 hover:underline">لایسنس</a>
                یا
                <a href="{{ route('admin.modules.index') }}" class="text-blue-600 hover:underline">ماژول‌ها</a>
                با «بروزرسانی ماژول‌ها» به‌روز می‌شوند. اگر ماژولی از پلتفرم نیامد، در saas-platform آن را به سایت تخصیص دهید.
                برای ماژول‌های محلی می‌توانید در <code>.env</code> فعال کنید. اعلان‌های ویژهٔ بازدیدکنندگان و کاربران این سایت
                از طریق ماژول «اعلان‌ها» در همین پنل مدیریت تنظیم می‌شوند؛ اعلان‌های ادمین برای این سایت همچنان از پلتفرم اصلی (saas-platform) ارسال می‌شوند.
            </p>
            @php
                $license = $license ?? null;
                $modulesFromPlatform = $modulesFromPlatform ?? [];
                $localModules = $localModules ?? [];
            @endphp
            <div style="margin-bottom:0;">
                <strong>وضعیت لایسنس:</strong>
                @if(!$license || empty($license->license_key))
                    <span style="color:#b45309;">تنظیم نشده</span> — در <a href="{{ route('admin.license.index') }}" class="text-blue-600 hover:underline">صفحهٔ لایسنس</a> کلید را وارد کنید.
                @elseif(!$license->isValid())
                    <span style="color:#dc2626;">نامعتبر یا منقضی</span> — دامنه یا انقضا را بررسی کنید.
                @else
                    <span style="color:#15803d;">فعال</span>
                    @if(!empty($license->modules))
                        — ماژول‌های دریافتی: {{ implode('، ', $license->modules) }}
                    @endif
                @endif
            </div>
        </div>
        <div class="dashboard-modules-content" style="flex:1; min-width:0; padding:1.25rem; overflow-x:auto;">
            @if(count($localModules) > 0)
                <table style="width:100%; border-collapse:collapse; text-align:right;">
                    <thead>
                        <tr style="border-bottom:1px solid #e5e7eb;">
                            <th style="padding:0.5rem 0.75rem; font-weight:600;">ماژول</th>
                            <th style="padding:0.5rem 0.75rem; font-weight:600;">از پلتفرم</th>
                            <th style="padding:0.5rem 0.75rem; font-weight:600;">فعال در این سایت</th>
                            <th style="padding:0.5rem 0.75rem; font-weight:600;">وضعیت</th>
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
                            <tr style="border-bottom:1px solid #f1f5f9;">
                                <td style="padding:0.5rem 0.75rem;">{{ $info['name'] ?? $key }}</td>
                                <td style="padding:0.5rem 0.75rem;">
                                    @if($fromPlatform)
                                        <span style="display:inline-flex; padding:0.15rem 0.5rem; border-radius:4px; background:#dcfce7; color:#166534; font-size:.8rem;">✓ فعال</span>
                                    @else
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                </td>
                                <td style="padding:0.5rem 0.75rem;">
                                    @if($enabledLocally)
                                        <span style="display:inline-flex; padding:0.15rem 0.5rem; border-radius:4px; background:#dcfce7; color:#166534; font-size:.8rem;">✓ فعال</span>
                                    @else
                                        <span style="color:#94a3b8;">غیرفعال</span>
                                    @endif
                                </td>
                                <td style="padding:0.5rem 0.75rem;">
                                    @if($ok)
                                        <span style="color:#15803d; font-size:.85rem;">هماهنگ</span>
                                    @elseif($needEnable)
                                        <span style="color:#b45309; font-size:.85rem;">پلتفرم داده، در سایت غیرفعال است</span>
                                    @else
                                        <span style="color:#64748b; font-size:.85rem;">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="color:#64748b; font-size:.875rem;">ماژولی در تنظیمات این نصب تعریف نشده است.</p>
            @endif
        </div>
    </div>
</div>
@endsection

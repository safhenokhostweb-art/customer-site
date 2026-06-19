@extends('admin.layout')
@section('title', 'داشبورد')
@section('header', 'داشبورد')
@push('head')
<style>
/* ─── Glass Cards ─── */
.g-card{position:relative;background:rgba(255,255,255,0.75);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.3);box-shadow:0 8px 32px rgba(15,23,42,0.07);border-radius:1rem;overflow:hidden}
.g-card:hover{box-shadow:0 12px 48px rgba(15,23,42,0.12)}
.g-card-strong{position:relative;background:rgba(255,255,255,0.88);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);border:1px solid rgba(255,255,255,0.4);box-shadow:0 8px 32px rgba(15,23,42,0.09);border-radius:1rem;overflow:hidden}

/* ─── Stat Tiles ─── */
.dash-tile{position:relative;display:flex;flex-direction:column;gap:0.5rem;overflow:hidden;border-radius:1rem;padding:1.25rem 1.375rem;box-shadow:0 8px 28px rgba(15,23,42,0.12);transition:all .3s;color:#fff!important;text-decoration:none;border:1px solid rgba(255,255,255,0.15)}
.dash-tile:hover{transform:translateY(-5px) scale(1.02);box-shadow:0 18px 44px rgba(15,23,42,0.2);text-decoration:none}
.dash-tile::before{content:'';position:absolute;top:-30%;right:-20%;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,0.08);pointer-events:none}
.dash-tile__icon{font-size:1.75rem;line-height:1;opacity:0.9}
.dash-tile__label{font-size:0.78rem;font-weight:600;opacity:0.82;text-transform:uppercase;letter-spacing:0.04em}
.dash-tile__value{font-size:2rem;font-weight:800;line-height:1.1}
.dash-tile__hint{font-size:0.72rem;opacity:0.75}
.dash-tile--slate{background:linear-gradient(135deg,#475569,#0f172a)}
.dash-tile--indigo{background:linear-gradient(135deg,#6366f1,#1e1b4b)}
.dash-tile--sky{background:linear-gradient(135deg,#0284c7,#0c4a6e)}
.dash-tile--emerald{background:linear-gradient(135deg,#059669,#022c22)}
.dash-tile--amber{background:linear-gradient(135deg,#d97706,#451a03)}
.dash-tile--violet{background:linear-gradient(135deg,#7c3aed,#2e1065)}
.dash-tile--rose{background:linear-gradient(135deg,#e11d48,#4c0519)}
.dash-tile--teal{background:linear-gradient(135deg,#0d9488,#042f2e)}
.dash-tile--orange{background:linear-gradient(135deg,#ea580c,#431407)}

/* ─── Quick Links ─── */
.q-link{display:flex;align-items:center;gap:0.75rem;padding:0.75rem 1rem;border-radius:0.875rem;border:1px solid #e2e8f0;background:#fff;box-shadow:0 1px 4px rgba(15,23,42,0.05);transition:all .2s;text-decoration:none;color:#1e293b}
.q-link:hover{border-color:#a5b4fc;box-shadow:0 4px 16px rgba(99,102,241,0.1);transform:translateY(-1px);text-decoration:none}
.q-link__icon{display:flex;align-items:center;justify-content:center;width:2.5rem;height:2.5rem;border-radius:0.625rem;flex-shrink:0;font-size:1.1rem}
.q-link__label{font-size:0.875rem;font-weight:600;color:#1e293b}

/* ─── Alert Pulse ─── */
@keyframes dashboard-alert-pulse {
    0%, 100% { opacity: 1; background-color: #fef3c7; }
    50% { opacity: 0.92; background-color: #fde68a; }
}
.alert-pulse { animation: dashboard-alert-pulse 2s ease-in-out infinite; }
.alert-pulse:nth-child(even) { animation-delay: 0.5s; }

/* ─── Module Pills ─── */
.dashboard-pill{display:inline-flex;align-items:center;gap:0.25rem;padding:0.2rem 0.6rem;border-radius:9999px;font-size:0.72rem;font-weight:600}
.dashboard-pill-success{background:#dcfce7;color:#166534}
.dashboard-pill-muted{background:#f1f5f9;color:#64748b}
.dashboard-license-license-success{color:#059669;font-weight:600}
.dashboard-license-license-warning{color:#d97706;font-weight:600}
.dashboard-license-license-danger{color:#dc2626;font-weight:600}
.dashboard-license-license-muted{color:#94a3b8}

/* ─── Modules Block ─── */
.dashboard-modules-block{margin-top:1.5rem;margin-bottom:1.5rem}
.dashboard-modules-shell{display:grid;grid-template-columns:1fr 2fr;gap:1.5rem;align-items:start}
@media(max-width:900px){.dashboard-modules-shell{grid-template-columns:1fr}}
.dashboard-modules-title{font-size:1.1rem;font-weight:700;color:#1e293b;margin-bottom:0.375rem}
.dashboard-modules-lead{font-size:0.82rem;color:#64748b;line-height:1.6;margin-bottom:0.75rem}
.dashboard-modules-lead a{color:#6366f1;text-decoration:none}
.dashboard-modules-lead a:hover{text-decoration:underline}
.dashboard-license-state{margin-top:0.5rem;font-size:0.82rem;color:#475569}
.dashboard-modules-table{width:100%;border-collapse:collapse;font-size:0.82rem;text-align:right}
.dashboard-modules-table thead th{background:rgba(99,102,241,0.06);border-bottom:2px solid rgba(99,102,241,0.15);padding:0.5rem 0.75rem;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:#64748b;white-space:nowrap}
.dashboard-modules-table tbody td{padding:0.55rem 0.75rem;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.dashboard-modules-table tbody tr:hover{background:rgba(99,102,241,0.03)}

/* ─── pulse dot ─── */
.pulse-dot{display:inline-block;width:8px;height:8px;border-radius:50%;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.6;transform:scale(1.2)}}

/* ─── Mobile Layout Fixes ─── */
@media(max-width:639px){
  .admin-dashboard-page .dash-welcome-row{flex-direction:column!important;align-items:flex-start!important}
  .admin-dashboard-page .dash-tile__value{font-size:1.5rem}
  .admin-dashboard-page .q-link__label{font-size:0.8rem}
  .admin-dashboard-page .dashboard-modules-shell{grid-template-columns:1fr!important}
}
</style>
@endpush
@section('content')

@include('admin.partials.breadcrumb', ['crumbs' => [
    ['label' => 'داشبورد', 'url' => null],
]])

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

{{-- ═══ بنر خوش‌آمدید ═══ --}}
<div class="g-card mb-7">
    <div class="absolute inset-0 bg-linear-to-l from-indigo-600/10 via-sky-500/5 to-transparent pointer-events-none"></div>
    <div class="relative z-10 p-6 sm:p-8 flex flex-col items-start sm:flex-row sm:items-center gap-5">
        <div class="rounded-2xl flex items-center justify-center shrink-0"
             style="background:linear-gradient(135deg,#6366f1,#0284c7);width:3.5rem;height:3.5rem;min-width:3.5rem;flex-shrink:0">
            <x-core-ui::icon name="store" style="width:1.75rem;height:1.75rem;color:#fff" />
        </div>
        <div class="flex-1">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">
                خوش آمدید، <span class="bg-linear-to-l from-indigo-600 to-sky-500 bg-clip-text text-transparent">{{ auth()->user()->name ?? 'مدیر' }}</span> 👋
            </h2>
            <p class="text-gray-500 text-sm mt-1.5">آخرین وضعیت سایت <strong>{{ \App\Helpers\SiteHelper::siteName() }}</strong> در زیر نمایش داده شده است.</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="pulse-dot bg-green-500"></span>
            <span class="text-sm font-medium text-green-700">سایت فعال</span>
        </div>
    </div>
</div>

{{-- ═══ Lifecycle Status Widget ═══ --}}
@include('admin.partials.lifecycle-widget')

{{-- ═══ Resource Usage Widget ═══ --}}
@include('admin.partials.resource-widget')

{{-- ═══ هشدارهای مهم ═══ --}}
@if(isset($importantAlerts) && $importantAlerts->isNotEmpty())
<div class="mb-6 rounded-xl overflow-hidden border-2 border-amber-400 shadow-lg" id="dashboard-important-alerts">
    <div class="px-4 py-3 bg-amber-500 text-amber-950 font-bold text-sm flex items-center gap-2">
        <x-core-ui::icon name="store" class="w-5 h-5 shrink-0" />
        هشدارهای مهم
    </div>
    <ul class="divide-y divide-amber-200/80">
        @foreach($importantAlerts as $alert)
        <li class="alert-pulse px-4 py-4 bg-amber-100/90 hover:bg-amber-100">
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

{{-- ═══ آپدیت ماژول‌ها ═══ --}}
@if(!empty($moduleUpdates))
<div class="g-card-strong p-4 mb-6 border-r-4 border-r-indigo-500">
    <div class="flex items-start gap-3">
        <x-core-ui::icon name="store" class="w-6 h-6 text-indigo-600 shrink-0 mt-0.5" />
        <div class="flex-1">
            <h3 class="font-semibold text-indigo-900 mb-1">بروزرسانی ماژول‌ها موجود است</h3>
            <p class="text-sm text-indigo-800 mb-2">{{ count($moduleUpdates) }} ماژول بروزرسانی دارد.</p>
            <a href="{{ route('admin.modules.index') }}" class="btn btn-primary btn-sm">مشاهده و دریافت</a>
        </div>
    </div>
</div>
@endif

{{-- ═══ کاشی‌های آمار ═══ --}}
<p class="text-slate-500 text-sm mb-4">خلاصهٔ محتوا و وضعیت سایت — از کاشی‌ها برای ورود سریع استفاده کنید.</p>
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 mb-8">
    <a href="{{ route('admin.pages.index') }}" class="dash-tile dash-tile--slate">
        <span class="dash-tile__icon">📄</span>
        <span class="dash-tile__label">صفحات</span>
        <span class="dash-tile__value">{{ number_format($stats['pages']) }}</span>
        <span class="dash-tile__hint">صفحات ثابت</span>
    </a>
    <a href="{{ route('admin.posts.index') }}" class="dash-tile dash-tile--indigo">
        <span class="dash-tile__icon">✍️</span>
        <span class="dash-tile__label">مطالب وبلاگ</span>
        <span class="dash-tile__value">{{ number_format($stats['posts']) }}</span>
        <span class="dash-tile__hint">نوشته‌ها و مقالات</span>
    </a>
    @if(\App\Helpers\SiteHelper::shopEnabled())
        <a href="{{ route('admin.products.index') }}" class="dash-tile dash-tile--emerald">
            <span class="dash-tile__icon">📦</span>
            <span class="dash-tile__label">محصولات</span>
            <span class="dash-tile__value">{{ number_format($stats['products']) }}</span>
            <span class="dash-tile__hint">فروشگاه</span>
        </a>
        <a href="{{ route('admin.orders.index') }}" class="dash-tile dash-tile--amber">
            <span class="dash-tile__icon">🛒</span>
            <span class="dash-tile__label">سفارشات</span>
            <span class="dash-tile__value">{{ number_format($stats['orders']) }}</span>
            <span class="dash-tile__hint">پیگیری سفارش</span>
        </a>
    @endif
    <a href="{{ route('admin.settings.index') }}" class="dash-tile dash-tile--sky">
        <span class="dash-tile__icon">⚙️</span>
        <span class="dash-tile__label">تنظیمات</span>
        <span class="dash-tile__value" style="font-size:1.1rem">پیکربندی</span>
        <span class="dash-tile__hint">نام سایت، تم</span>
    </a>
    <a href="{{ route('admin.license.index') }}" class="dash-tile dash-tile--violet">
        <span class="dash-tile__icon">🔑</span>
        <span class="dash-tile__label">لایسنس</span>
        <span class="dash-tile__value" style="font-size:1.1rem">
            @if($license && $license->isValid())
                <span class="text-green-200">فعال</span>
            @else
                <span class="text-red-200">نامعتبر</span>
            @endif
        </span>
        <span class="dash-tile__hint">کلید و ماژول‌ها</span>
    </a>
    <a href="{{ route('admin.modules.index') }}" class="dash-tile dash-tile--rose">
        <span class="dash-tile__icon">🧩</span>
        <span class="dash-tile__label">ماژول‌ها</span>
        <span class="dash-tile__value" style="font-size:1.1rem">دریافت</span>
        <span class="dash-tile__hint">همگام با پلتفرم</span>
    </a>
</div>

{{-- ═══ میانبرهای سریع ═══ --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mb-8">
    <a href="{{ route('admin.settings.index') }}" class="q-link">
        <span class="q-link__icon bg-indigo-50 text-indigo-600">⚙️</span>
        <span class="q-link__label">تنظیمات سایت</span>
    </a>
    <a href="{{ route('admin.pages.index') }}" class="q-link">
        <span class="q-link__icon bg-sky-50 text-sky-600">📄</span>
        <span class="q-link__label">مدیریت صفحات</span>
    </a>
    <a href="{{ route('admin.modules.index') }}" class="q-link">
        <span class="q-link__icon bg-rose-50 text-rose-600">🧩</span>
        <span class="q-link__label">ماژول‌ها</span>
    </a>
    <a href="{{ route('admin.license.index') }}" class="q-link">
        <span class="q-link__icon bg-violet-50 text-violet-600">🔑</span>
        <span class="q-link__label">لایسنس</span>
    </a>
    @if(\App\Helpers\SiteHelper::shopEnabled())
    <a href="{{ route('admin.orders.index') }}" class="q-link">
        <span class="q-link__icon bg-amber-50 text-amber-600">🛒</span>
        <span class="q-link__label">سفارشات اخیر</span>
    </a>
    @endif
    <a href="{{ route('admin.inbox.index') }}" class="q-link">
        <span class="q-link__icon bg-emerald-50 text-emerald-600">💬</span>
        <span class="q-link__label">صندوق پشتیبانی</span>
    </a>
    <a href="{{ route('admin.posts.index') }}" class="q-link">
        <span class="q-link__icon bg-teal-50 text-teal-600">✍️</span>
        <span class="q-link__label">مطالب وبلاگ</span>
    </a>
    <a href="{{ url('/') }}" target="_blank" class="q-link">
        <span class="q-link__icon bg-slate-50 text-slate-600">🌐</span>
        <span class="q-link__label">مشاهده سایت</span>
    </a>
</div>

{{-- ═══ آخرین سفارشات ═══ --}}
@if(\App\Helpers\SiteHelper::shopEnabled() && $recentOrders->isNotEmpty())
<div class="g-card-strong p-6 mb-7">
    <div class="flex items-center gap-3 mb-4">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700 text-lg">🛒</span>
        <h2 class="font-bold text-gray-900 text-lg">آخرین سفارشات</h2>
        <a href="{{ route('admin.orders.index') }}" class="mr-auto text-sm text-indigo-600 hover:underline">همه سفارشات ←</a>
    </div>
    <div class="overflow-x-auto rounded-xl border border-gray-100">
        <table class="w-full text-right border-collapse text-sm">
            <thead>
                <tr class="border-b bg-gray-50/80">
                    <th class="p-3 font-semibold text-gray-500 text-xs uppercase">شماره</th>
                    <th class="p-3 font-semibold text-gray-500 text-xs uppercase">مشتری</th>
                    <th class="p-3 font-semibold text-gray-500 text-xs uppercase">مبلغ</th>
                    <th class="p-3 font-semibold text-gray-500 text-xs uppercase">وضعیت</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentOrders as $order)
                <tr class="border-b hover:bg-indigo-50/30 transition-colors">
                    <td class="p-3 font-medium">{{ $order->number }}</td>
                    <td class="p-3 text-gray-600">{{ $order->customer_name }}</td>
                    <td class="p-3">{{ number_format($order->total) }} تومان</td>
                    <td class="p-3">{{ $order->status }}</td>
                    <td class="p-3"><a href="{{ route('admin.orders.show', $order) }}" class="text-indigo-600 hover:underline text-sm">مشاهده</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ═══ وضعیت ماژول‌ها و پلتفرم ═══ --}}
<div class="dashboard-modules-block">
    <div class="dashboard-modules-shell">
        <div class="g-card-strong p-5">
            <h2 class="dashboard-modules-title flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-100 text-violet-700 text-base">🧩</span>
                ماژول‌ها و امکانات از پلتفرم
            </h2>
            <p class="dashboard-modules-lead mt-2">
                مقایسهٔ ماژول‌های دریافتی از پلتفرم با وضعیت فعال بودن در این سایت. برای به‌روزرسانی از
                <a href="{{ route('admin.license.index') }}">لایسنس</a> یا
                <a href="{{ route('admin.modules.index') }}">ماژول‌ها</a> اقدام کنید.
            </p>
            @php
                $license = $license ?? null;
                $modulesFromPlatform = $modulesFromPlatform ?? [];
                $localModules = $localModules ?? [];
            @endphp
            <div class="dashboard-license-state mt-3 p-3 rounded-lg bg-slate-50 border border-slate-100">
                <strong class="text-slate-700 text-xs uppercase tracking-wide">وضعیت لایسنس</strong><br>
                <span class="mt-1 inline-block">
                    @if(!$license || empty($license->license_key))
                        <span class="dashboard-license-license-warning">تنظیم نشده</span> — <a href="{{ route('admin.license.index') }}" class="text-indigo-600 hover:underline text-sm">وارد کردن کلید</a>
                    @elseif(!$license->isValid())
                        <span class="dashboard-license-license-danger">نامعتبر یا منقضی</span>
                    @else
                        <span class="dashboard-license-license-success">✓ فعال</span>
                        @if(!empty($license->modules))
                            <span class="text-slate-500 text-xs">— {{ implode('، ', $license->modules) }}</span>
                        @endif
                    @endif
                </span>
            </div>
        </div>
        <div class="g-card-strong p-5">
            @if(count($localModules) > 0)
                <div class="overflow-x-auto rounded-xl border border-gray-100">
                    <table class="dashboard-modules-table">
                        <thead>
                            <tr>
                                <th>ماژول</th>
                                <th>از پلتفرم</th>
                                <th>فعال در سایت</th>
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
                                    <td class="font-medium">{{ $info['name'] ?? $key }}</td>
                                    <td>
                                        @if($fromPlatform)
                                            <span class="dashboard-pill dashboard-pill-success">✓ فعال</span>
                                        @else
                                            <span class="dashboard-pill dashboard-pill-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($enabledLocally)
                                            <span class="dashboard-pill dashboard-pill-success">✓</span>
                                        @else
                                            <span class="dashboard-pill dashboard-pill-muted">غیرفعال</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($ok)
                                            <span class="dashboard-license-license-success text-xs">هماهنگ</span>
                                        @elseif($needEnable)
                                            <span class="dashboard-license-license-warning text-xs">غیرفعال در سایت</span>
                                        @else
                                            <span class="dashboard-license-license-muted text-xs">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="dashboard-license-license-muted text-sm">ماژولی در تنظیمات این نصب تعریف نشده است.</p>
            @endif
        </div>
    </div>
</div>

{{-- ═══ وضعیت سیستم ═══ --}}
<div class="g-card p-5 mb-2">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">💚</span>
            <div>
                <p class="font-semibold text-gray-800">وضعیت سیستم</p>
                <p class="text-sm text-gray-500">سایت در حال اجراست</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="pulse-dot bg-green-500"></span>
            <span class="text-sm font-medium text-green-700">فعال</span>
        </div>
    </div>
</div>

</div>
@endsection



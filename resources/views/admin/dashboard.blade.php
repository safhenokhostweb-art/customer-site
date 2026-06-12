@extends('admin.layout')
@section('title', 'ط¯ط§ط´ط¨ظˆط±ط¯')
@section('header', 'ط¯ط§ط´ط¨ظˆط±ط¯')
@push('head')
<style>
/* â”€â”€â”€ Glass Cards â”€â”€â”€ */
.g-card{position:relative;background:rgba(255,255,255,0.75);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.3);box-shadow:0 8px 32px rgba(15,23,42,0.07);border-radius:1rem;overflow:hidden}
.g-card:hover{box-shadow:0 12px 48px rgba(15,23,42,0.12)}
.g-card-strong{position:relative;background:rgba(255,255,255,0.88);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);border:1px solid rgba(255,255,255,0.4);box-shadow:0 8px 32px rgba(15,23,42,0.09);border-radius:1rem;overflow:hidden}

/* â”€â”€â”€ Stat Tiles â”€â”€â”€ */
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

/* â”€â”€â”€ Quick Links â”€â”€â”€ */
.q-link{display:flex;align-items:center;gap:0.75rem;padding:0.75rem 1rem;border-radius:0.875rem;border:1px solid #e2e8f0;background:#fff;box-shadow:0 1px 4px rgba(15,23,42,0.05);transition:all .2s;text-decoration:none;color:#1e293b}
.q-link:hover{border-color:#a5b4fc;box-shadow:0 4px 16px rgba(99,102,241,0.1);transform:translateY(-1px);text-decoration:none}
.q-link__icon{display:flex;align-items:center;justify-content:center;width:2.5rem;height:2.5rem;border-radius:0.625rem;flex-shrink:0;font-size:1.1rem}
.q-link__label{font-size:0.875rem;font-weight:600;color:#1e293b}

/* â”€â”€â”€ Alert Pulse â”€â”€â”€ */
@keyframes dashboard-alert-pulse {
    0%, 100% { opacity: 1; background-color: #fef3c7; }
    50% { opacity: 0.92; background-color: #fde68a; }
}
.alert-pulse { animation: dashboard-alert-pulse 2s ease-in-out infinite; }
.alert-pulse:nth-child(even) { animation-delay: 0.5s; }

/* â”€â”€â”€ Module Pills â”€â”€â”€ */
.dashboard-pill{display:inline-flex;align-items:center;gap:0.25rem;padding:0.2rem 0.6rem;border-radius:9999px;font-size:0.72rem;font-weight:600}
.dashboard-pill-success{background:#dcfce7;color:#166534}
.dashboard-pill-muted{background:#f1f5f9;color:#64748b}
.dashboard-license-license-success{color:#059669;font-weight:600}
.dashboard-license-license-warning{color:#d97706;font-weight:600}
.dashboard-license-license-danger{color:#dc2626;font-weight:600}
.dashboard-license-license-muted{color:#94a3b8}

/* â”€â”€â”€ Modules Block â”€â”€â”€ */
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

/* â”€â”€â”€ pulse dot â”€â”€â”€ */
.pulse-dot{display:inline-block;width:8px;height:8px;border-radius:50%;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.6;transform:scale(1.2)}}
</style>
@endpush
@section('content')

@include('admin.partials.breadcrumb', ['crumbs' => [
    ['label' => 'ط¯ط§ط´ط¨ظˆط±ط¯', 'url' => null],
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

{{-- â•گâ•گâ•گ ط¨ظ†ط± ط®ظˆط´â€Œط¢ظ…ط¯غŒط¯ â•گâ•گâ•گ --}}
<div class="g-card mb-7">
    <div class="absolute inset-0 bg-linear-to-l from-indigo-600/10 via-sky-500/5 to-transparent pointer-events-none"></div>
    <div class="relative z-10 p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center gap-5">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0"
             style="background:linear-gradient(135deg,#6366f1,#0284c7)">
            <x-core-ui::icon name="store" class="w-7 h-7 text-white" />
        </div>
        <div class="flex-1">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">
                ط®ظˆط´ ط¢ظ…ط¯غŒط¯طŒ <span class="bg-linear-to-l from-indigo-600 to-sky-500 bg-clip-text text-transparent">{{ auth()->user()->name ?? 'ظ…ط¯غŒط±' }}</span> ًں‘‹
            </h2>
            <p class="text-gray-500 text-sm mt-1.5">ط¢ط®ط±غŒظ† ظˆط¶ط¹غŒطھ ط³ط§غŒطھ <strong>{{ \App\Helpers\SiteHelper::siteName() }}</strong> ط¯ط± ط²غŒط± ظ†ظ…ط§غŒط´ ط¯ط§ط¯ظ‡ ط´ط¯ظ‡ ط§ط³طھ.</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="pulse-dot bg-green-500"></span>
            <span class="text-sm font-medium text-green-700">ط³ط§غŒطھ ظپط¹ط§ظ„</span>
        </div>
    </div>
</div>

{{-- â•گâ•گâ•گ ظ‡ط´ط¯ط§ط±ظ‡ط§غŒ ظ…ظ‡ظ… â•گâ•گâ•گ --}}
@if(isset($importantAlerts) && $importantAlerts->isNotEmpty())
<div class="mb-6 rounded-xl overflow-hidden border-2 border-amber-400 shadow-lg" id="dashboard-important-alerts">
    <div class="px-4 py-3 bg-amber-500 text-amber-950 font-bold text-sm flex items-center gap-2">
        <x-core-ui::icon name="store" class="w-5 h-5 shrink-0" />
        ظ‡ط´ط¯ط§ط±ظ‡ط§غŒ ظ…ظ‡ظ…
    </div>
    <ul class="divide-y divide-amber-200/80">
        @foreach($importantAlerts as $alert)
        <li class="alert-pulse px-4 py-4 bg-amber-100/90 hover:bg-amber-100">
            <div class="font-semibold text-amber-900">{{ $alert['title'] ?? 'ظ‡ط´ط¯ط§ط±' }}</div>
            @if(!empty($alert['body']))
                <p class="text-sm text-amber-800 mt-1">{{ $alert['body'] }}</p>
            @endif
            @if(!empty($alert['link']))
                <a href="{{ $alert['link'] }}" target="_blank" rel="noopener" class="inline-block mt-2 text-sm font-medium text-amber-700 hover:text-amber-900 underline">{{ $alert['link_text'] ?? 'ظ…ط´ط§ظ‡ط¯ظ‡' }}</a>
            @endif
        </li>
        @endforeach
    </ul>
</div>
@endif

{{-- â•گâ•گâ•گ ط¢ظ¾ط¯غŒطھ ظ…ط§عکظˆظ„â€Œظ‡ط§ â•گâ•گâ•گ --}}
@if(!empty($moduleUpdates))
<div class="g-card-strong p-4 mb-6 border-r-4 border-r-indigo-500">
    <div class="flex items-start gap-3">
        <x-core-ui::icon name="store" class="w-6 h-6 text-indigo-600 shrink-0 mt-0.5" />
        <div class="flex-1">
            <h3 class="font-semibold text-indigo-900 mb-1">ط¨ط±ظˆط²ط±ط³ط§ظ†غŒ ظ…ط§عکظˆظ„â€Œظ‡ط§ ظ…ظˆط¬ظˆط¯ ط§ط³طھ</h3>
            <p class="text-sm text-indigo-800 mb-2">{{ count($moduleUpdates) }} ظ…ط§عکظˆظ„ ط¨ط±ظˆط²ط±ط³ط§ظ†غŒ ط¯ط§ط±ط¯.</p>
            <a href="{{ route('admin.modules.index') }}" class="btn btn-primary btn-sm">ظ…ط´ط§ظ‡ط¯ظ‡ ظˆ ط¯ط±غŒط§ظپطھ</a>
        </div>
    </div>
</div>
@endif

{{-- â•گâ•گâ•گ ع©ط§ط´غŒâ€Œظ‡ط§غŒ ط¢ظ…ط§ط± â•گâ•گâ•گ --}}
<p class="text-slate-500 text-sm mb-4">ط®ظ„ط§طµظ‡ظ” ظ…ط­طھظˆط§ ظˆ ظˆط¶ط¹غŒطھ ط³ط§غŒطھ â€” ط§ط² ع©ط§ط´غŒâ€Œظ‡ط§ ط¨ط±ط§غŒ ظˆط±ظˆط¯ ط³ط±غŒط¹ ط§ط³طھظپط§ط¯ظ‡ ع©ظ†غŒط¯.</p>
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 mb-8">
    <a href="{{ route('admin.pages.index') }}" class="dash-tile dash-tile--slate">
        <span class="dash-tile__icon">ًں“„</span>
        <span class="dash-tile__label">طµظپط­ط§طھ</span>
        <span class="dash-tile__value">{{ number_format($stats['pages']) }}</span>
        <span class="dash-tile__hint">طµظپط­ط§طھ ط«ط§ط¨طھ</span>
    </a>
    <a href="{{ route('admin.posts.index') }}" class="dash-tile dash-tile--indigo">
        <span class="dash-tile__icon">âœچï¸ڈ</span>
        <span class="dash-tile__label">ظ…ط·ط§ظ„ط¨ ظˆط¨ظ„ط§ع¯</span>
        <span class="dash-tile__value">{{ number_format($stats['posts']) }}</span>
        <span class="dash-tile__hint">ظ†ظˆط´طھظ‡â€Œظ‡ط§ ظˆ ظ…ظ‚ط§ظ„ط§طھ</span>
    </a>
    @if(\App\Helpers\SiteHelper::shopEnabled())
        <a href="{{ route('admin.products.index') }}" class="dash-tile dash-tile--emerald">
            <span class="dash-tile__icon">ًں“¦</span>
            <span class="dash-tile__label">ظ…ط­طµظˆظ„ط§طھ</span>
            <span class="dash-tile__value">{{ number_format($stats['products']) }}</span>
            <span class="dash-tile__hint">ظپط±ظˆط´ع¯ط§ظ‡</span>
        </a>
        <a href="{{ route('admin.orders.index') }}" class="dash-tile dash-tile--amber">
            <span class="dash-tile__icon">ًں›’</span>
            <span class="dash-tile__label">ط³ظپط§ط±ط´ط§طھ</span>
            <span class="dash-tile__value">{{ number_format($stats['orders']) }}</span>
            <span class="dash-tile__hint">ظ¾غŒع¯غŒط±غŒ ط³ظپط§ط±ط´</span>
        </a>
    @endif
    <a href="{{ route('admin.settings.index') }}" class="dash-tile dash-tile--sky">
        <span class="dash-tile__icon">âڑ™ï¸ڈ</span>
        <span class="dash-tile__label">طھظ†ط¸غŒظ…ط§طھ</span>
        <span class="dash-tile__value" style="font-size:1.1rem">ظ¾غŒع©ط±ط¨ظ†ط¯غŒ</span>
        <span class="dash-tile__hint">ظ†ط§ظ… ط³ط§غŒطھطŒ طھظ…</span>
    </a>
    <a href="{{ route('admin.license.index') }}" class="dash-tile dash-tile--violet">
        <span class="dash-tile__icon">ًں”‘</span>
        <span class="dash-tile__label">ظ„ط§غŒط³ظ†ط³</span>
        <span class="dash-tile__value" style="font-size:1.1rem">
            @if($license && $license->isValid())
                <span class="text-green-200">ظپط¹ط§ظ„</span>
            @else
                <span class="text-red-200">ظ†ط§ظ…ط¹طھط¨ط±</span>
            @endif
        </span>
        <span class="dash-tile__hint">ع©ظ„غŒط¯ ظˆ ظ…ط§عکظˆظ„â€Œظ‡ط§</span>
    </a>
    <a href="{{ route('admin.modules.index') }}" class="dash-tile dash-tile--rose">
        <span class="dash-tile__icon">ًں§©</span>
        <span class="dash-tile__label">ظ…ط§عکظˆظ„â€Œظ‡ط§</span>
        <span class="dash-tile__value" style="font-size:1.1rem">ط¯ط±غŒط§ظپطھ</span>
        <span class="dash-tile__hint">ظ‡ظ…ع¯ط§ظ… ط¨ط§ ظ¾ظ„طھظپط±ظ…</span>
    </a>
</div>

{{-- â•گâ•گâ•گ ظ…غŒط§ظ†ط¨ط±ظ‡ط§غŒ ط³ط±غŒط¹ â•گâ•گâ•گ --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mb-8">
    <a href="{{ route('admin.settings.index') }}" class="q-link">
        <span class="q-link__icon bg-indigo-50 text-indigo-600">âڑ™ï¸ڈ</span>
        <span class="q-link__label">طھظ†ط¸غŒظ…ط§طھ ط³ط§غŒطھ</span>
    </a>
    <a href="{{ route('admin.pages.index') }}" class="q-link">
        <span class="q-link__icon bg-sky-50 text-sky-600">ًں“„</span>
        <span class="q-link__label">ظ…ط¯غŒط±غŒطھ طµظپط­ط§طھ</span>
    </a>
    <a href="{{ route('admin.modules.index') }}" class="q-link">
        <span class="q-link__icon bg-rose-50 text-rose-600">ًں§©</span>
        <span class="q-link__label">ظ…ط§عکظˆظ„â€Œظ‡ط§</span>
    </a>
    <a href="{{ route('admin.license.index') }}" class="q-link">
        <span class="q-link__icon bg-violet-50 text-violet-600">ًں”‘</span>
        <span class="q-link__label">ظ„ط§غŒط³ظ†ط³</span>
    </a>
    @if(\App\Helpers\SiteHelper::shopEnabled())
    <a href="{{ route('admin.orders.index') }}" class="q-link">
        <span class="q-link__icon bg-amber-50 text-amber-600">ًں›’</span>
        <span class="q-link__label">ط³ظپط§ط±ط´ط§طھ ط§ط®غŒط±</span>
    </a>
    @endif
    <a href="{{ route('admin.inbox.index') }}" class="q-link">
        <span class="q-link__icon bg-emerald-50 text-emerald-600">ًں’¬</span>
        <span class="q-link__label">طµظ†ط¯ظˆظ‚ ظ¾ط´طھغŒط¨ط§ظ†غŒ</span>
    </a>
    <a href="{{ route('admin.posts.index') }}" class="q-link">
        <span class="q-link__icon bg-teal-50 text-teal-600">âœچï¸ڈ</span>
        <span class="q-link__label">ظ…ط·ط§ظ„ط¨ ظˆط¨ظ„ط§ع¯</span>
    </a>
    <a href="{{ url('/') }}" target="_blank" class="q-link">
        <span class="q-link__icon bg-slate-50 text-slate-600">ًںŒگ</span>
        <span class="q-link__label">ظ…ط´ط§ظ‡ط¯ظ‡ ط³ط§غŒطھ</span>
    </a>
</div>

{{-- â•گâ•گâ•گ ط¢ط®ط±غŒظ† ط³ظپط§ط±ط´ط§طھ â•گâ•گâ•گ --}}
@if(\App\Helpers\SiteHelper::shopEnabled() && $recentOrders->isNotEmpty())
<div class="g-card-strong p-6 mb-7">
    <div class="flex items-center gap-3 mb-4">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700 text-lg">ًں›’</span>
        <h2 class="font-bold text-gray-900 text-lg">ط¢ط®ط±غŒظ† ط³ظپط§ط±ط´ط§طھ</h2>
        <a href="{{ route('admin.orders.index') }}" class="mr-auto text-sm text-indigo-600 hover:underline">ظ‡ظ…ظ‡ ط³ظپط§ط±ط´ط§طھ â†گ</a>
    </div>
    <div class="overflow-x-auto rounded-xl border border-gray-100">
        <table class="w-full text-right border-collapse text-sm">
            <thead>
                <tr class="border-b bg-gray-50/80">
                    <th class="p-3 font-semibold text-gray-500 text-xs uppercase">ط´ظ…ط§ط±ظ‡</th>
                    <th class="p-3 font-semibold text-gray-500 text-xs uppercase">ظ…ط´طھط±غŒ</th>
                    <th class="p-3 font-semibold text-gray-500 text-xs uppercase">ظ…ط¨ظ„ط؛</th>
                    <th class="p-3 font-semibold text-gray-500 text-xs uppercase">ظˆط¶ط¹غŒطھ</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentOrders as $order)
                <tr class="border-b hover:bg-indigo-50/30 transition-colors">
                    <td class="p-3 font-medium">{{ $order->number }}</td>
                    <td class="p-3 text-gray-600">{{ $order->customer_name }}</td>
                    <td class="p-3">{{ number_format($order->total) }} طھظˆظ…ط§ظ†</td>
                    <td class="p-3">{{ $order->status }}</td>
                    <td class="p-3"><a href="{{ route('admin.orders.show', $order) }}" class="text-indigo-600 hover:underline text-sm">ظ…ط´ط§ظ‡ط¯ظ‡</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- â•گâ•گâ•گ ظˆط¶ط¹غŒطھ ظ…ط§عکظˆظ„â€Œظ‡ط§ ظˆ ظ¾ظ„طھظپط±ظ… â•گâ•گâ•گ --}}
<div class="dashboard-modules-block">
    <div class="dashboard-modules-shell">
        <div class="g-card-strong p-5">
            <h2 class="dashboard-modules-title flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-100 text-violet-700 text-base">ًں§©</span>
                ظ…ط§عکظˆظ„â€Œظ‡ط§ ظˆ ط§ظ…ع©ط§ظ†ط§طھ ط§ط² ظ¾ظ„طھظپط±ظ…
            </h2>
            <p class="dashboard-modules-lead mt-2">
                ظ…ظ‚ط§غŒط³ظ‡ظ” ظ…ط§عکظˆظ„â€Œظ‡ط§غŒ ط¯ط±غŒط§ظپطھغŒ ط§ط² ظ¾ظ„طھظپط±ظ… ط¨ط§ ظˆط¶ط¹غŒطھ ظپط¹ط§ظ„ ط¨ظˆط¯ظ† ط¯ط± ط§غŒظ† ط³ط§غŒطھ. ط¨ط±ط§غŒ ط¨ظ‡â€Œط±ظˆط²ط±ط³ط§ظ†غŒ ط§ط²
                <a href="{{ route('admin.license.index') }}">ظ„ط§غŒط³ظ†ط³</a> غŒط§
                <a href="{{ route('admin.modules.index') }}">ظ…ط§عکظˆظ„â€Œظ‡ط§</a> ط§ظ‚ط¯ط§ظ… ع©ظ†غŒط¯.
            </p>
            @php
                $license = $license ?? null;
                $modulesFromPlatform = $modulesFromPlatform ?? [];
                $localModules = $localModules ?? [];
            @endphp
            <div class="dashboard-license-state mt-3 p-3 rounded-lg bg-slate-50 border border-slate-100">
                <strong class="text-slate-700 text-xs uppercase tracking-wide">ظˆط¶ط¹غŒطھ ظ„ط§غŒط³ظ†ط³</strong><br>
                <span class="mt-1 inline-block">
                    @if(!$license || empty($license->license_key))
                        <span class="dashboard-license-license-warning">طھظ†ط¸غŒظ… ظ†ط´ط¯ظ‡</span> â€” <a href="{{ route('admin.license.index') }}" class="text-indigo-600 hover:underline text-sm">ظˆط§ط±ط¯ ع©ط±ط¯ظ† ع©ظ„غŒط¯</a>
                    @elseif(!$license->isValid())
                        <span class="dashboard-license-license-danger">ظ†ط§ظ…ط¹طھط¨ط± غŒط§ ظ…ظ†ظ‚ط¶غŒ</span>
                    @else
                        <span class="dashboard-license-license-success">âœ“ ظپط¹ط§ظ„</span>
                        @if(!empty($license->modules))
                            <span class="text-slate-500 text-xs">â€” {{ implode('طŒ ', $license->modules) }}</span>
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
                                <th>ظ…ط§عکظˆظ„</th>
                                <th>ط§ط² ظ¾ظ„طھظپط±ظ…</th>
                                <th>ظپط¹ط§ظ„ ط¯ط± ط³ط§غŒطھ</th>
                                <th>ظˆط¶ط¹غŒطھ</th>
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
                                            <span class="dashboard-pill dashboard-pill-success">âœ“ ظپط¹ط§ظ„</span>
                                        @else
                                            <span class="dashboard-pill dashboard-pill-muted">â€”</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($enabledLocally)
                                            <span class="dashboard-pill dashboard-pill-success">âœ“</span>
                                        @else
                                            <span class="dashboard-pill dashboard-pill-muted">ط؛غŒط±ظپط¹ط§ظ„</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($ok)
                                            <span class="dashboard-license-license-success text-xs">ظ‡ظ…ط§ظ‡ظ†ع¯</span>
                                        @elseif($needEnable)
                                            <span class="dashboard-license-license-warning text-xs">ط؛غŒط±ظپط¹ط§ظ„ ط¯ط± ط³ط§غŒطھ</span>
                                        @else
                                            <span class="dashboard-license-license-muted text-xs">â€”</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="dashboard-license-license-muted text-sm">ظ…ط§عکظˆظ„غŒ ط¯ط± طھظ†ط¸غŒظ…ط§طھ ط§غŒظ† ظ†طµط¨ طھط¹ط±غŒظپ ظ†ط´ط¯ظ‡ ط§ط³طھ.</p>
            @endif
        </div>
    </div>
</div>

{{-- â•گâ•گâ•گ ظˆط¶ط¹غŒطھ ط³غŒط³طھظ… â•گâ•گâ•گ --}}
<div class="g-card p-5 mb-2">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">ًں’ڑ</span>
            <div>
                <p class="font-semibold text-gray-800">ظˆط¶ط¹غŒطھ ط³غŒط³طھظ…</p>
                <p class="text-sm text-gray-500">ط³ط§غŒطھ ط¯ط± ط­ط§ظ„ ط§ط¬ط±ط§ط³طھ</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="pulse-dot bg-green-500"></span>
            <span class="text-sm font-medium text-green-700">ظپط¹ط§ظ„</span>
        </div>
    </div>
</div>

</div>
@endsection



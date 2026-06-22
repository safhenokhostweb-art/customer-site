@extends('admin.layout')
@section('title', 'داشبورد')
@section('header', 'داشبورد')

@push('head')
<style>
/* ═══════════════════════════════════════════════════
   Customer Dashboard 2.1 — Compact SaaS Design
   ═══════════════════════════════════════════════════ */

/* ─── Welcome Bar ─── */
.cd-welcome{display:flex;align-items:center;gap:0.875rem;background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:0.625rem 1rem;box-shadow:0 1px 3px rgba(15,23,42,0.05);margin-bottom:0.875rem}
.cd-welcome__avatar{width:2rem;height:2rem;border-radius:0.5rem;background:linear-gradient(135deg,#6366f1,#0284c7);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.cd-welcome__name{font-size:0.875rem;font-weight:700;color:#0f172a;line-height:1.2}
.cd-welcome__sub{font-size:0.7rem;color:#94a3b8;line-height:1.2}

/* ─── Section label ─── */
.cd-section-label{font-size:0.65rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.5rem;padding-right:0.125rem}

/* ─── Section card ─── */
.cd-section{background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;box-shadow:0 1px 3px rgba(15,23,42,0.05);overflow:hidden;margin-bottom:0.875rem}
.cd-section-header{display:flex;align-items:center;gap:0.625rem;padding:0.625rem 0.875rem;border-bottom:1px solid #f1f5f9}
.cd-section-icon{width:1.625rem;height:1.625rem;border-radius:0.375rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:0.8rem}
.cd-section-title{font-size:0.82rem;font-weight:700;color:#1e293b}
.cd-section-action{margin-right:auto;font-size:0.7rem;color:#6366f1;text-decoration:none;font-weight:600}
.cd-section-action:hover{text-decoration:underline;color:#4f46e5}
.cd-section-body{padding:0.875rem}

/* ─── Module Stat Chips ─── */
.cd-mstat{display:flex;align-items:center;gap:0.625rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.75rem;padding:0.625rem 0.75rem}
.cd-mstat__dot{width:2.125rem;height:2.125rem;border-radius:0.375rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:0.875rem}
.cd-mstat__val{font-size:1.25rem;font-weight:800;color:#0f172a;line-height:1}
.cd-mstat__label{font-size:0.65rem;font-weight:600;color:#94a3b8;margin-top:0.1rem}

/* ─── Resource Progress Cards ─── */
.cd-res{background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.75rem;padding:0.625rem 0.75rem}
.cd-res__top{display:flex;align-items:center;justify-content:space-between;margin-bottom:0.375rem}
.cd-res__label{font-size:0.68rem;font-weight:600;color:#64748b}
.cd-res__pct{font-size:0.68rem;font-weight:700}
.cd-res__bar{height:5px;background:#e2e8f0;border-radius:9999px;overflow:hidden}
.cd-res__fill{height:100%;border-radius:9999px}
.cd-res__meta{font-size:0.62rem;color:#94a3b8;margin-top:0.3rem}

/* ─── KPI Cards ─── */
.cd-kpi{display:flex;flex-direction:column;gap:0.2rem;background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:0.75rem 1rem;box-shadow:0 1px 3px rgba(15,23,42,0.05);text-decoration:none;transition:box-shadow .15s,border-color .15s}
.cd-kpi:hover{box-shadow:0 4px 16px rgba(15,23,42,0.09);border-color:#c7d2fe;text-decoration:none}
.cd-kpi__label{font-size:0.68rem;font-weight:600;color:#94a3b8;white-space:nowrap}
.cd-kpi__value{font-size:1.375rem;font-weight:800;color:#0f172a;line-height:1.1}
.cd-kpi__meta{font-size:0.63rem;color:#cbd5e1}

/* ─── Quick Actions Grid ─── */
.cd-qaction{display:flex;flex-direction:column;align-items:center;gap:0.5rem;padding:0.875rem 0.5rem;background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;text-decoration:none;transition:all .15s;box-shadow:0 1px 3px rgba(15,23,42,0.04)}
.cd-qaction:hover{border-color:#a5b4fc;background:#fafafe;box-shadow:0 4px 12px rgba(99,102,241,0.1);transform:translateY(-2px);text-decoration:none}
.cd-qaction__icon{width:2.5rem;height:2.5rem;border-radius:0.75rem;display:flex;align-items:center;justify-content:center;font-size:1.1rem;transition:transform .15s}
.cd-qaction:hover .cd-qaction__icon{transform:scale(1.1)}
.cd-qaction__label{font-size:0.7rem;font-weight:600;color:#374151;text-align:center;line-height:1.3}

/* ─── Module Feature Cards ─── */
.cd-mod-card{background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:0.875rem;box-shadow:0 1px 3px rgba(15,23,42,0.05);transition:all .2s;display:flex;flex-direction:column;gap:0.5rem;position:relative;overflow:hidden}
.cd-mod-card:hover{box-shadow:0 6px 20px rgba(15,23,42,0.1);border-color:#c7d2fe;transform:translateY(-2px)}
.cd-mod-card::before{content:'';position:absolute;top:0;right:0;width:3px;height:100%;border-radius:0 3px 3px 0}
.cd-mod-card.mod-active::before{background:linear-gradient(to bottom,#22c55e,#16a34a)}
.cd-mod-card.mod-inactive::before{background:linear-gradient(to bottom,#f59e0b,#d97706)}
.cd-mod-card.mod-platform-only::before{background:linear-gradient(to bottom,#6366f1,#4f46e5)}

.cd-mod-icon{width:2.5rem;height:2.5rem;border-radius:0.75rem;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0}
.cd-mod-name{font-size:0.82rem;font-weight:700;color:#1e293b;line-height:1.2;margin-top:0.125rem}

/* Scrolling description */
.cd-mod-desc-wrap{overflow:hidden;white-space:nowrap;font-size:0.68rem;color:#64748b;line-height:1.4}
.cd-mod-desc-inner{display:inline-block}
.cd-mod-card:hover .cd-mod-desc-inner{animation:cd-marquee 6s linear 0.8s infinite}
@keyframes cd-marquee{
    0%{transform:translateX(0)}
    100%{transform:translateX(-50%)}
}

.cd-mod-badges{display:flex;align-items:center;gap:0.3rem;flex-wrap:wrap;margin-top:auto}

/* ─── Badges ─── */
.cd-badge{display:inline-flex;align-items:center;gap:0.2rem;padding:0.15rem 0.5rem;border-radius:9999px;font-size:0.62rem;font-weight:700;white-space:nowrap;line-height:1.4}
.cd-badge-green{background:#dcfce7;color:#166534}
.cd-badge-amber{background:#fef3c7;color:#92400e}
.cd-badge-gray{background:#f1f5f9;color:#475569}
.cd-badge-blue{background:#dbeafe;color:#1e40af}
.cd-badge-violet{background:#ede9fe;color:#5b21b6}
.cd-badge-red{background:#fee2e2;color:#991b1b}

/* ─── Compact grid ─── */
.cd-grid{width:100%;border-collapse:collapse;font-size:0.78rem;text-align:right}
.cd-grid thead th{background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:0.4rem 0.75rem;font-size:0.63rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#94a3b8;white-space:nowrap}
.cd-grid tbody td{padding:0 0.75rem;height:40px;border-bottom:1px solid #f8fafc;vertical-align:middle}
.cd-grid tbody tr:last-child td{border-bottom:none}
.cd-grid tbody tr:hover{background:#f8fafc}

/* ─── Search ─── */
.cd-search{padding:0.3rem 0.625rem;border:1px solid #e2e8f0;border-radius:0.5rem;font-size:0.75rem;color:#374151;outline:none;transition:border .15s;min-width:160px}
.cd-search:focus{border-color:#a5b4fc;box-shadow:0 0 0 2px rgba(99,102,241,0.1)}

/* ─── Pulse ─── */
.pulse-dot{display:inline-block;width:7px;height:7px;border-radius:50%;animation:cdk-pulse 2s infinite;flex-shrink:0}
@keyframes cdk-pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.55;transform:scale(1.25)}}

/* ─── Alert pulse ─── */
@keyframes cd-alert-pulse{0%,100%{background-color:#fef3c7}50%{background-color:#fde68a}}
.cd-alert-pulse{animation:cd-alert-pulse 2s ease-in-out infinite}

/* ─── Mobile ─── */
@media(max-width:639px){
  .cd-kpi__value{font-size:1.125rem}
  .cd-mstat__val{font-size:1.1rem}
  .cd-welcome__name{font-size:0.8rem}
  .cd-qaction{padding:0.625rem 0.375rem}
}
</style>
@endpush

@section('content')

@php
    $license             = $license ?? null;
    $localModules        = $localModules ?? [];
    $modulesFromPlatform = $modulesFromPlatform ?? [];

    /* ── Module stats ── */
    $activeModules   = 0;
    $inactiveModules = 0;
    foreach ($localModules as $key => $info) {
        $fp  = in_array($key, $modulesFromPlatform, true)
            || ($key === 'whatsapp' && in_array('messaging', $modulesFromPlatform, true));
        $ena = !empty($info['enabled']);
        if ($fp && $ena)  $activeModules++;
        if ($fp && !$ena) $inactiveModules++;
    }
    $premiumModules   = count($modulesFromPlatform);
    $availableModules = count($localModules);

    /* ── Module descriptions & colors ── */
    $moduleDescriptions = [
        'blog'            => 'مدیریت مطالب، نوشته‌ها و مقالات وبلاگ سایت',
        'shop'            => 'فروشگاه آنلاین با سبد خرید، پرداخت و مدیریت سفارش',
        'coupon'          => 'کدهای تخفیف و پروموشن برای جذب مشتری',
        'reviews'         => 'نظرات، امتیاز و بررسی محصولات توسط کاربران',
        'referral'        => 'برنامه معرفی دوستان با پورسانت و پاداش',
        'subscription'    => 'اشتراک و عضویت ویژه با مزایای بیشتر',
        'whatsapp'        => 'پاسخگویی مشتریان از طریق شبکه‌های اجتماعی',
        'messaging'       => 'مدیریت پیام‌ها و ارتباط با مشتریان',
        'sms'             => 'ارسال پیامک تبلیغاتی و اطلاع‌رسانی به مخاطبان',
        'courses'         => 'آموزش آنلاین با دوره‌های ویدیویی و آزمون',
        'invoice'         => 'صدور فاکتور رسمی برای سفارشات و خدمات',
        'business_card'   => 'کارت ویزیت دیجیتال حرفه‌ای برای معرفی',
        'social_links'    => 'لینک تمام شبکه‌های اجتماعی در یک صفحه',
        'advertisements'  => 'مدیریت بنرها و تبلیغات هدفمند در سایت',
        'forms'           => 'ساخت فرم‌های پیشرفته با گردش کار خودکار',
        'advanced_search' => 'جستجوی هوشمند با فیلترهای پیشرفته محصول',
        'currency_reform' => 'نمایش قیمت‌ها با واحد جدید پول ملی',
        'menu'            => 'مدیریت منوها و ساختار ناوبری سایت',
        'notifications'   => 'اعلان‌های فوری درون‌برنامه‌ای برای کاربران',
    ];
    $moduleColors = [
        'blog'            => '#6366f1', 'shop'           => '#059669',
        'coupon'          => '#d97706', 'reviews'        => '#f59e0b',
        'referral'        => '#10b981', 'subscription'   => '#8b5cf6',
        'whatsapp'        => '#22c55e', 'messaging'      => '#22c55e',
        'sms'             => '#3b82f6', 'courses'        => '#ec4899',
        'invoice'         => '#64748b', 'business_card'  => '#0ea5e9',
        'social_links'    => '#06b6d4', 'advertisements' => '#f97316',
        'forms'           => '#7c3aed', 'advanced_search'=> '#0891b2',
        'currency_reform' => '#dc2626', 'menu'           => '#374151',
        'notifications'   => '#f59e0b',
    ];
    $moduleIcons = [
        'blog'            => '✍️',  'shop'           => '🛒',
        'coupon'          => '🎟️', 'reviews'         => '⭐',
        'referral'        => '🤝',  'subscription'   => '💳',
        'whatsapp'        => '💬',  'messaging'      => '💬',
        'sms'             => '📱',  'courses'        => '🎓',
        'invoice'         => '🧾',  'business_card'  => '🪪',
        'social_links'    => '🔗',  'advertisements' => '📢',
        'forms'           => '📋',  'advanced_search'=> '🔍',
        'currency_reform' => '💰',  'menu'           => '☰',
        'notifications'   => '🔔',
    ];

    /* ── Module updates ── */
    $moduleUpdates = [];
    if ($license && $license->license_key && $license->isValid()) {
        $result = app(\App\Services\UpdateCheckService::class)->checkModuleUpdates();
        if (!empty($result['success']) && !empty($result['modules'])) {
            $moduleUpdates = $result['modules'];
        }
    }
    $updateSlugs        = array_column($moduleUpdates, 'slug');
    sort($updateSlugs);
    $updateSignature    = implode(',', $updateSlugs);
    $dismissedSignature = \App\Models\Setting::get('module_updates_dismissed_sig', '');
    $showModuleUpdatesBanner = !empty($moduleUpdates) && $updateSignature !== $dismissedSignature;

    /* ── Resources ── */
    $summary      = \App\Models\Setting::get('site_resource_summary', null);
    $resourceData = $summary ? json_decode($summary, true) : null;
    $storagePct   = $resourceData ? (float)($resourceData['storage_pct'] ?? 0) : 0;
    $storageUsed  = $resourceData ? \App\Helpers\NumberHelper::formatBytes((int)($resourceData['storage_bytes'] ?? 0)) : '—';
    $storageLimit = $resourceData ? \App\Helpers\NumberHelper::formatBytes((int)($resourceData['storage_limit'] ?? 0)) : '—';
    $dbBytes      = (int)($resourceData['db_bytes']  ?? 0);
    $dbLimit      = (int)($resourceData['db_limit']  ?? 536870912);
    $dbPct        = $dbLimit > 0 ? min(100, round(($dbBytes / $dbLimit) * 100)) : 0;
    $dbUsed       = $resourceData ? \App\Helpers\NumberHelper::formatBytes($dbBytes) : '—';
    $resAvailable = $resourceData !== null;

    /* ── License / lifecycle ── */
    $licenseOk       = $license && $license->isValid();
    $licenseLabel    = !$license || empty($license->license_key) ? 'تنظیم نشده' : ($licenseOk ? 'فعال' : 'نامعتبر');
    $lifecycleStatus = \App\Models\Setting::get('site_lifecycle_status', 'active');
    $siteIsActive    = in_array($lifecycleStatus, ['active', 'grace_period']);
    $statusLabel     = match($lifecycleStatus) {
        'active'       => 'فعال',
        'grace_period' => 'دوره اعتبار',
        'suspended'    => 'معلق',
        'archived'     => 'آرشیو',
        default        => $lifecycleStatus,
    };
@endphp

<div class="admin-dashboard-page">

{{-- ══════════════════════════════════════
     WELCOME BAR
     ══════════════════════════════════════ --}}
<div class="cd-welcome">
    <div class="cd-welcome__avatar">
        <svg width="14" height="14" fill="none" stroke="#fff" stroke-width="2.2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
    </div>
    <div class="flex-1 min-w-0">
        <div class="cd-welcome__name">{{ auth()->user()->name ?? 'مدیر' }}</div>
        <div class="cd-welcome__sub">{{ \App\Helpers\SiteHelper::siteName() }}</div>
    </div>
    <div class="flex items-center gap-2 flex-wrap justify-end">
        <div class="flex items-center gap-1.5">
            <span class="pulse-dot {{ $siteIsActive ? 'bg-green-500' : 'bg-red-500' }}"></span>
            <span class="text-xs font-semibold {{ $siteIsActive ? 'text-green-700' : 'text-red-600' }}">{{ $statusLabel }}</span>
        </div>
        <span class="cd-badge {{ $licenseOk ? 'cd-badge-green' : 'cd-badge-amber' }}">🔑 {{ $licenseLabel }}</span>
        <a href="{{ url('/') }}" target="_blank" class="cd-badge cd-badge-blue" style="text-decoration:none">🌐 مشاهده سایت</a>
    </div>
</div>

{{-- Lifecycle widget (non-active only) --}}
@include('admin.partials.lifecycle-widget')

{{-- Alerts --}}
@if(isset($importantAlerts) && $importantAlerts->isNotEmpty())
<div class="mb-3 rounded-xl overflow-hidden border border-amber-300" id="dashboard-important-alerts">
    <div class="px-3 py-2 bg-amber-500 text-amber-950 font-bold text-xs">⚠️ هشدارهای مهم</div>
    <ul class="divide-y divide-amber-200">
        @foreach($importantAlerts as $alert)
        <li class="cd-alert-pulse px-4 py-2.5 bg-amber-50">
            <div class="font-semibold text-amber-900 text-sm">{{ $alert['title'] ?? 'هشدار' }}</div>
            @if(!empty($alert['body']))<p class="text-xs text-amber-800 mt-0.5">{{ $alert['body'] }}</p>@endif
            @if(!empty($alert['link']))<a href="{{ $alert['link'] }}" target="_blank" rel="noopener" class="text-xs font-medium text-amber-700 hover:underline mt-1 inline-block">{{ $alert['link_text'] ?? 'مشاهده' }} ←</a>@endif
        </li>
        @endforeach
    </ul>
</div>
@endif

@if($showModuleUpdatesBanner)
<div class="cd-section mb-3 border-r-4 border-r-indigo-500" id="module-updates-banner" style="border-radius:0.875rem">
    <div class="cd-section-body flex items-center gap-3 py-2.5 px-4">
        <span class="text-indigo-500 text-base">🔄</span>
        <div class="flex-1 text-sm font-semibold text-indigo-900">{{ count($moduleUpdates) }} ماژول آپدیت جدید دارد</div>
        <a href="{{ route('admin.modules.index') }}" class="cd-badge cd-badge-violet" style="text-decoration:none" onclick="dismissModuleUpdatesBanner()">دریافت آپدیت</a>
        <button type="button" class="text-xs text-slate-400 hover:text-slate-600 font-bold px-1" onclick="dismissModuleUpdatesBanner()">✕</button>
    </div>
</div>
<script>
function dismissModuleUpdatesBanner() {
    fetch('{{ route('admin.modules.dismiss-updates') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
    }).catch(() => {});
    document.getElementById('module-updates-banner')?.remove();
}
</script>
@endif

{{-- ══════════════════════════════════════
     ROW 1 — MODULE STATS + RESOURCES
     ══════════════════════════════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-3">
    {{-- Module Stat Chips --}}
    <div class="cd-section" style="margin-bottom:0">
        <div class="cd-section-header">
            <div class="cd-section-icon bg-violet-100">🧩</div>
            <span class="cd-section-title">ماژول‌ها و امکانات</span>
            <a href="{{ route('admin.modules.index') }}" class="cd-section-action">مدیریت ←</a>
        </div>
        <div class="cd-section-body">
            <div class="grid grid-cols-4 gap-2">
                <div class="cd-mstat">
                    <div class="cd-mstat__dot bg-green-100">✅</div>
                    <div><div class="cd-mstat__val">{{ $activeModules }}</div><div class="cd-mstat__label">فعال</div></div>
                </div>
                <div class="cd-mstat">
                    <div class="cd-mstat__dot bg-slate-100">⏸</div>
                    <div><div class="cd-mstat__val">{{ $inactiveModules }}</div><div class="cd-mstat__label">غیرفعال</div></div>
                </div>
                <div class="cd-mstat">
                    <div class="cd-mstat__dot bg-violet-100">⭐</div>
                    <div><div class="cd-mstat__val">{{ $premiumModules }}</div><div class="cd-mstat__label">پریمیوم</div></div>
                </div>
                <div class="cd-mstat">
                    <div class="cd-mstat__dot bg-sky-100">📦</div>
                    <div><div class="cd-mstat__val">{{ $availableModules }}</div><div class="cd-mstat__label">در دسترس</div></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Resources --}}
    <div class="cd-section" style="margin-bottom:0">
        <div class="cd-section-header">
            <div class="cd-section-icon bg-sky-100">📊</div>
            <span class="cd-section-title">منابع سیستم</span>
            <a href="{{ url('/account/resources') }}" class="cd-section-action">جزئیات ←</a>
        </div>
        <div class="cd-section-body">
            @if($resAvailable)
            <div class="grid grid-cols-2 gap-2">
                <div class="cd-res">
                    <div class="cd-res__top">
                        <span class="cd-res__label">💾 فضای ذخیره‌سازی</span>
                        <span class="cd-res__pct {{ $storagePct >= 80 ? 'text-red-600' : ($storagePct >= 60 ? 'text-amber-600' : 'text-emerald-600') }}">{{ $storagePct }}٪</span>
                    </div>
                    <div class="cd-res__bar">
                        <div class="cd-res__fill {{ $storagePct >= 80 ? 'bg-red-500' : ($storagePct >= 60 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width:{{ min(100,$storagePct) }}%"></div>
                    </div>
                    <div class="cd-res__meta">{{ $storageUsed }} از {{ $storageLimit }}</div>
                </div>
                <div class="cd-res">
                    <div class="cd-res__top">
                        <span class="cd-res__label">🗄️ دیتابیس</span>
                        <span class="cd-res__pct {{ $dbPct >= 80 ? 'text-red-600' : ($dbPct >= 60 ? 'text-amber-600' : 'text-emerald-600') }}">{{ $dbPct }}٪</span>
                    </div>
                    <div class="cd-res__bar">
                        <div class="cd-res__fill {{ $dbPct >= 80 ? 'bg-red-500' : ($dbPct >= 60 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width:{{ min(100,$dbPct) }}%"></div>
                    </div>
                    <div class="cd-res__meta">{{ $dbUsed }} استفاده شده</div>
                </div>
            </div>
            @else
            <div class="text-center py-3">
                <span class="text-xs text-slate-400">اطلاعات منابع هنوز محاسبه نشده</span><br>
                <span class="text-xs text-slate-300">هر روز ساعت ۶ صبح آپدیت می‌شود</span>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════
     ROW 2 — CONTENT KPI CARDS
     ══════════════════════════════════════ --}}
<div class="cd-section-label">آمار محتوا</div>
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
    <a href="{{ route('admin.pages.index') }}" class="cd-kpi">
        <span class="cd-kpi__label">📄 صفحات</span>
        <span class="cd-kpi__value">{{ number_format($stats['pages']) }}</span>
        <span class="cd-kpi__meta">صفحات ثابت</span>
    </a>
    <a href="{{ route('admin.posts.index') }}" class="cd-kpi">
        <span class="cd-kpi__label">✍️ مطالب</span>
        <span class="cd-kpi__value">{{ number_format($stats['posts']) }}</span>
        <span class="cd-kpi__meta">وبلاگ</span>
    </a>
    @if(\App\Helpers\SiteHelper::shopEnabled())
    <a href="{{ route('admin.products.index') }}" class="cd-kpi">
        <span class="cd-kpi__label">📦 محصولات</span>
        <span class="cd-kpi__value">{{ number_format($stats['products']) }}</span>
        <span class="cd-kpi__meta">فروشگاه</span>
    </a>
    <a href="{{ route('admin.orders.index') }}" class="cd-kpi">
        <span class="cd-kpi__label">🛒 سفارشات</span>
        <span class="cd-kpi__value">{{ number_format($stats['orders']) }}</span>
        <span class="cd-kpi__meta">پیگیری</span>
    </a>
    @else
    <a href="{{ route('admin.license.index') }}" class="cd-kpi">
        <span class="cd-kpi__label">🔑 لایسنس</span>
        <span class="cd-kpi__value" style="font-size:0.95rem;margin-top:0.125rem">{{ $licenseLabel }}</span>
        <span class="cd-kpi__meta">وضعیت</span>
    </a>
    <a href="{{ route('admin.settings.index') }}" class="cd-kpi">
        <span class="cd-kpi__label">⚙️ تنظیمات</span>
        <span class="cd-kpi__value" style="font-size:0.95rem;margin-top:0.125rem">پیکربندی</span>
        <span class="cd-kpi__meta">سایت</span>
    </a>
    @endif
</div>

{{-- ══════════════════════════════════════
     ROW 3 — QUICK ACTIONS (full-width grid)
     ══════════════════════════════════════ --}}
<div class="cd-section-label">دسترسی سریع</div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(88px,1fr));gap:0.5rem;margin-bottom:1rem">
    <a href="{{ route('admin.settings.index') }}" class="cd-qaction">
        <div class="cd-qaction__icon bg-indigo-50">⚙️</div>
        <span class="cd-qaction__label">تنظیمات</span>
    </a>
    <a href="{{ route('admin.pages.index') }}" class="cd-qaction">
        <div class="cd-qaction__icon bg-sky-50">📄</div>
        <span class="cd-qaction__label">صفحات</span>
    </a>
    <a href="{{ route('admin.posts.index') }}" class="cd-qaction">
        <div class="cd-qaction__icon bg-teal-50">✍️</div>
        <span class="cd-qaction__label">وبلاگ</span>
    </a>
    <a href="{{ route('admin.inbox.index') }}" class="cd-qaction">
        <div class="cd-qaction__icon bg-emerald-50">💬</div>
        <span class="cd-qaction__label">پشتیبانی</span>
    </a>
    @if(\App\Helpers\SiteHelper::shopEnabled())
    <a href="{{ route('admin.orders.index') }}" class="cd-qaction">
        <div class="cd-qaction__icon bg-amber-50">🛒</div>
        <span class="cd-qaction__label">سفارشات</span>
    </a>
    <a href="{{ route('admin.products.index') }}" class="cd-qaction">
        <div class="cd-qaction__icon bg-green-50">📦</div>
        <span class="cd-qaction__label">محصولات</span>
    </a>
    @endif
    <a href="{{ route('admin.license.index') }}" class="cd-qaction">
        <div class="cd-qaction__icon bg-violet-50">🔑</div>
        <span class="cd-qaction__label">لایسنس</span>
    </a>
    <a href="{{ route('admin.modules.index') }}" class="cd-qaction">
        <div class="cd-qaction__icon bg-rose-50">🧩</div>
        <span class="cd-qaction__label">ماژول‌ها</span>
    </a>
    <a href="{{ route('admin.theme-design.index') }}" class="cd-qaction">
        <div class="cd-qaction__icon bg-pink-50">🎨</div>
        <span class="cd-qaction__label">طراحی قالب</span>
    </a>
    <a href="{{ url('/') }}" target="_blank" class="cd-qaction">
        <div class="cd-qaction__icon bg-slate-50">🌐</div>
        <span class="cd-qaction__label">مشاهده سایت</span>
    </a>
</div>

{{-- ══════════════════════════════════════
     MODULE FEATURE SHOWCASE CARDS
     PHP-rendered, Alpine.js search via data-name
     ══════════════════════════════════════ --}}
<div class="cd-section" x-data="{ q: '' }">
    <div class="cd-section-header">
        <div class="cd-section-icon bg-violet-100">🧩</div>
        <span class="cd-section-title">امکانات سایت شما</span>
        <div class="mr-auto flex items-center gap-2">
            <input
                type="text"
                class="cd-search"
                placeholder="🔍 جستجو در ماژول‌ها..."
                x-model="q"
                dir="rtl"
            >
        </div>
    </div>
    <div class="cd-section-body">
        @if(count($localModules) > 0)
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
            @foreach($localModules as $key => $info)
            @php
                $fp       = in_array($key, $modulesFromPlatform, true)
                    || ($key === 'whatsapp' && in_array('messaging', $modulesFromPlatform, true));
                $ena      = !empty($info['enabled']);
                $ok       = $fp && $ena;
                $needEnable = $fp && !$ena;
                $name     = $info['name'] ?? $key;
                $desc     = $moduleDescriptions[$key] ?? 'امکانات این ماژول برای سایت شما فعال است';
                $icon     = $moduleIcons[$key] ?? '🧩';
                $color    = $moduleColors[$key] ?? '#6366f1';
                $cardClass = $ok ? 'mod-active' : ($needEnable ? 'mod-inactive' : 'mod-platform-only');
                // Double the description for seamless marquee loop
                $descDouble = $desc . '    •    ' . $desc;
            @endphp
            <div
                class="cd-mod-card {{ $cardClass }}"
                data-modname="{{ $name }}"
                x-show="!q || $el.dataset.modname.toLowerCase().includes(q.toLowerCase())"
            >
                {{-- Icon --}}
                <div class="flex items-center gap-2">
                    <div class="cd-mod-icon" style="background-color:{{ $color }}18;">
                        <span style="font-size:1.25rem">{{ $icon }}</span>
                    </div>
                    <div class="cd-mod-name">{{ $name }}</div>
                </div>

                {{-- Scrolling description --}}
                <div class="cd-mod-desc-wrap" title="{{ $desc }}">
                    <span class="cd-mod-desc-inner">{{ $descDouble }}</span>
                </div>

                {{-- Badges --}}
                <div class="cd-mod-badges">
                    @if($ok)
                        <span class="cd-badge cd-badge-green">✓ فعال</span>
                    @elseif($needEnable)
                        <span class="cd-badge cd-badge-amber">غیرفعال</span>
                    @else
                        <span class="cd-badge cd-badge-gray">—</span>
                    @endif
                    @if($fp)
                        <span class="cd-badge cd-badge-violet">⭐ پریمیوم</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        {{-- Empty search result --}}
        <div
            class="text-center py-8 text-slate-400 text-sm"
            x-show="q && !document.querySelector('.cd-mod-card[x-show]:not([style*=\'display: none\'])') && false"
        >
            ماژولی با این نام یافت نشد
        </div>
        @else
        <div class="text-center py-8">
            <div class="text-3xl mb-2">🧩</div>
            <p class="text-sm text-slate-500">ماژولی تعریف نشده است.</p>
            <a href="{{ route('admin.modules.index') }}" class="text-xs text-indigo-600 hover:underline mt-1 inline-block">دریافت ماژول‌ها ←</a>
        </div>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════
     RECENT ORDERS (compact)
     ══════════════════════════════════════ --}}
@if(\App\Helpers\SiteHelper::shopEnabled() && $recentOrders->isNotEmpty())
<div class="cd-section">
    <div class="cd-section-header">
        <div class="cd-section-icon bg-amber-100">🛒</div>
        <span class="cd-section-title">آخرین سفارشات</span>
        <a href="{{ route('admin.orders.index') }}" class="cd-section-action">همه سفارشات ←</a>
    </div>
    <div class="overflow-x-auto">
        <table class="cd-grid">
            <thead>
                <tr>
                    <th>شماره</th>
                    <th>مشتری</th>
                    <th>مبلغ</th>
                    <th>وضعیت</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentOrders as $order)
                <tr>
                    <td class="font-semibold text-gray-700">{{ $order->number }}</td>
                    <td class="text-gray-600">{{ $order->customer_name }}</td>
                    <td class="font-medium text-gray-800">{{ number_format($order->total) }} ت</td>
                    <td><span class="cd-badge cd-badge-blue">{{ $order->status }}</span></td>
                    <td><a href="{{ route('admin.orders.show', $order) }}" class="text-xs font-semibold text-indigo-600 hover:underline">مشاهده</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

</div>{{-- /admin-dashboard-page --}}

@endsection

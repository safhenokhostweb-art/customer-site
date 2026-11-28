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
.cd-qactions-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(72px,1fr));gap:0.625rem}
.cd-qaction{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0.5rem;padding:1.1rem 0.5rem;background:var(--qa-bg,#6366f1);border:none;border-radius:1rem;text-decoration:none;transition:all .18s;box-shadow:0 3px 10px rgba(0,0,0,0.15);min-height:90px}
.cd-qaction:hover{transform:translateY(-4px);box-shadow:0 10px 26px rgba(0,0,0,0.22);text-decoration:none;filter:brightness(1.1)}
.cd-qaction__icon{width:2.75rem;height:2.75rem;border-radius:0.75rem;display:flex;align-items:center;justify-content:center;font-size:1.4rem;background:rgba(255,255,255,0.22);transition:transform .18s;flex-shrink:0}
.cd-qaction:hover .cd-qaction__icon{transform:scale(1.12)}
.cd-qaction__label{font-size:0.7rem;font-weight:700;color:#fff;text-align:center;line-height:1.3;text-shadow:0 1px 2px rgba(0,0,0,0.15)}

/* ─── Collapsible section toggle ─── */
.cd-section-toggle{background:none;border:none;cursor:pointer;padding:0.125rem;color:#94a3b8;transition:color .15s}
.cd-section-toggle:hover{color:#475569}
.cd-section-toggle svg{transition:transform .25s;display:block}

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
  .cd-qactions-grid{gap:0.375rem}
  .cd-qaction{padding:0.55rem 0.25rem}
  .cd-qaction__icon{width:1.9rem;height:1.9rem;font-size:1rem}
}
</style>
@endpush

@section('content')

@include('admin.partials.breadcrumb', ['crumbs' => [
    ['label' => 'داشبورد', 'url' => null],
]])

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

{{-- ══════════════════════════════════════
     PLATFORM UPDATE BANNER & CHANGELOG MODAL
     ══════════════════════════════════════ --}}
@if(!empty($latestUpdate))
<div class="cd-section mb-3 border border-indigo-200/80 bg-gradient-to-r from-indigo-50/90 via-sky-50/70 to-indigo-50/90" style="border-radius:0.875rem" id="platform-update-banner">
    <div class="flex items-center justify-between flex-wrap gap-2.5 py-2 px-3.5">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-600 text-white text-xs shadow-sm flex-shrink-0 animate-pulse">
                🚀
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-indigo-600 text-white font-mono shadow-sm">
                        {{ $latestUpdate['version'] ?? 'v1.4.0' }}
                    </span>
                    <span class="text-xs font-bold text-gray-900 truncate">
                        {{ $latestUpdate['title'] ?? 'بروزرسانی جدید سامانه' }}
                    </span>
                    @if(!empty($latestUpdate['shamsi_date']))
                        <span class="text-[10px] text-gray-500 font-medium">📅 {{ $latestUpdate['shamsi_date'] }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="openUpdateModal()" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold text-indigo-700 bg-white hover:bg-indigo-50 border border-indigo-200 shadow-sm transition">
                <span>✨</span>
                <span>امکانات جدید و تاریخچه بروزرسانی‌ها</span>
            </button>
        </div>
    </div>
</div>

{{-- Platform Updates Modal --}}
<div id="platform-updates-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden" style="backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden text-right" dir="rtl">
        {{-- Header --}}
        <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-l from-indigo-50/50 to-white">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-sm shadow-md">
                    🚀
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900">تاریخچه بروزرسانی‌ها و امکانات جدید پلتفرم</h3>
                    <p class="text-[11px] text-gray-500">مشاهده آخرین ویژگی‌ها، بهبودها و گزارش تغییرات سامانه</p>
                </div>
            </div>
            <button type="button" onclick="closeUpdateModal()" class="w-7 h-7 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex items-center justify-center text-sm font-bold transition">
                ✕
            </button>
        </div>

        {{-- Body: Timeline of updates --}}
        <div class="p-4 overflow-y-auto space-y-4 flex-1">
            @forelse($updateHistory ?? [] as $up)
                <div class="bg-slate-50/80 rounded-xl p-4 border border-slate-200/70 space-y-2.5">
                    <div class="flex items-center justify-between flex-wrap gap-2 border-b border-slate-200/60 pb-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2 py-0.5 rounded-md text-xs font-bold bg-indigo-600 text-white font-mono shadow-sm">
                                {{ $up['version'] ?? 'نسخه' }}
                            </span>
                            <span class="text-xs font-bold text-slate-900">
                                {{ $up['title'] ?? 'بروزرسانی سامانه' }}
                            </span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ ($up['update_type'] ?? '') === 'feature' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $up['type_label'] ?? 'امکان جدید' }}
                            </span>
                        </div>
                        @if(!empty($up['shamsi_date']))
                            <span class="text-[11px] text-slate-500 font-medium">📅 {{ $up['shamsi_date'] }}</span>
                        @endif
                    </div>

                    @if(!empty($up['notes']))
                        <p class="text-xs text-slate-600 leading-relaxed">{{ $up['notes'] }}</p>
                    @endif

                    @if(!empty($up['features']) && is_array($up['features']))
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5 pt-1">
                            @foreach($up['features'] as $cat)
                                <div class="bg-white rounded-lg p-2.5 border border-slate-200 shadow-2xs">
                                    <div class="text-[11px] font-bold text-indigo-900 mb-1.5 flex items-center gap-1">
                                        <span class="text-indigo-500">✦</span>
                                        <span>{{ $cat['category'] ?? 'امکانات' }}</span>
                                    </div>
                                    <ul class="text-[11px] text-slate-600 space-y-1">
                                        @foreach($cat['items'] ?? [] as $featureItem)
                                            <li class="flex items-start gap-1.5">
                                                <span class="text-emerald-500 font-bold">✓</span>
                                                <span>{{ $featureItem }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-8 text-slate-400 text-xs">
                    اطلاعات تاریخچه بروزرسانی در دسترس نیست.
                </div>
            @endforelse
        </div>

        {{-- Footer --}}
        <div class="p-3 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
            <span class="text-[11px] text-gray-500">پلتفرم مدیریت هوشمند کسب‌وکار</span>
            <button type="button" onclick="closeUpdateModal()" class="px-4 py-1.5 rounded-lg text-xs font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-100 transition shadow-xs">
                بستن
            </button>
        </div>
    </div>
</div>

<script>
function openUpdateModal() {
    var modal = document.getElementById('platform-updates-modal');
    if (modal) modal.classList.remove('hidden');
}
function closeUpdateModal() {
    var modal = document.getElementById('platform-updates-modal');
    if (modal) modal.classList.add('hidden');
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeUpdateModal();
});
</script>
@endif

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
            @if(!empty($alert['link']))
            @php $isExternalAlertLink = preg_match('#^https?://#i', (string)($alert['link'] ?? '')); @endphp
            @if($isExternalAlertLink)
            <a href="{{ $alert['link'] }}" target="_blank" rel="noopener" class="inline-block mt-2 px-3 py-1.5 bg-amber-600 text-white text-xs font-bold rounded-lg hover:bg-amber-700 transition">{{ $alert['link_text'] ?? 'مشاهده' }} ←</a>
            @else
            <a href="{{ $alert['link'] }}" class="inline-block mt-2 px-3 py-1.5 bg-amber-600 text-white text-xs font-bold rounded-lg hover:bg-amber-700 transition">{{ $alert['link_text'] ?? 'مشاهده' }} ←</a>
            @endif
            @endif
        </li>
        @endforeach
    </ul>
</div>
@endif

@if($showModuleUpdatesBanner)
<div class="cd-section mb-3 border-r-4 border-r-indigo-500" id="module-updates-banner" style="border-radius:0.875rem">
    <div class="cd-section-body flex items-center gap-3 py-2.5 px-4">
        <span class="text-indigo-500 text-base">🔄</span>
        <div class="flex-1 text-sm font-semibold text-indigo-900">@numFa(count($moduleUpdates)) ماژول آپدیت جدید دارد</div>
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
     QUICK ACTIONS — colorful tiles (moved to top)
     ══════════════════════════════════════ --}}
<div class="cd-section-label">دسترسی سریع</div>
<div class="cd-qactions-grid" style="margin-bottom:1rem">
    <a href="{{ route('admin.settings.index') }}" class="cd-qaction" style="--qa-bg:linear-gradient(135deg,#6366f1,#4f46e5)">
        <div class="cd-qaction__icon">⚙️</div>
        <span class="cd-qaction__label">تنظیمات</span>
    </a>
    <a href="{{ route('admin.pages.index') }}" class="cd-qaction" style="--qa-bg:linear-gradient(135deg,#0ea5e9,#0284c7)">
        <div class="cd-qaction__icon">📄</div>
        <span class="cd-qaction__label">صفحات</span>
    </a>
    <a href="{{ route('admin.posts.index') }}" class="cd-qaction" style="--qa-bg:linear-gradient(135deg,#14b8a6,#0d9488)">
        <div class="cd-qaction__icon">✍️</div>
        <span class="cd-qaction__label">وبلاگ</span>
    </a>
    <a href="{{ route('admin.inbox.index') }}" class="cd-qaction" style="--qa-bg:linear-gradient(135deg,#10b981,#059669)">
        <div class="cd-qaction__icon">💬</div>
        <span class="cd-qaction__label">پشتیبانی</span>
    </a>
    @if(\App\Helpers\SiteHelper::shopEnabled())
    <a href="{{ route('admin.orders.index') }}" class="cd-qaction" style="--qa-bg:linear-gradient(135deg,#f59e0b,#d97706)">
        <div class="cd-qaction__icon">🛒</div>
        <span class="cd-qaction__label">سفارشات</span>
    </a>
    <a href="{{ route('admin.products.index') }}" class="cd-qaction" style="--qa-bg:linear-gradient(135deg,#22c55e,#16a34a)">
        <div class="cd-qaction__icon">📦</div>
        <span class="cd-qaction__label">محصولات</span>
    </a>
    @endif
    <a href="{{ route('admin.license.index') }}" class="cd-qaction" style="--qa-bg:linear-gradient(135deg,#8b5cf6,#7c3aed)">
        <div class="cd-qaction__icon">🔑</div>
        <span class="cd-qaction__label">لایسنس</span>
    </a>
    <a href="{{ route('admin.modules.index') }}" class="cd-qaction" style="--qa-bg:linear-gradient(135deg,#f43f5e,#e11d48)">
        <div class="cd-qaction__icon">🧩</div>
        <span class="cd-qaction__label">ماژول‌ها</span>
    </a>
    <a href="{{ route('admin.theme-marketplace.my-licenses') }}" class="cd-qaction" style="--qa-bg:linear-gradient(135deg,#ec4899,#db2777)">
        <div class="cd-qaction__icon">🖼️</div>
        <span class="cd-qaction__label">قالب‌های من</span>
    </a>
    <a href="{{ url('/') }}" target="_blank" class="cd-qaction" style="--qa-bg:linear-gradient(135deg,#64748b,#475569)">
        <div class="cd-qaction__icon">🌐</div>
        <span class="cd-qaction__label">مشاهده سایت</span>
    </a>
</div>

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
                    <div><div class="cd-mstat__val">@numFa($activeModules)</div><div class="cd-mstat__label">فعال</div></div>
                </div>
                <div class="cd-mstat">
                    <div class="cd-mstat__dot bg-slate-100">⏸</div>
                    <div><div class="cd-mstat__val">@numFa($inactiveModules)</div><div class="cd-mstat__label">غیرفعال</div></div>
                </div>
                <div class="cd-mstat">
                    <div class="cd-mstat__dot bg-violet-100">⭐</div>
                    <div><div class="cd-mstat__val">@numFa($premiumModules)</div><div class="cd-mstat__label">پریمیوم</div></div>
                </div>
                <div class="cd-mstat">
                    <div class="cd-mstat__dot bg-sky-100">📦</div>
                    <div><div class="cd-mstat__val">@numFa($availableModules)</div><div class="cd-mstat__label">در دسترس</div></div>
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
                        <span class="cd-res__pct {{ $storagePct >= 80 ? 'text-red-600' : ($storagePct >= 60 ? 'text-amber-600' : 'text-emerald-600') }}">@numFa($storagePct)٪</span>
                    </div>
                    <div class="cd-res__bar">
                        <div class="cd-res__fill {{ $storagePct >= 80 ? 'bg-red-500' : ($storagePct >= 60 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width:{{ min(100,$storagePct) }}%"></div>
                    </div>
                    <div class="cd-res__meta">{{ $storageUsed }} از {{ $storageLimit }}</div>
                </div>
                <div class="cd-res">
                    <div class="cd-res__top">
                        <span class="cd-res__label">🗄️ دیتابیس</span>
                        <span class="cd-res__pct {{ $dbPct >= 80 ? 'text-red-600' : ($dbPct >= 60 ? 'text-amber-600' : 'text-emerald-600') }}">@numFa($dbPct)٪</span>
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
        <span class="cd-kpi__value">@numFa($stats['pages'])</span>
        <span class="cd-kpi__meta">صفحات ثابت</span>
    </a>
    <a href="{{ route('admin.posts.index') }}" class="cd-kpi">
        <span class="cd-kpi__label">✍️ مطالب</span>
        <span class="cd-kpi__value">@numFa($stats['posts'])</span>
        <span class="cd-kpi__meta">وبلاگ</span>
    </a>
    @if(\App\Helpers\SiteHelper::shopEnabled())
    <a href="{{ route('admin.products.index') }}" class="cd-kpi">
        <span class="cd-kpi__label">📦 محصولات</span>
        <span class="cd-kpi__value">@numFa($stats['products'])</span>
        <span class="cd-kpi__meta">فروشگاه</span>
    </a>
    <a href="{{ route('admin.orders.index') }}" class="cd-kpi">
        <span class="cd-kpi__label">🛒 سفارشات</span>
        <span class="cd-kpi__value">@numFa($stats['orders'])</span>
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
     MODULE FEATURE SHOWCASE CARDS
     Collapsible — closed by default
     ══════════════════════════════════════ --}}
<div class="cd-section" x-data="{ open: false }">
    <div class="cd-section-header" @click="open = !open" style="cursor:pointer;user-select:none">
        <div class="cd-section-icon bg-violet-100">🧩</div>
        <span class="cd-section-title">امکانات سایت شما</span>
        <div class="mr-auto flex items-center gap-2">
            <span class="text-xs text-slate-400 font-medium" x-text="open ? 'بستن' : 'نمایش امکانات'"></span>
            <button type="button" class="cd-section-toggle">
                <svg x-bind:style="open ? 'transform:rotate(180deg)' : ''" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
        </div>
    </div>
    <div x-show="open" x-transition.duration.200ms>
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
                    $descDouble = $desc . '    •    ' . $desc;
                @endphp
                <div class="cd-mod-card {{ $cardClass }}">
                    <div class="flex items-center gap-2">
                        <div class="cd-mod-icon" style="background-color:{{ $color }}18;">
                            <span style="font-size:1.25rem">{{ $icon }}</span>
                        </div>
                        <div class="cd-mod-name">{{ $name }}</div>
                    </div>
                    <div class="cd-mod-desc-wrap" title="{{ $desc }}">
                        <span class="cd-mod-desc-inner">{{ $descDouble }}</span>
                    </div>
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
            @else
            <div class="text-center py-8">
                <div class="text-3xl mb-2">🧩</div>
                <p class="text-sm text-slate-500">ماژولی تعریف نشده است.</p>
                <a href="{{ route('admin.modules.index') }}" class="text-xs text-indigo-600 hover:underline mt-1 inline-block">دریافت ماژول‌ها ←</a>
            </div>
            @endif
        </div>
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
                    <td class="font-medium text-gray-800">@numFa($order->total) ت</td>
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

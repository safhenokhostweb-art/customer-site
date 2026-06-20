<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'حساب من') — {{ \App\Helpers\SiteHelper::siteName() }}</title>
    <link rel="preload" as="font" href="{{ asset('assets/fonts/vazirmatn/Vazirmatn-Regular.woff2') }}" type="font/woff2" crossorigin>
    <link rel="preload" as="font" href="{{ asset('assets/fonts/vazirmatn/Vazirmatn-Bold.woff2') }}" type="font/woff2" crossorigin>
    <style>@include('partials.vazirmatn-font-faces-inline')</style>
    <link rel="stylesheet" href="{{ asset('css/customer-admin-layout.css') }}?v={{ @filemtime(public_path('css/customer-admin-layout.css')) ?: time() }}">
    @include('partials.customer-admin-sidebar-theme')
    @if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @include('partials.platform-styles')
    {{-- Theme Engine: same colors as admin panel for consistent UX --}}
    @include('partials.theme-engine-tokens')
    <style>
        body { font-family: Vazirmatn, Tahoma, sans-serif; direction: rtl; font-feature-settings: "ss01" on; }

        /* ─── Base link ─── */
        .account-sidebar-link {
            display: flex; align-items: center; padding: 0.6rem 0.875rem;
            font-size: 0.875rem; font-weight: 500;
            color: var(--admin-sidebar-text, #cbd5e1);
            border-radius: 0.5rem; transition: all 0.15s; text-decoration: none;
        }
        .account-sidebar-link svg { width: 1.125rem; height: 1.125rem; min-width: 1.125rem; flex-shrink: 0; }
        .account-sidebar-link > span { margin-right: 0.625rem; }
        .account-sidebar-link:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .account-sidebar-link.active { background: rgba(255,255,255,0.12); color: #fff; font-weight: 600; }

        /* ─── Group button ─── */
        .acct-grp-btn {
            display: flex; align-items: center; gap: 0.5rem; width: 100%;
            padding: 0.45rem 0.625rem; border-radius: 0.5rem;
            border: none; background: transparent; cursor: pointer; text-align: right;
            color: var(--admin-sidebar-muted, #94a3b8);
            font-family: Vazirmatn, Tahoma, sans-serif;
            transition: background 0.15s, color 0.15s;
        }
        .acct-grp-btn:hover { background: rgba(255,255,255,0.06); color: var(--admin-sidebar-text, #e2e8f0); }
        .acct-grp-icon { width: 1rem; height: 1rem; flex-shrink: 0; opacity: 0.8; }
        .acct-grp-label { flex: 1; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; text-align: right; }
        .acct-grp-caret { width: 0.8rem; height: 0.8rem; flex-shrink: 0; transition: transform 0.25s ease; }
        .acct-grp-btn.is-collapsed .acct-grp-caret { transform: rotate(-90deg); }

        /* ─── Group accordion items ─── */
        .acct-grp-items { overflow: hidden; transition: max-height 0.3s ease; }

        /* ─── Sidebar collapse / icon-rail ─── */
        #account-sidebar { transition: width 0.26s ease, min-width 0.26s ease; }
        #account-sidebar.sidebar-icon-rail { width: 3.75rem !important; min-width: 3.75rem !important; overflow: hidden; }

        /* hide text elements in icon-rail */
        #account-sidebar.sidebar-icon-rail .acct-brand-name,
        #account-sidebar.sidebar-icon-rail .acct-grp-label,
        #account-sidebar.sidebar-icon-rail .acct-grp-caret,
        #account-sidebar.sidebar-icon-rail .acct-grp-items,
        #account-sidebar.sidebar-icon-rail .account-sidebar-link > span,
        #account-sidebar.sidebar-icon-rail .acct-bottom-text { display: none !important; }

        /* center items in icon-rail */
        #account-sidebar.sidebar-icon-rail .account-sidebar-brand { padding: 1rem 0 !important; justify-content: center !important; }
        #account-sidebar.sidebar-icon-rail nav { padding: 0.375rem 0.25rem !important; }
        #account-sidebar.sidebar-icon-rail .acct-grp-btn { justify-content: center; padding: 0.65rem 0; margin: 0; }
        #account-sidebar.sidebar-icon-rail .acct-grp-icon { width: 1.25rem; height: 1.25rem; opacity: 1; }
        #account-sidebar.sidebar-icon-rail .mb-4 { margin-bottom: 0.125rem; }
        #account-sidebar.sidebar-icon-rail .acct-bottom { padding-top: 0.5rem; margin-top: 0.25rem; }
        #account-sidebar.sidebar-icon-rail .account-sidebar-link { justify-content: center; padding: 0.65rem 0; }
        #account-sidebar.sidebar-icon-rail .account-sidebar-link svg { margin: 0 !important; }
        #account-sidebar.sidebar-icon-rail .acct-bottom-btn { justify-content: center; padding: 0.65rem 0; }
        #account-sidebar.sidebar-icon-rail .acct-bottom-btn svg { margin: 0 !important; }

        /* tooltips in icon-rail (appear to the left, toward content area) */
        #account-sidebar.sidebar-icon-rail .acct-grp-btn,
        #account-sidebar.sidebar-icon-rail .account-sidebar-link,
        #account-sidebar.sidebar-icon-rail .acct-bottom-btn { position: relative; }
        #account-sidebar.sidebar-icon-rail .acct-grp-btn:hover::after,
        #account-sidebar.sidebar-icon-rail .account-sidebar-link:hover::after,
        #account-sidebar.sidebar-icon-rail .acct-bottom-btn:hover::after {
            content: attr(data-title);
            position: absolute;
            right: calc(100% + 0.5rem); top: 50%; transform: translateY(-50%);
            background: rgba(15,23,42,0.92); color: #f1f5f9;
            font-size: 0.7rem; font-weight: 600; font-family: Vazirmatn,Tahoma,sans-serif;
            padding: 0.3rem 0.65rem; border-radius: 0.4rem;
            white-space: nowrap; z-index: 9999; pointer-events: none;
            box-shadow: 0 4px 14px rgba(0,0,0,0.3);
        }

        /* ─── Toggle button ─── */
        .sidebar-toggle-btn {
            display: none; align-items: center; justify-content: center;
            width: 2rem; height: 2rem; border-radius: 0.5rem;
            border: none; background: transparent; cursor: pointer;
            color: #64748b; transition: background 0.15s;
        }
        .sidebar-toggle-btn:hover { background: #f1f5f9; color: #1e293b; }
        @media (min-width: 768px) { .sidebar-toggle-btn { display: flex; } }

        /* ─── Cards & utility ─── */
        .account-card { background: #fff; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; overflow: hidden; }
        .account-card-header { padding: 1rem 1.5rem; border-bottom: 1px solid #e5e7eb; background: #f8fafc; font-weight: 600; }
        .account-card-body { padding: 1.25rem 1.5rem; }
        .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 500; font-size: 0.875rem; transition: all 0.15s; cursor: pointer; border: none; text-decoration: none; }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-primary:hover { background: #4338ca; }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        /* Prevent platform theme `a { color }` from overriding button-styled anchors */
        a.btn-primary, a.btn-danger { color: #fff; }
        a.btn-secondary { color: #475569; }
        a.btn-gradient { color: #fff; }
        a[class*="text-white"] { color: #fff; }
        .btn-sm { padding: 0.35rem 0.75rem; font-size: 0.8rem; }
        .badge { display: inline-flex; padding: 0.2rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .form-label { display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.4rem; }
        .form-input, input[type="text"], input[type="email"], input[type="url"], input[type="password"], input[type="file"], textarea, select { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-family: inherit; }
        .form-input:focus, input:focus, textarea:focus, select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,0.2); }
        .form-group { margin-bottom: 1rem; }
        @media (max-width: 767px) { #account-sidebar { display: none !important; } #account-mobile-nav { display: block; } }
        @media (min-width: 768px) { #account-mobile-nav { display: none; } #account-sidebar { height: 100%; } }
    </style>
    @stack('head')
</head>
<body class="bg-slate-50" style="margin:0;padding:0;overflow:hidden;height:100vh;">
<div class="flex h-screen overflow-hidden">

    {{-- ════════════════ SIDEBAR ════════════════ --}}
    @php
    $acctGroupIconNames = [
        'اصلی'           => 'home',
        'خرید و اشتراک' => 'shopping-bag',
        'تعامل'          => 'message',
        'حساب کاربری'   => 'shield-check',
    ];
    $acctGrpIdx = 0;
    @endphp

    <aside id="account-sidebar" class="account-sidebar-shell hidden md:flex md:shrink-0 flex-col w-64">

        {{-- Brand --}}
        <div class="account-sidebar-brand flex items-center px-4 py-3 border-b border-slate-700/40">
            <x-core-ui::icon name="user" class="w-7 h-7 text-sky-400 shrink-0" />
            <span class="acct-brand-name mr-3 text-lg font-bold text-slate-100 truncate">{{ \App\Helpers\SiteHelper::siteName() }}</span>
        </div>

        {{-- Nav groups --}}
        <nav class="flex-1 min-h-0 px-2 py-3 overflow-y-auto overscroll-contain" style="scrollbar-width:thin;">

            @foreach(\App\Helpers\AccountMenu::getGroups() as $groupLabel => $keys)
                @php
                    $visibleKeys = array_filter($keys, fn($k) => \App\Helpers\AccountMenu::isVisible($k));
                    $firstKey    = array_values($visibleKeys)[0] ?? null;
                    $firstUrl    = $firstKey ? route(\App\Helpers\AccountMenu::getRoute($firstKey)) : '#';
                    $grpIconName = $acctGroupIconNames[$groupLabel] ?? 'menu';
                @endphp
                @if(count($visibleKeys) > 0)
                <div class="mb-4">
                    {{-- Group header button --}}
                    <button type="button"
                            class="acct-grp-btn"
                            data-grp="{{ $acctGrpIdx }}"
                            data-url="{{ $firstUrl }}"
                            data-title="{{ $groupLabel }}"
                            onclick="acctGrpToggle({{ $acctGrpIdx }})">
                        <x-core-ui::icon :name="$grpIconName" class="acct-grp-icon" />
                        <span class="acct-grp-label">{{ $groupLabel }}</span>
                        <x-core-ui::icon name="chevron-down" class="acct-grp-caret" />
                    </button>

                    {{-- Group items (accordion body) --}}
                    <div class="acct-grp-items" id="acct-items-{{ $acctGrpIdx }}" style="max-height:500px">
                        <div class="mt-0.5 space-y-0.5 pb-1 px-0.5">
                            @foreach($visibleKeys as $key)
                            <a href="{{ route(\App\Helpers\AccountMenu::getRoute($key)) }}"
                               class="account-sidebar-link {{ \App\Helpers\AccountMenu::isActive($key) ? 'active' : '' }}"
                               data-title="{{ \App\Helpers\AccountMenu::getTitle($key) }}">
                                @include('account.partials.sidebar-icon', ['key' => $key])
                                <span>{{ \App\Helpers\AccountMenu::getTitle($key) }}</span>
                            </a>
                            @endforeach
                        </div>
                    </div>
                </div>
                @php $acctGrpIdx++; @endphp
                @endif
            @endforeach

            {{-- Bottom links --}}
            <div class="acct-bottom pt-3 mt-2 border-t border-slate-700/40 space-y-0.5">
                <a href="{{ url('/') }}"
                   class="account-sidebar-link"
                   data-title="مشاهده سایت">
                    <x-core-ui::icon name="globe" class="w-5 h-5 shrink-0" />
                    <span>مشاهده سایت</span>
                </a>
                @if(auth()->user()->isTenantAdmin())
                <a href="{{ route('admin.dashboard') }}"
                   class="account-sidebar-link"
                   data-title="پنل مدیریت">
                    <x-core-ui::icon name="dashboard" class="w-5 h-5 shrink-0" />
                    <span>پنل مدیریت</span>
                </a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="acct-bottom-btn account-sidebar-link w-full text-right text-red-400 hover:bg-red-900/20 hover:text-red-300 border-0 bg-transparent cursor-pointer"
                            data-title="خروج">
                        <x-core-ui::icon name="logout" class="w-5 h-5 shrink-0" />
                        <span>خروج</span>
                    </button>
                </form>
            </div>

        </nav>
    </aside>
    {{-- ═══════════════════════════════════════ --}}

    <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
        <header class="bg-white border-b border-slate-200 px-4 md:px-6 py-4 shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    {{-- Desktop sidebar toggle --}}
                    <button class="sidebar-toggle-btn" id="account-sidebar-toggle" title="جمع/باز کردن منو" aria-label="جمع/باز کردن منو">
                        <x-core-ui::icon id="sidebar-toggle-icon-open" name="menu" class="w-5 h-5" />
                        <x-core-ui::icon id="sidebar-toggle-icon-closed" name="arrow-right" class="w-5 h-5 hidden" />
                    </button>
                    {{-- Mobile dropdown nav --}}
                    <details id="account-mobile-nav" class="relative">
                        <summary class="list-none p-2 rounded-lg text-slate-600 hover:bg-slate-100 cursor-pointer">
                            <x-core-ui::icon name="menu" class="w-6 h-6" />
                        </summary>
                        <div class="absolute right-0 top-full mt-2 w-64 max-h-[80vh] overflow-y-auto bg-white rounded-xl shadow-xl border border-slate-200 py-2 z-50">
                            @foreach(\App\Helpers\AccountMenu::getGroups() as $groupLabel => $keys)
                                @php $visibleKeys = array_filter($keys, fn($k) => \App\Helpers\AccountMenu::isVisible($k)); @endphp
                                @if(count($visibleKeys) > 0)
                                    <div class="px-3 py-1.5 text-xs font-semibold text-slate-500 bg-slate-50">{{ $groupLabel }}</div>
                                    @foreach($visibleKeys as $key)
                                        <a href="{{ route(\App\Helpers\AccountMenu::getRoute($key)) }}" class="block px-4 py-2.5 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700">{{ \App\Helpers\AccountMenu::getTitle($key) }}</a>
                                    @endforeach
                                @endif
                            @endforeach
                        </div>
                    </details>
                    <h1 class="text-xl md:text-2xl font-bold text-slate-900">@yield('header', 'حساب من')</h1>
                </div>
                <div class="flex items-center gap-3">
                    @include('account.partials.notifications-toolbar')

                    {{-- ─── Profile Dropdown ─────────────────────────── --}}
                    @php
                        $__acctUser    = auth()->user();
                        $__acctAvatar  = $__acctUser->avatar_url;
                        $__acctName    = $__acctUser->name ?? $__acctUser->mobile;
                        $__acctInitial = mb_substr($__acctName, 0, 1);
                    @endphp
                    <div id="acct-profile-menu" class="relative" style="z-index:50">
                        <button id="acct-profile-btn" type="button"
                                class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl hover:bg-slate-100 transition-all">
                            @if($__acctAvatar)
                                <img src="{{ $__acctAvatar }}" class="w-8 h-8 rounded-full object-cover ring-2 ring-white shadow-sm account-avatar-img" alt="{{ $__acctName }}">
                            @else
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center ring-2 ring-white shadow-sm account-avatar-initials">
                                    <span class="text-xs font-bold text-white">{{ $__acctInitial }}</span>
                                </div>
                            @endif
                            <div class="hidden sm:block text-right">
                                <p class="text-xs font-semibold text-slate-700 leading-none">{{ $__acctName }}</p>
                                <p class="text-[10px] text-slate-400 mt-0.5 leading-none">{{ $__acctUser->mobile ?? '' }}</p>
                            </div>
                            <x-core-ui::icon name="chevron-down" id="acct-profile-caret" class="w-3.5 h-3.5 text-slate-400 transition-transform" />
                        </button>

                        <div id="acct-profile-dropdown"
                             class="absolute left-0 top-full mt-2 w-56 bg-white rounded-2xl shadow-xl border border-gray-100 py-2"
                             style="display:none;z-index:100">
                            {{-- User info --}}
                            <div class="px-4 py-3 border-b border-gray-100">
                                <div class="flex items-center gap-3">
                                    @if($__acctAvatar)
                                        <img src="{{ $__acctAvatar }}" class="w-10 h-10 rounded-full object-cover ring-2 ring-indigo-100 account-avatar-img">
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center account-avatar-initials">
                                            <span class="text-sm font-bold text-white">{{ $__acctInitial }}</span>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $__acctName }}</p>
                                        <p class="text-xs text-gray-400 truncate">{{ $__acctUser->mobile ?? '' }}</p>
                                    </div>
                                </div>
                            </div>
                            {{-- Links --}}
                            <div class="py-1">
                                <a href="{{ route('account.profile.index') }}"
                                   class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                                    <x-core-ui::icon name="user" class="w-4 h-4" />
                                    پروفایل من
                                </a>
                                <a href="{{ route('account.security.index') }}"
                                   class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                                    <x-core-ui::icon name="lock" class="w-4 h-4" />
                                    امنیت حساب
                                </a>
                                @if($__acctUser->isTenantAdmin())
                                <a href="{{ route('admin.dashboard') }}"
                                   class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                    <x-core-ui::icon name="dashboard" class="w-4 h-4" />
                                    پنل مدیریت
                                </a>
                                @endif
                            </div>
                            <div class="border-t border-gray-100 pt-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="flex w-full items-center gap-2.5 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                        <x-core-ui::icon name="logout" class="w-4 h-4" />
                                        خروج از حساب
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 md:p-6">
            @include('account.partials.pending-invoice-banner')
            @if(session('success'))
                <div class="mb-6 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-2">
                    <x-core-ui::icon name="check-circle" class="w-5 h-5 shrink-0 text-emerald-600" />
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 flex items-center gap-2">
                    <x-core-ui::icon name="x-circle" class="w-5 h-5 shrink-0 text-red-600" />
                    {{ session('error') }}
                </div>
            @endif
            @if(session('info'))
                <div class="mb-6 p-4 rounded-lg bg-blue-50 border border-blue-200 text-blue-800 flex items-center gap-2">
                    <x-core-ui::icon name="info-circle" class="w-5 h-5 shrink-0 text-blue-600" />
                    {{ session('info') }}
                </div>
            @endif
            @if(session('warning'))
                <div class="mb-6 p-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 flex items-center gap-2">
                    <x-core-ui::icon name="alert-triangle" class="w-5 h-5 shrink-0 text-amber-600" />
                    {{ session('warning') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

@include('partials.account-notification-bell')
@include('partials.chat-sounds')
@include('partials.notification-permission', ['context' => 'customer_account'])
@include('partials.account-ticket-notifier')
@stack('scripts')

<script>
/* ─── Account Profile Dropdown ──────────────────────────────────── */
(function(){
    var menu     = document.getElementById('acct-profile-menu');
    var btn      = document.getElementById('acct-profile-btn');
    var dropdown = document.getElementById('acct-profile-dropdown');
    var caret    = document.getElementById('acct-profile-caret');
    if (!menu || !btn || !dropdown) return;
    function openDrop()  { dropdown.style.display = 'block';  if (caret) caret.style.transform = 'rotate(180deg)'; }
    function closeDrop() { dropdown.style.display = 'none';   if (caret) caret.style.transform = ''; }
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.style.display === 'none' ? openDrop() : closeDrop();
    });
    document.addEventListener('click', function(e) {
        if (!menu.contains(e.target)) closeDrop();
    });
})();

/* ─── Persian digit converter ──────────────────────────────────── */
(function(){
    var pd=['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    var skip={SCRIPT:1,STYLE:1,INPUT:1,TEXTAREA:1,SELECT:1,CODE:1,PRE:1};
    function toFa(s){return s.replace(/[0-9]/g,function(d){return pd[+d];});}
    function walk(n){
        if(n.nodeType===3){var t=n.textContent,c=toFa(t);if(c!==t)n.textContent=c;}
        else if(n.nodeType===1&&!skip[n.tagName]){for(var ch=n.firstChild;ch;ch=ch.nextSibling)walk(ch);}
    }
    function run(){walk(document.body);}
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',run);else run();
    if(window.MutationObserver){
        new MutationObserver(function(ms){ms.forEach(function(m){m.addedNodes.forEach(function(n){walk(n);});});})
            .observe(document.body,{childList:true,subtree:true});
    }
})();

/* ─── Group accordion ──────────────────────────────────────────── */
(function(){
    var STORE = 'acct_grp_v2';
    var states = {};
    try{states=JSON.parse(localStorage.getItem(STORE))||{};}catch(e){}

    function items(idx){return document.getElementById('acct-items-'+idx);}
    function btn(idx){return document.querySelector('.acct-grp-btn[data-grp="'+idx+'"]');}

    function setGroup(idx, open){
        var el=items(idx), b=btn(idx);
        if(!el)return;
        el.style.maxHeight = open ? el.scrollHeight+'px' : '0px';
        if(b){b.classList[open?'remove':'add']('is-collapsed');}
    }

    /* Init: restore saved states (default all open) */
    document.querySelectorAll('.acct-grp-btn').forEach(function(b){
        var idx=b.dataset.grp;
        setGroup(idx, states[idx]!=='0');
    });

    /* Re-measure heights after fonts/images load */
    window.addEventListener('load', function(){
        document.querySelectorAll('.acct-grp-btn').forEach(function(b){
            var idx=b.dataset.grp;
            if(states[idx]==='0')return;
            var el=items(idx);
            if(el)el.style.maxHeight=el.scrollHeight+'px';
        });
    });

    window.acctGrpToggle = function(idx){
        var sidebar=document.getElementById('account-sidebar');
        /* Icon-rail mode: navigate to first item instead of toggling */
        if(sidebar&&sidebar.classList.contains('sidebar-icon-rail')){
            var b=btn(idx);
            if(b&&b.dataset.url&&b.dataset.url!=='#')window.location.href=b.dataset.url;
            return;
        }
        var el=items(idx);
        if(!el)return;
        var isOpen=(el.style.maxHeight!=='0px'&&el.style.maxHeight!=='');
        setGroup(idx,!isOpen);
        states[idx]=isOpen?'0':'1';
        try{localStorage.setItem(STORE,JSON.stringify(states));}catch(e){}
    };
})();

/* ─── Sidebar toggle (full ↔ icon-rail) ───────────────────────── */
(function(){
    var sidebar=document.getElementById('account-sidebar');
    var toggle=document.getElementById('account-sidebar-toggle');
    var icoOpen=document.getElementById('sidebar-toggle-icon-open');
    var icoClosed=document.getElementById('sidebar-toggle-icon-closed');
    if(!sidebar||!toggle)return;

    function apply(open){
        if(open){
            sidebar.classList.remove('sidebar-icon-rail');
            if(icoOpen)icoOpen.classList.remove('hidden');
            if(icoClosed)icoClosed.classList.add('hidden');
        }else{
            sidebar.classList.add('sidebar-icon-rail');
            if(icoOpen)icoOpen.classList.add('hidden');
            if(icoClosed)icoClosed.classList.remove('hidden');
        }
    }

    apply(localStorage.getItem('acct_sidebar')!=='0');

    toggle.addEventListener('click',function(){
        var open=!sidebar.classList.contains('sidebar-icon-rail');
        apply(!open);
        localStorage.setItem('acct_sidebar',open?'0':'1');
    });
})();
</script>
</body>
</html>

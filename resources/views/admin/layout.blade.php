<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'پنل مدیریت') — {{ \App\Helpers\SiteHelper::siteName() }}</title>
    {{-- قالب و منو: فقط فایل محلی؛ ابزارهای صفحات داخلی از build محلی Vite (بدون CDN) --}}
    <link rel="stylesheet" href="{{ asset('css/customer-admin-layout.css') }}?v={{ @filemtime(public_path('css/customer-admin-layout.css')) ?: time() }}">
    @include('partials.customer-admin-sidebar-theme')
    <link rel="stylesheet" href="{{ asset('css/admin-modules.css') }}?v={{ @filemtime(public_path('css/admin-modules.css')) ?: time() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.platform-styles')
    {{-- Theme Engine: injected LAST so it overrides all static and partial-generated CSS vars --}}
    @include('partials.theme-engine-tokens')
    @include('admin.partials.persian-date-admin-styles')
    <style>
        @keyframes badge-blink{0%,100%{opacity:1;box-shadow:0 0 0 0 rgba(239,68,68,0.4)}50%{opacity:0.7;box-shadow:0 0 0 6px rgba(239,68,68,0)}}
        .badge-blink{animation:badge-blink 1.5s infinite}
    </style>
    @stack('head')
</head>
<body class="admin-body">

    <div id="admin-layout-root">
        <aside id="admin-sidebar" data-collapsed="false">
            <div class="admin-sidebar-inner">
                <div class="admin-sidebar-scroll">
                    <div class="admin-sidebar-brand">
                        <svg class="admin-sidebar-brand-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span class="sidebar-text admin-sidebar-brand-text">{{ \App\Helpers\SiteHelper::siteName() }}</span>
                    </div>

                    <nav class="admin-sidebar-nav" aria-label="منوی ادمین">
                        @foreach(\App\Helpers\AdminMenu::getGroups() as $groupLabel => $keys)
                            @php
                                $visibleKeys = array_filter($keys, fn($k) => \App\Helpers\AdminMenu::isVisible($k));
                            @endphp
                            @if(count($visibleKeys) > 0)
                                <div class="admin-sidebar-group" data-group-index="{{ $loop->index }}">
                                    <button
                                        type="button"
                                        class="sidebar-group-toggle"
                                        data-group-toggle="{{ $loop->index }}"
                                        aria-expanded="false"
                                    >
                                        <span class="sidebar-group-label sidebar-text">{{ $groupLabel }}</span>
                                        <svg class="sidebar-group-caret" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                            <path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                    <div class="admin-sidebar-item-list sidebar-group-items is-collapsed" data-group-items="{{ $loop->index }}">
                                        @foreach($visibleKeys as $key)
                                            @php $routeName = \App\Helpers\AdminMenu::getRoute($key); $routeParams = \App\Helpers\AdminMenu::getRouteParams($key); @endphp
                                            <a href="{{ route($routeName, $routeParams) }}" class="sidebar-link {{ \App\Helpers\AdminMenu::isActive($key) ? 'active' : '' }}" style="position:relative">
                                                @include('admin.partials.sidebar-icon', ['key' => $key])
                                                <span class="sidebar-text">{{ \App\Helpers\AdminMenu::getTitle($key) }}</span>
                                                @if($key === 'support')
                                                    <span id="support-unread-badge" style="display:none;position:absolute;left:0.5rem;top:50%;transform:translateY(-50%);min-width:1.25rem;height:1.25rem;padding:0 0.375rem;border-radius:9999px;font-size:0.625rem;font-weight:700;align-items:center;justify-content:center;background:#ef4444;color:#fff;box-shadow:0 2px 6px rgba(239,68,68,0.4)">0</span>
                                                @endif
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach

                        <div class="admin-sidebar-footer">
                            <a href="{{ url('/') }}" target="_blank" rel="noopener noreferrer" class="sidebar-link">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                <span class="sidebar-text">مشاهده سایت</span>
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="admin-sidebar-logout-form">
                                @csrf
                                <button type="submit" class="sidebar-link sidebar-link--danger">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    <span class="sidebar-text">خروج</span>
                                </button>
                            </form>
                        </div>
                    </nav>
                </div>
            </div>
        </aside>

        <div class="admin-main-column">
            <header class="admin-header">
                <div class="admin-header-left">
                    <button type="button" id="admin-sidebar-toggle" class="admin-sidebar-toggle" aria-label="جمع کردن منو">
                        <svg id="admin-sidebar-toggle-icon-expand" class="admin-icon-hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M18 19l-7-7 7-7"/></svg>
                        <svg id="admin-sidebar-toggle-icon-collapse" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                    </button>
                    <details class="admin-mobile-nav">
                        <summary>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </summary>
                        <div class="admin-mobile-nav-panel">
                            @foreach(\App\Helpers\AdminMenu::getGroups() as $groupLabel => $keys)
                                @php $visibleKeys = array_filter($keys, fn($k) => \App\Helpers\AdminMenu::isVisible($k)); @endphp
                                @if(count($visibleKeys) > 0)
                                    <div class="admin-mobile-nav-group">{{ $groupLabel }}</div>
                                    @foreach($visibleKeys as $key)
                                        @php $routeName = \App\Helpers\AdminMenu::getRoute($key); $routeParams = \App\Helpers\AdminMenu::getRouteParams($key); @endphp
                                        <a href="{{ route($routeName, $routeParams) }}">{{ \App\Helpers\AdminMenu::getTitle($key) }}</a>
                                    @endforeach
                                @endif
                            @endforeach
                        </div>
                    </details>
                    <h1 class="admin-page-title">@yield('header', 'پنل مدیریت')</h1>
                </div>
                {{-- Profile Dropdown --}}
                @php
                    $adminUser = auth()->user();
                    $adminAvatarUrl = $adminUser->avatar ? url($adminUser->avatar) : null;
                    $adminInitial   = mb_substr($adminUser->name ?? $adminUser->mobile, 0, 1);
                @endphp
                <div x-data="{ open: false }" @click.outside="open = false" class="relative" style="z-index:50">
                    <button @click="open = !open" type="button"
                            class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl hover:bg-gray-100 transition-all group">
                        {{-- Avatar --}}
                        @if($adminAvatarUrl)
                        <img src="{{ $adminAvatarUrl }}" class="w-8 h-8 rounded-full object-cover ring-2 ring-white shadow-sm admin-avatar-img" alt="{{ $adminUser->name }}">
                        @else
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center ring-2 ring-white shadow-sm admin-avatar-initials">
                            <span class="text-xs font-bold text-white">{{ $adminInitial }}</span>
                        </div>
                        @endif
                        <div class="hidden sm:block text-right">
                            <p class="text-xs font-semibold text-gray-700 leading-none">{{ $adminUser->name ?? 'ادمین' }}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5 leading-none">{{ $adminUser->mobile }}</p>
                        </div>
                        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    {{-- Dropdown --}}
                    <div x-show="open" x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                         class="absolute left-0 top-full mt-2 w-56 bg-white rounded-2xl shadow-xl border border-gray-100 py-2"
                         style="display:none">
                        {{-- User info --}}
                        <div class="px-4 py-3 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                @if($adminAvatarUrl)
                                <img src="{{ $adminAvatarUrl }}" class="w-10 h-10 rounded-full object-cover ring-2 ring-indigo-100">
                                @else
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center">
                                    <span class="text-sm font-bold text-white">{{ $adminInitial }}</span>
                                </div>
                                @endif
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $adminUser->name ?? 'ادمین' }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $adminUser->mobile }}</p>
                                </div>
                            </div>
                        </div>
                        {{-- Links --}}
                        <div class="py-1">
                            <a href="{{ route('admin.profile.index') }}"
                               class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                                ویرایش پروفایل
                            </a>
                            <a href="{{ route('admin.dashboard') }}"
                               class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                                داشبورد
                            </a>
                        </div>
                        <div class="border-t border-gray-100 pt-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="flex w-full items-center gap-2.5 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                                    خروج از حساب
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="admin-main">
                @include('admin.partials.persian-date-admin-assets')
                @include('admin.partials.license-transient-banner')
                @if(session('success'))
                    <div class="admin-flash admin-flash--success" role="status">
                        <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if(session('warning'))
                    <div class="admin-flash admin-flash--warning" role="alert">
                        <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ session('warning') }}</span>
                    </div>
                @endif
                @if(session('error'))
                    <div class="admin-flash admin-flash--error" role="alert">
                        <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
                @if(session('info'))
                    <div class="admin-flash admin-flash--info" role="status">
                        <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        <span>{{ session('info') }}</span>
                    </div>
                @endif
                @if($errors->any())
                    <div class="admin-errors" role="alert">
                        <strong>خطاها:</strong>
                        <ul>
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

    @include('partials.admin-notifications-widget')
    @include('partials.chat-sounds')
    @include('partials.notification-permission', ['context' => 'admin'])
    @include('admin.partials.user-ticket-notifier')
    @include('admin.partials.floating-search-widget')

    <script>
    (function(){
        var sidebar = document.getElementById('admin-sidebar');
        var toggle = document.getElementById('admin-sidebar-toggle');
        var iconExpand = document.getElementById('admin-sidebar-toggle-icon-expand');
        var iconCollapse = document.getElementById('admin-sidebar-toggle-icon-collapse');
        var key = 'customer-admin-sidebar-collapsed';
        if (sidebar && toggle) {
            var collapsed = localStorage.getItem(key) === 'true';
            function apply() {
                sidebar.setAttribute('data-collapsed', collapsed ? 'true' : 'false');
                if (iconExpand) iconExpand.classList.toggle('admin-icon-hidden', !collapsed);
                if (iconCollapse) iconCollapse.classList.toggle('admin-icon-hidden', collapsed);
            }
            apply();
            toggle.addEventListener('click', function() {
                collapsed = !collapsed;
                localStorage.setItem(key, collapsed ? 'true' : 'false');
                apply();
            });
        }
    })();
    // Sidebar menu groups accordion (collapse/expand); گروه حاوی صفحهٔ جاری همیشه باز می‌ماند
    (function(){
        var toggles = document.querySelectorAll('[data-group-toggle]');
        toggles.forEach(function(btn){
            var idx = btn.getAttribute('data-group-toggle');
            var group = btn.closest('.admin-sidebar-group');
            if (!group) return;

            var items = group.querySelector('[data-group-items]');
            var caret = group.querySelector('.sidebar-group-caret');
            if (!items || !caret) return;

            var storageKey = 'customer-admin-group-open-' + idx;
            var hasActive = !!group.querySelector('.sidebar-link.active');
            var open = hasActive || localStorage.getItem(storageKey) === 'true';

            function setOpen(isOpen){
                if (isOpen) {
                    items.classList.remove('is-collapsed');
                    btn.setAttribute('aria-expanded', 'true');
                    caret.classList.add('is-open');
                } else {
                    items.classList.add('is-collapsed');
                    btn.setAttribute('aria-expanded', 'false');
                    caret.classList.remove('is-open');
                }
            }

            setOpen(open);
            if (hasActive) {
                localStorage.setItem(storageKey, 'true');
            }

            btn.addEventListener('click', function(e){
                e.preventDefault();
                open = items.classList.contains('is-collapsed');
                localStorage.setItem(storageKey, open ? 'true' : 'false');
                setOpen(open);
            });
        });
    })();
    // Polling for unread support ticket count (notification badge)
    (function(){
        var badge = document.getElementById('support-unread-badge');
        if (!badge) return;
        var platformUrl = '{{ rtrim(config("site.platform_url", ""), "/") }}';
        var licenseKey = '{{ optional(\App\Models\License::current())->license_key ?? "" }}';
        var domain = '{{ optional(\App\Models\License::current())->domain ?? "" }}';
        if (!platformUrl || !licenseKey || !domain) return;
        function checkUnread() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', platformUrl + '/api/support-tickets/unread-count?license_key=' + encodeURIComponent(licenseKey) + '&domain=' + encodeURIComponent(domain), true);
            xhr.timeout = 8000;
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (data.success && data.unread_count > 0) {
                            badge.textContent = data.unread_count;
                            badge.style.display = 'inline-flex';
                            badge.classList.add('badge-blink');
                        } else {
                            badge.style.display = 'none';
                            badge.classList.remove('badge-blink');
                        }
                    } catch(e) {}
                }
            };
            xhr.onerror = function(){};
            xhr.send();
        }
        checkUnread();
        setInterval(checkUnread, 30000);
    })();
    </script>
    @stack('scripts')
    @yield('scripts')
</body>
</html>

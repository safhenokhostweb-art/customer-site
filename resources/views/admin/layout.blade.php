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
    @vite(['resources/css/app.css'])
    @include('partials.platform-styles')
    @include('admin.partials.persian-date-admin-styles')
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
                                            <a href="{{ route($routeName, $routeParams) }}" class="sidebar-link {{ \App\Helpers\AdminMenu::isActive($key) ? 'active' : '' }}">
                                                @include('admin.partials.sidebar-icon', ['key' => $key])
                                                <span class="sidebar-text">{{ \App\Helpers\AdminMenu::getTitle($key) }}</span>
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
                <span class="admin-header-user">{{ auth()->user()->mobile ?? auth()->user()->email }}</span>
            </header>

            <main class="admin-main">
                @include('admin.partials.persian-date-admin-assets')
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
    </script>
    @stack('scripts')
</body>
</html>

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
    @php
        $siteFavicon = \App\Models\Setting::get('site_favicon', '');
        if ($siteFavicon && \Illuminate\Support\Facades\Storage::disk('public')->exists($siteFavicon)) {
            $faviconUrl  = \Illuminate\Support\Facades\Storage::url($siteFavicon);
            $faviconMime = match(strtolower(pathinfo($faviconUrl, PATHINFO_EXTENSION))) {
                'ico'  => 'image/x-icon',
                'svg'  => 'image/svg+xml',
                'png'  => 'image/png',
                'gif'  => 'image/gif',
                'webp' => 'image/webp',
                default => 'image/jpeg',
            };
            echo '<link rel="icon" type="' . $faviconMime . '" href="' . $faviconUrl . '">';
            echo '<link rel="shortcut icon" href="' . $faviconUrl . '">';
        }
    @endphp
</head>
<body class="admin-body">

    <div id="admin-layout-root">
        <aside id="admin-sidebar" data-collapsed="false">
            <div class="admin-sidebar-inner">
                <div class="admin-sidebar-scroll">
                    <div class="admin-sidebar-brand">
                        <x-core-ui::icon name="store" class="admin-sidebar-brand-icon" aria-hidden="true" />
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
                                        <x-core-ui::icon name="chevron-down" class="sidebar-group-caret" aria-hidden="true" />
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
                                <x-core-ui::icon name="external-link" aria-hidden="true" />
                                <span class="sidebar-text">مشاهده سایت</span>
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="admin-sidebar-logout-form">
                                @csrf
                                <button type="submit" class="sidebar-link sidebar-link--danger">
                                    <x-core-ui::icon name="logout" aria-hidden="true" />
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
                        <x-core-ui::icon name="chevron-left" id="admin-sidebar-toggle-icon-expand" class="admin-icon-hidden" aria-hidden="true" />
                        <x-core-ui::icon name="chevron-right" id="admin-sidebar-toggle-icon-collapse" aria-hidden="true" />
                    </button>
                    <details class="admin-mobile-nav">
                        <summary>
                            <x-core-ui::icon name="menu" aria-hidden="true" />
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
                <div id="admin-profile-menu" class="relative" style="z-index:50">
                    <button id="admin-profile-btn" type="button"
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
                        <x-core-ui::icon name="chevron-down" id="admin-profile-caret" class="w-3.5 h-3.5 text-gray-400 transition-transform" />
                    </button>

                    {{-- Dropdown --}}
                    <div id="admin-profile-dropdown"
                         class="absolute left-0 top-full mt-2 w-56 bg-white rounded-2xl shadow-xl border border-gray-100 py-2"
                         style="display:none;z-index:100">
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
                                <x-core-ui::icon name="user" class="w-4 h-4" />
                                ویرایش پروفایل
                            </a>
                            <a href="{{ route('admin.dashboard') }}"
                               class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                <x-core-ui::icon name="dashboard" class="w-4 h-4" />
                                داشبورد
                            </a>
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
            </header>

            <main class="admin-main">
                @include('admin.partials.persian-date-admin-assets')
                @include('admin.partials.license-transient-banner')
                @if(session('success'))
                    <div class="admin-flash admin-flash--success" role="status">
                        <x-core-ui::icon name="check-circle" aria-hidden="true" />
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if(session('warning'))
                    <div class="admin-flash admin-flash--warning" role="alert">
                        <x-core-ui::icon name="alert-triangle" aria-hidden="true" />
                        <span>{{ session('warning') }}</span>
                    </div>
                @endif
                @if(session('error'))
                    <div class="admin-flash admin-flash--error" role="alert">
                        <x-core-ui::icon name="x-circle" aria-hidden="true" />
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
                @if(session('info'))
                    <div class="admin-flash admin-flash--info" role="status">
                        <x-core-ui::icon name="info-circle" aria-hidden="true" />
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
    <script>
    /* ─── Admin Profile Dropdown ─── */
    (function(){
        var menu     = document.getElementById('admin-profile-menu');
        var btn      = document.getElementById('admin-profile-btn');
        var dropdown = document.getElementById('admin-profile-dropdown');
        var caret    = document.getElementById('admin-profile-caret');
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
    </script>
</body>
</html>

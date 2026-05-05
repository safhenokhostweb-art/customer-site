<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'پنل مدیریت') — {{ \App\Helpers\SiteHelper::siteName() }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { vazir: ['Vazirmatn', 'Tahoma', 'sans-serif'] } } } };</script>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @include('partials.platform-styles')
    <style>
        body { font-family: Vazirmatn, Tahoma, sans-serif; direction: rtl; }
        .sidebar-link { display: flex; align-items: center; padding: 0.65rem 1rem; font-size: 0.9rem; font-weight: 500; color: #374151; border-radius: 0.5rem; transition: all 0.15s; }
        .sidebar-link svg { width: 1.125rem; height: 1.125rem; min-width: 1.125rem; }
        .sidebar-link:hover { background: #eef2ff; color: #4f46e5; }
        .sidebar-link.active { background: #eef2ff; color: #4f46e5; font-weight: 600; }
        #admin-sidebar[data-collapsed="true"] { width: 5rem; min-width: 5rem; }
        #admin-sidebar[data-collapsed="true"] .admin-sidebar-inner { width: 5rem; min-width: 5rem; }
        #admin-sidebar[data-collapsed="true"] .sidebar-text { display: none; }
        #admin-sidebar[data-collapsed="true"] .sidebar-group-label { display: none; }
        .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 500; font-size: 0.875rem; transition: all 0.15s; }
        .btn-primary { background: #4f46e5; color: #fff; border: none; }
        .btn-primary:hover { background: #4338ca; }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-danger { background: #dc2626; color: #fff; border: none; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-sm { padding: 0.35rem 0.75rem; font-size: 0.8rem; }
        .card { background: #fff; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; overflow: hidden; }
        .card-header { padding: 1rem 1.5rem; border-bottom: 1px solid #e5e7eb; background: #f8fafc; font-weight: 600; }
        .card-body { padding: 1.25rem 1.5rem; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem 1rem; text-align: right; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-weight: 600; font-size: 0.8rem; color: #64748b; }
        tr:hover td { background: #f8fafc; }
        .badge { display: inline-flex; padding: 0.2rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .form-label { display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.4rem; }
        input[type="text"], input[type="email"], input[type="number"], input[type="url"], input[type="tel"], input[type="password"], textarea, select { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-family: inherit; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,0.2); }
        .form-group { margin-bottom: 1rem; }
    </style>
    @stack('head')
</head>
<body class="min-h-screen bg-slate-50">

    <div class="flex h-screen overflow-hidden" id="admin-layout-root">
        {{-- منوی کناری جمع‌شونده --}}
        <aside id="admin-sidebar" class="hidden md:flex md:flex-shrink-0 transition-[width] duration-300 ease-in-out w-64 bg-white border-l border-slate-200" data-collapsed="false">
            <div class="admin-sidebar-inner flex flex-col w-64 min-w-0 flex-1">
                <div class="flex flex-col flex-grow overflow-y-auto overflow-x-hidden">
                    {{-- برند --}}
                    <div class="flex items-center flex-shrink-0 px-4 py-5 border-b border-slate-200 min-h-[4rem]">
                        <svg class="w-7 h-7 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span class="sidebar-text mr-3 text-lg font-bold text-slate-800 whitespace-nowrap overflow-hidden">{{ \App\Helpers\SiteHelper::siteName() }}</span>
                    </div>

                    {{-- منوی گروه‌بندی‌شده --}}
                    <nav class="flex-1 px-3 py-4 overflow-y-auto">
                        @foreach(\App\Helpers\AdminMenu::getGroups() as $groupLabel => $keys)
                            @php
                                $visibleKeys = array_filter($keys, fn($k) => \App\Helpers\AdminMenu::isVisible($k));
                            @endphp
                            @if(count($visibleKeys) > 0)
                                <div class="mb-4">
                                    <div class="sidebar-group-label sidebar-text px-3 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                                        {{ $groupLabel }}
                                    </div>
                                    <div class="mt-1 space-y-0.5">
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

                        <div class="pt-4 mt-4 border-t border-slate-200">
                            <a href="{{ url('/') }}" target="_blank" class="sidebar-link">
                                <svg class="w-5 h-5 ml-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                <span class="sidebar-text">مشاهده سایت</span>
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                                @csrf
                                <button type="submit" class="sidebar-link w-full text-right text-red-600 hover:bg-red-50 hover:text-red-700 border-0 bg-transparent cursor-pointer">
                                    <svg class="w-5 h-5 ml-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    <span class="sidebar-text">خروج</span>
                                </button>
                            </form>
                        </div>
                    </nav>
                </div>
            </div>
        </aside>

        {{-- محتوای اصلی --}}
        <div class="flex flex-col flex-1 overflow-hidden">
            {{-- هدر --}}
            <header class="bg-white border-b border-slate-200 px-4 md:px-6 py-4 flex-shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <button type="button" id="admin-sidebar-toggle" class="hidden md:inline-flex items-center justify-center w-10 h-10 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors" aria-label="جمع کردن منو">
                            <svg id="admin-sidebar-toggle-icon-expand" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M18 19l-7-7 7-7"/></svg>
                            <svg id="admin-sidebar-toggle-icon-collapse" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                        </button>
                        <details class="md:hidden relative">
                            <summary class="list-none p-2 rounded-lg text-slate-600 hover:bg-slate-100 cursor-pointer">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            </summary>
                            <div class="absolute right-0 top-full mt-2 w-64 max-h-[80vh] overflow-y-auto bg-white rounded-xl shadow-xl border border-slate-200 py-2 z-50">
                                @foreach(\App\Helpers\AdminMenu::getGroups() as $groupLabel => $keys)
                                    @php $visibleKeys = array_filter($keys, fn($k) => \App\Helpers\AdminMenu::isVisible($k)); @endphp
                                    @if(count($visibleKeys) > 0)
                                        <div class="px-3 py-1.5 text-xs font-semibold text-slate-500 bg-slate-50">{{ $groupLabel }}</div>
                                        @foreach($visibleKeys as $key)
                                            @php $routeName = \App\Helpers\AdminMenu::getRoute($key); $routeParams = \App\Helpers\AdminMenu::getRouteParams($key); @endphp
                                            <a href="{{ route($routeName, $routeParams) }}" class="block px-4 py-2.5 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700">{{ \App\Helpers\AdminMenu::getTitle($key) }}</a>
                                        @endforeach
                                    @endif
                                @endforeach
                            </div>
                        </details>
                        <h1 class="text-xl md:text-2xl font-bold text-slate-900">@yield('header', 'پنل مدیریت')</h1>
                    </div>
                    <span class="text-sm text-slate-500">{{ auth()->user()->mobile ?? auth()->user()->email }}</span>
                </div>
            </header>

            {{-- محتوا --}}
            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                @if(session('success'))
                    <div class="mb-6 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-2">
                        <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('warning'))
                    <div class="mb-6 p-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 flex items-center gap-2">
                        <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ session('warning') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 flex items-center gap-2">
                        <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        {{ session('error') }}
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
                if (iconExpand) iconExpand.classList.toggle('hidden', !collapsed);
                if (iconCollapse) iconCollapse.classList.toggle('hidden', collapsed);
            }
            apply();
            toggle.addEventListener('click', function() {
                collapsed = !collapsed;
                localStorage.setItem(key, collapsed ? 'true' : 'false');
                apply();
            });
        }
    })();
    </script>
    @stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'حساب من') — {{ \App\Helpers\SiteHelper::siteName() }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { vazir: ['Vazirmatn', 'Tahoma', 'sans-serif'] } } } };</script>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @include('partials.platform-styles')
    <style>
        body { font-family: Vazirmatn, Tahoma, sans-serif; direction: rtl; }
        .account-sidebar-link { display: flex; align-items: center; padding: 0.65rem 1rem; font-size: 0.9rem; font-weight: 500; color: #374151; border-radius: 0.5rem; transition: all 0.15s; }
        .account-sidebar-link svg { width: 1.125rem; height: 1.125rem; min-width: 1.125rem; }
        .account-sidebar-link:hover { background: #eef2ff; color: #4f46e5; }
        .account-sidebar-link.active { background: #eef2ff; color: #4f46e5; font-weight: 600; }
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
        @media (max-width: 767px) { #account-sidebar { display: none; } #account-mobile-nav { display: block; } }
        @media (min-width: 768px) { #account-mobile-nav { display: none; } }
    </style>
    @stack('head')
</head>
<body class="min-h-screen bg-slate-50">
<div class="flex min-h-screen">
        <aside id="account-sidebar" class="hidden md:flex md:flex-shrink-0 w-64 bg-white border-l border-slate-200 flex-col">
            <div class="flex items-center px-4 py-5 border-b border-slate-200">
                <svg class="w-7 h-7 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span class="mr-3 text-lg font-bold text-slate-800">{{ \App\Helpers\SiteHelper::siteName() }}</span>
            </div>
            <nav class="flex-1 px-3 py-4 overflow-y-auto">
                @foreach(\App\Helpers\AccountMenu::getGroups() as $groupLabel => $keys)
                    @php $visibleKeys = array_filter($keys, fn($k) => \App\Helpers\AccountMenu::isVisible($k)); @endphp
                    @if(count($visibleKeys) > 0)
                        <div class="mb-4">
                            <div class="px-3 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">{{ $groupLabel }}</div>
                            <div class="mt-1 space-y-0.5">
                                @foreach($visibleKeys as $key)
                                    <a href="{{ route(\App\Helpers\AccountMenu::getRoute($key)) }}" class="account-sidebar-link {{ \App\Helpers\AccountMenu::isActive($key) ? 'active' : '' }}">
                                        @include('account.partials.sidebar-icon', ['key' => $key])
                                        <span>{{ \App\Helpers\AccountMenu::getTitle($key) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
                <div class="pt-4 mt-4 border-t border-slate-200">
                    <a href="{{ url('/') }}" class="account-sidebar-link"><svg class="w-5 h-5 ml-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg><span>مشاهده سایت</span></a>
                    @if(auth()->user()->isTenantAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="account-sidebar-link"><svg class="w-5 h-5 ml-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg><span>پنل مدیریت</span></a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="mt-1">
                        @csrf
                        <button type="submit" class="account-sidebar-link w-full text-right text-red-600 hover:bg-red-50 hover:text-red-700 border-0 bg-transparent cursor-pointer">
                            <svg class="w-5 h-5 ml-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            <span>خروج</span>
                        </button>
                    </form>
                </div>
            </nav>
        </aside>
        <div class="flex flex-col flex-1 min-w-0">
            <header class="bg-white border-b border-slate-200 px-4 md:px-6 py-4 flex-shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <details id="account-mobile-nav" class="relative">
                            <summary class="list-none p-2 rounded-lg text-slate-600 hover:bg-slate-100 cursor-pointer"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></summary>
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
                    <span class="text-sm text-slate-500">{{ auth()->user()->name }} — {{ auth()->user()->mobile ?? auth()->user()->email }}</span>
                </div>
            </header>
            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                @if(session('success'))
                    <div class="mb-6 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-2">
                        <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        {{ session('success') }}
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
    @include('partials.account-notification-bell')
    @stack('scripts')
</body>
</html>

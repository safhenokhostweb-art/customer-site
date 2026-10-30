<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ویرایشگر قالب Canvas') — {{ \App\Helpers\SiteHelper::siteName() }}</title>
    {{-- The one head-tag renderer. This layout previously emitted no robots
         directive, no description and no canonical; a Canvas editor is a
         PREVIEW_EDITOR surface and is never indexable, so the directive is
         pinned here rather than inherited from the tenant's seo_robots
         setting. --}}
    <x-seo-meta robots="noindex, nofollow" :include-organization-json-ld="false" />
    {{-- Phase 0.4 (ADR-063): no Google Fonts — app.css bundles the local Vazirmatn faces. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Vazirmatn', sans-serif; margin: 0; padding: 0; overflow: hidden; height: 100vh; background: #f1f5f9; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        [x-cloak] { display: none !important; }
    </style>
    @stack('head')
</head>
<body>
    @yield('content')
    @stack('scripts')
</body>
</html>

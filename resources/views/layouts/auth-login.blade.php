<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ورود') — {{ \App\Helpers\PlatformUx::platformName() }}</title>
    <x-seo-meta robots="noindex, nofollow" :include-organization-json-ld="false" />
    <link rel="preload" as="font" href="{{ asset('assets/fonts/vazirmatn/Vazirmatn-Regular.woff2') }}" type="font/woff2" crossorigin>
    <link rel="preload" as="font" href="{{ asset('assets/fonts/vazirmatn/Vazirmatn-Bold.woff2') }}" type="font/woff2" crossorigin>
    <style>@include('partials.vazirmatn-font-faces-inline')</style>
    @if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif
    @include('partials.platform-styles')
    <style id="auth-solar-industrial-css">
        :root {
            --auth-solar-primary: #FF7900;
            --auth-solar-primary-hover: #E06600;
            --auth-solar-primary-soft: rgba(255, 121, 0, 0.2);
            --auth-solar-bg: #0B0B0B;
            --auth-solar-surface: #1E1E1E;
            --auth-solar-surface-card: #151515;
            --auth-solar-input: #12121F;
            --auth-solar-border: #2A2A2A;
            --auth-solar-border-hover: rgba(255, 121, 0, 0.5);
            --auth-solar-text: #F5F5F5;
            --auth-solar-text-muted: #A3A3A3;
        }
        .auth-login-body {
            margin: 0;
            min-height: 100vh;
            font-family: Vazirmatn, Tahoma, sans-serif;
            background: var(--auth-solar-bg);
            color: var(--auth-solar-text);
        }
        .auth-login-grid {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr;
        }
        @media (min-width: 900px) {
            .auth-login-grid {
                grid-template-columns: minmax(440px, 1.05fr) minmax(0, 1.2fr);
            }
        }
        .auth-login-panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem 2.5rem;
            box-sizing: border-box;
            background: var(--auth-solar-bg);
        }
        .auth-login-card-wrap {
            width: 100%;
            max-width: 420px;
        }
        .auth-login-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .auth-login-brand span {
            font-weight: 800;
            font-size: 1.25rem;
            color: #fff;
            letter-spacing: -0.02em;
        }
        .auth-login-brand span strong {
            color: var(--auth-solar-primary);
        }
        .auth-login-card {
            background: var(--auth-solar-surface);
            border-radius: 1.25rem;
            padding: 2rem 1.75rem 1.75rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6), 0 0 0 1px var(--auth-solar-border);
            border: 1px solid var(--auth-solar-border);
            box-sizing: border-box;
        }
        .auth-login-card h1 {
            margin: 0 0 0.4rem;
            font-size: 1.4rem;
            font-weight: 800;
            text-align: center;
            color: #fff;
        }
        .auth-login-card .auth-login-lead {
            margin: 0 0 1.5rem;
            text-align: center;
            font-size: 0.875rem;
            color: var(--auth-solar-text-muted);
            line-height: 1.6;
        }
        .auth-login-body .form-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--auth-solar-text);
            margin-bottom: 0.4rem;
        }
        .auth-login-body .form-control {
            width: 100%;
            padding: 0.75rem 0.95rem;
            background: var(--auth-solar-input);
            border: 1px solid var(--auth-solar-border);
            border-radius: 0.75rem;
            font-size: 1rem;
            color: #fff;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }
        .auth-login-body .form-control::placeholder {
            color: #666;
        }
        .auth-login-body .form-control:focus {
            outline: none;
            border-color: var(--auth-solar-primary);
            box-shadow: 0 0 0 3px var(--auth-solar-primary-soft);
            background: #181828;
        }
        .auth-login-body .space-y-4 > * + * { margin-top: 1.15rem; }
        .auth-login-remember {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--auth-solar-text-muted);
            cursor: pointer;
        }
        .auth-login-remember input {
            width: 1.1rem;
            height: 1.1rem;
            accent-color: var(--auth-solar-primary);
            cursor: pointer;
        }
        .auth-login-body .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.8rem 1.25rem;
            border-radius: 0.75rem;
            font-weight: 700;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .auth-login-body .btn-primary {
            background: var(--auth-solar-primary);
            color: #fff;
            width: 100%;
            box-shadow: 0 4px 14px rgba(255, 121, 0, 0.4);
        }
        .auth-login-body .btn-primary:hover {
            background: var(--auth-solar-primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(255, 121, 0, 0.5);
        }
        .auth-login-body .btn-primary:active {
            transform: translateY(0);
        }
        .auth-login-body .alert {
            padding: 0.75rem 1rem;
            border-radius: 0.65rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            line-height: 1.5;
        }
        .auth-login-body .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
        }
        .auth-login-body .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.4);
            color: #86efac;
        }
        .auth-login-footer-text {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.875rem;
            color: var(--auth-solar-text-muted);
        }
        .auth-login-footer-text a {
            color: var(--auth-solar-primary);
            font-weight: 700;
            text-decoration: none;
            margin-right: 0.35rem;
        }
        .auth-login-footer-text a:hover {
            text-decoration: underline;
        }
        .auth-login-hero {
            display: none;
            position: relative;
            background: linear-gradient(135deg, #12121F 0%, #050508 100%);
            border-right: 1px solid var(--auth-solar-border);
            color: #fff;
            overflow: hidden;
        }
        @media (min-width: 900px) {
            .auth-login-hero { display: flex; align-items: center; justify-content: center; }
        }
        .auth-login-hero-inner {
            position: relative;
            z-index: 1;
            padding: 3rem 2.5rem;
            max-width: 440px;
            text-align: center;
        }
        .auth-solar-sun-icon {
            width: 80px;
            height: 80px;
            padding: 12px;
            box-sizing: border-box;
            overflow: visible;
            margin: 0 auto 1.5rem;
            background: rgba(255, 121, 0, 0.15);
            border: 1px solid rgba(255, 121, 0, 0.3);
            border-radius: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--auth-solar-primary);
            box-shadow: 0 0 30px rgba(255, 121, 0, 0.2);
        }
        .auth-solar-sun-icon svg {
            overflow: visible;
        }
        .auth-login-hero h2 {
            margin: 0 0 0.75rem;
            font-size: 1.6rem;
            font-weight: 800;
            color: #fff;
        }
        .auth-login-hero h2 span {
            color: var(--auth-solar-primary);
        }
        .auth-login-hero p {
            margin: 0;
            color: var(--auth-solar-text-muted);
            font-size: 0.95rem;
            line-height: 1.8;
        }
        .auth-solar-glow {
            position: absolute;
            width: 450px;
            height: 450px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 121, 0, 0.15) 0%, transparent 70%);
            pointer-events: none;
        }
        .auth-solar-glow-1 { top: -100px; left: -100px; }
        .auth-solar-glow-2 { bottom: -100px; right: -100px; }
        .tb-logo-anim--rotate_pop { display: inline-block; transform-origin: center center; animation: tb-anim-rotate-pop 5s cubic-bezier(.34,1.56,.64,1) infinite; will-change: transform; }
        @keyframes tb-anim-rotate-pop { 0% { transform: rotate(0deg) scale(1); } 45% { transform: rotate(360deg) scale(1); } 55% { transform: rotate(360deg) scale(1.28); } 70% { transform: rotate(360deg) scale(.92); } 85% { transform: rotate(360deg) scale(1.06); } 95%, 100% { transform: rotate(360deg) scale(1); } }
    </style>
    <style>@include('theme-runtime::css.contract')</style>
    <style>@include('theme-runtime::css.blocks')</style>
    @stack('head')
</head>
<body class="auth-login-body">
    @if(!empty($renderedAuthHtml))
        {!! $renderedAuthHtml !!}
    @else
        <div class="auth-login-grid">
            <div class="auth-login-panel">
                <div class="auth-login-card-wrap">
                    <div class="auth-login-brand">
                        <x-site-brand />
                    </div>
                    @if(session('success'))
                        <div class="alert alert-success" role="status">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
                    @endif
                    @yield('content')
                </div>
            </div>
            <div class="auth-login-hero" aria-hidden="true">
                <span class="auth-solar-glow auth-solar-glow-1"></span>
                <span class="auth-solar-glow auth-solar-glow-2"></span>
                <div class="auth-login-hero-inner">
                    <div class="auth-solar-sun-icon">
<<<<<<< HEAD
                        {{-- White-label (every tenant's sign-in): a neutral catalog icon and copy, no industry. --}}
                        {!! \SaaSPlatform\ThemeRuntime\Support\IconLibrary::svg('shield-check') !!}
                    </div>
                    <h2>ورود امن به <span>حساب کاربری</span></h2>
                    <p>دسترسی امن به پنل کاربری، سفارش‌ها، دوره‌ها و پشتیبانی آنلاین، همه در یک حساب.</p>
=======
                        <i class="fas fa-sun tb-logo-anim--rotate_pop"><svg width="40" height="40" viewBox="0 0 512 512" fill="currentColor" aria-hidden="true"><path d="M361.5 1.2c5 2.1 8.6 6.6 9.6 11.9L391 121l107.9 19.8c5.3 1 9.8 4.6 11.9 9.6s1 10.9-2.7 14.8L429.2 248l78.8 82.8c3.7 3.9 4.8 9.8 2.7 14.8s-6.6 8.6-11.9 9.6L391 375l-19.8 107.9c-1 5.3-4.6 9.8-9.6 11.9s-10.9 1-14.8-2.7L264 413.2l-82.8 78.8c-3.9 3.7-9.8 4.8-14.8 2.7s-8.6-6.6-9.6-11.9L137 375l-107.9-19.8c-5.3-1-9.8-4.6-11.9-9.6s-1-10.9 2.7-14.8L98.8 248 20 165.2c-3.7-3.9-4.8-9.8-2.7-14.8s6.6-8.6 11.9-9.6L137 121l19.8-107.9c1-5.3 4.6-9.8 9.6-11.9s10.9-1 14.8 2.7L264 82.8l82.8-78.8c3.9-3.7 9.8-4.8 14.8-2.7zM256 160c-53 0-96 43-96 96s43 96 96 96 96-43 96-96-43-96-96-96z"/></svg></i>
                    </div>
                    <h2>پلتفرم یکپارچه <span>صنعت برق</span></h2>
                    <p>دسترسی امن به پنل کاربری، سفارش تجهیزات خورشیدی، دوره‌های آکادمی تخصصی و پشتیبانی آنلاین پروژه‌های نیروگاهی.</p>
>>>>>>> 71c527c (feat(customer-site): finalize uncommitted local changes and sync theme builder assets)
                </div>
            </div>
        </div>
    @endif
    @stack('scripts')
</body>
</html>

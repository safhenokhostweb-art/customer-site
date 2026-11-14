{{-- Shared header, tabs and styles of the solar calculator admin pages. --}}
@push('head')
<style>
.sc-admin-tabs{display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:1.25rem;padding:.3rem;background:#f1f5f9;border-radius:.9rem;width:fit-content;max-width:100%}
.sc-admin-tabs a{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border-radius:.65rem;font-size:.85rem;font-weight:700;color:#475569;text-decoration:none;transition:background .15s,color .15s}
.sc-admin-tabs a:hover{color:#1e293b;background:#fff}
.sc-admin-tabs a.is-active{background:#fff;color:#4338ca;box-shadow:0 1px 3px rgba(15,23,42,.12)}
.sc-kpis{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:.75rem;margin-bottom:1.25rem}
.sc-kpi{display:flex;flex-direction:column;gap:.2rem;padding:.9rem 1.1rem;border-radius:.875rem;background:#fff;border:1px solid #e2e8f0;text-decoration:none;color:inherit;transition:border-color .15s,box-shadow .15s}
.sc-kpi:hover,.sc-kpi.is-active{border-color:#a5b4fc;box-shadow:0 4px 14px rgba(99,102,241,.12)}
.sc-kpi span{font-size:.72rem;font-weight:600;color:#64748b}
.sc-kpi b{font-size:1.45rem;font-weight:800;color:#1e293b;line-height:1.15}
.sc-badge{display:inline-flex;align-items:center;padding:.18rem .6rem;border-radius:999px;font-size:.72rem;font-weight:700;background:#f1f5f9;color:#475569}
.sc-badge--new{background:#e0e7ff;color:#3730a3}.sc-badge--contacted{background:#e0f2fe;color:#075985}.sc-badge--site_visit{background:#fef9c3;color:#854d0e}.sc-badge--won{background:#dcfce7;color:#166534}.sc-badge--lost{background:#fee2e2;color:#991b1b}
.sc-code{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.8rem;direction:ltr;unicode-bidi:isolate;background:#f8fafc;border:1px solid #e2e8f0;border-radius:.4rem;padding:.1rem .4rem;display:inline-block;max-width:100%;overflow-wrap:anywhere}
.sc-grid-2{display:grid;gap:1rem;grid-template-columns:1fr}
@media (min-width:1024px){.sc-grid-2{grid-template-columns:minmax(0,2fr) minmax(0,1fr)}}
.sc-dl{display:grid;grid-template-columns:auto 1fr;gap:.45rem 1rem;margin:0;font-size:.875rem}
.sc-dl dt{color:#64748b}.sc-dl dd{margin:0;font-weight:600}
.sc-spec-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:.6rem}
.sc-spec{border:1px solid #e2e8f0;border-radius:.75rem;padding:.65rem .8rem;background:#f8fafc}
.sc-spec span{display:block;color:#64748b;font-size:.72rem}.sc-spec b{font-size:1.05rem}
.sc-row-actions{display:flex;flex-wrap:wrap;gap:.4rem;align-items:center}
.sc-help{font-size:.78rem;color:#64748b;line-height:1.8}
.sc-repeater-row{display:grid;gap:.5rem;align-items:end;padding:.75rem;border:1px solid #e2e8f0;border-radius:.75rem;background:#f8fafc;margin-bottom:.5rem}
.sc-repeater-row .btn-danger{height:2.4rem}
</style>
@endpush

@include('admin.partials.breadcrumb', ['crumbs' => [
    ['label' => 'داشبورد', 'url' => route('admin.dashboard')],
    ['label' => 'ماشین‌حساب خورشیدی', 'url' => route('admin.solar-calculator.leads.index')],
    ['label' => $crumb, 'url' => null],
]])

@php
    $scTabs = [
        'leads' => ['درخواست‌ها و پیش‌فاکتورها', 'admin.solar-calculator.leads.index', 'admin.solar-calculator.leads.*', 'users'],
        'formulas' => ['فرمول‌ها و ضرایب', 'admin.solar-calculator.formulas.index', 'admin.solar-calculator.formulas.*', 'calculator'],
        'settings' => ['تنظیمات و ظاهر', 'admin.solar-calculator.settings.edit', 'admin.solar-calculator.settings.*', 'settings'],
        'rates' => ['نرخ ارز', 'admin.exchange-rates.index', 'admin.exchange-rates.*', 'wallet'],
    ];
@endphp
<nav class="sc-admin-tabs" aria-label="بخش‌های ماشین‌حساب خورشیدی">
    @foreach($scTabs as [$label, $route, $pattern, $icon])
        <a href="{{ route($route) }}" class="{{ request()->routeIs($pattern) ? 'is-active' : '' }}" @if(request()->routeIs($pattern)) aria-current="page" @endif>
            <x-core-ui::icon :name="$icon" class="w-4 h-4" />{{ $label }}
        </a>
    @endforeach
</nav>

@if(session('error'))
    <div class="admin-flash admin-flash--error" role="alert" style="margin-bottom:1rem"><span class="flex-1">{{ session('error') }}</span></div>
@endif
@if($errors->any())
    <div class="admin-flash admin-flash--error" role="alert" style="margin-bottom:1rem">
        <ul class="flex-1" style="margin:0;padding-right:1rem;list-style:disc">
            @foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach
        </ul>
    </div>
@endif

@extends('admin.layout')
@section('title', 'سئو پیشرفته')
@section('header', 'سئو پیشرفته')
@push('head')
<style>
.seo-kpi{padding:1rem 1.25rem;border-radius:.875rem;background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(15,23,42,.05);display:flex;align-items:flex-start;gap:.875rem}
.seo-kpi__icon{width:2.75rem;height:2.75rem;border-radius:.75rem;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.seo-kpi__label{font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.2rem}
.seo-kpi__value{font-size:1.5rem;font-weight:800;color:#1e293b;line-height:1}
.seo-kpi__sub{font-size:.7rem;color:#94a3b8;margin-top:.2rem}

.seo-check{display:flex;align-items:flex-start;gap:.75rem;padding:.75rem;border-radius:.75rem;border:1px solid #e2e8f0;background:#fff;margin-bottom:.5rem}
.seo-check--ok{border-color:#bbf7d0;background:#f0fdf4}
.seo-check--warn{border-color:#fde68a;background:#fefce8}
.seo-check--error{border-color:#fecaca;background:#fef2f2}
.seo-check__dot{width:1.5rem;height:1.5rem;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.75rem}
.seo-check__dot--ok{background:#dcfce7;color:#16a34a}
.seo-check__dot--warn{background:#fef9c3;color:#d97706}
.seo-check__dot--error{background:#fee2e2;color:#dc2626}
</style>
@endpush
@section('content')

@include('admin.partials.breadcrumb', ['crumbs' => [
    ['label' => 'داشبورد', 'url' => route('admin.dashboard')],
    ['label' => 'سئو پیشرفته', 'url' => null],
]])

<div class="admin-page">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">سئو پیشرفته</h1>
            <p class="admin-section-lead" style="margin-top:.3rem">نمای کلی وضعیت سئو سایت، متاتگ‌ها و محتوای منتشر شده.</p>
        </div>
        <div class="admin-page-tools">
            <a href="{{ url('/sitemap.xml') }}" target="_blank" rel="noopener" class="btn btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                Sitemap
            </a>
        </div>
    </div>

    {{-- Module Status --}}
    @if(!$enabled)
        <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:.875rem;padding:1rem 1.25rem;margin-bottom:1.5rem;color:#92400e;font-size:.875rem;font-weight:600">
            ⚠️ ماژول سئو پیشرفته فعال نیست. در پلتفرم، ماژول <code>seo_advanced</code> را برای این سایت فعال کنید.
        </div>
    @endif

    {{-- Content KPIs --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem">
        <div class="seo-kpi">
            <div class="seo-kpi__icon" style="background:#ede9fe">
                <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <div class="seo-kpi__label">صفحات ثابت</div>
                <div class="seo-kpi__value">{{ \App\Helpers\NumberHelper::formatFa($pagesCount) }}</div>
                <div class="seo-kpi__sub">{{ \App\Helpers\NumberHelper::formatFa($publishedPagesCount) }} منتشر شده</div>
            </div>
        </div>
        <div class="seo-kpi">
            <div class="seo-kpi__icon" style="background:#dbeafe">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            </div>
            <div>
                <div class="seo-kpi__label">مطالب وبلاگ</div>
                <div class="seo-kpi__value">{{ \App\Helpers\NumberHelper::formatFa($postsCount) }}</div>
                <div class="seo-kpi__sub">{{ \App\Helpers\NumberHelper::formatFa($publishedPostsCount) }} منتشر شده</div>
            </div>
        </div>
        <div class="seo-kpi">
            <div class="seo-kpi__icon" style="background:#dcfce7">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <div>
                <div class="seo-kpi__label">محصولات</div>
                <div class="seo-kpi__value">{{ \App\Helpers\NumberHelper::formatFa($productsCount) }}</div>
                <div class="seo-kpi__sub">{{ \App\Helpers\NumberHelper::formatFa($activeProductsCount) }} فعال</div>
            </div>
        </div>
        <div class="seo-kpi">
            <div class="seo-kpi__icon" style="background:#fef9c3">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div>
                <div class="seo-kpi__label">URL‌های ایندکس</div>
                <div class="seo-kpi__value">{{ \App\Helpers\NumberHelper::formatFa($publishedPagesCount + $publishedPostsCount + $activeProductsCount) }}</div>
                <div class="seo-kpi__sub">کل صفحات قابل ایندکس</div>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start">
        {{-- SEO Config --}}
        <div class="card">
            <div style="padding:.875rem 1.25rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-weight:700;font-size:.88rem;color:#1e293b">
                تنظیمات سئو پلتفرم
            </div>
            <div style="padding:1rem 1.25rem">
                @foreach([
                    ['seo_default_title', 'عنوان پیش‌فرض'],
                    ['seo_default_description', 'توضیح پیش‌فرض'],
                    ['seo_robots', 'robots'],
                    ['seo_twitter_card', 'Twitter Card'],
                ] as [$key, $label])
                    <div style="padding:.625rem 0;border-bottom:1px solid #f1f5f9">
                        <div style="font-size:.7rem;font-weight:600;color:#64748b;margin-bottom:.2rem">{{ $label }}</div>
                        <div style="font-size:.82rem;color:#1e293b">
                            {{ $seoConfig[$key] ?? config('seo.' . str_replace('seo_', 'default_', $key), '—') }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- SEO Checklist --}}
        <div class="card">
            <div style="padding:.875rem 1.25rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-weight:700;font-size:.88rem;color:#1e293b">
                چک‌لیست سئو
            </div>
            <div style="padding:1rem 1.25rem">
                @php
                    $checks = [
                        [!empty($seoConfig['seo_default_title']), 'عنوان پیش‌فرض تنظیم شده'],
                        [!empty($seoConfig['seo_default_description']), 'توضیح پیش‌فرض تنظیم شده'],
                        [$publishedPostsCount > 0, 'حداقل یک مطلب منتشر شده'],
                        [$publishedPagesCount > 0, 'حداقل یک صفحه منتشر شده'],
                        [$enabled, 'ماژول سئو پیشرفته فعال است'],
                    ];
                @endphp
                @foreach($checks as [$ok, $label])
                    <div class="seo-check {{ $ok ? 'seo-check--ok' : 'seo-check--warn' }}">
                        <div class="seo-check__dot {{ $ok ? 'seo-check__dot--ok' : 'seo-check__dot--warn' }}">
                            {{ $ok ? '✓' : '!' }}
                        </div>
                        <span style="font-size:.82rem;color:#334155">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Quick Links --}}
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-top:1.25rem">
        <a href="{{ route('admin.posts.index') }}" class="card" style="padding:.875rem 1rem;display:flex;align-items:center;gap:.5rem;text-decoration:none;color:#1e293b;font-size:.85rem;font-weight:600">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            مطالب وبلاگ
        </a>
        <a href="{{ route('admin.products.index') }}" class="card" style="padding:.875rem 1rem;display:flex;align-items:center;gap:.5rem;text-decoration:none;color:#1e293b;font-size:.85rem;font-weight:600">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            محصولات
        </a>
        <a href="{{ route('admin.pages.index') }}" class="card" style="padding:.875rem 1rem;display:flex;align-items:center;gap:.5rem;text-decoration:none;color:#1e293b;font-size:.85rem;font-weight:600">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            صفحات
        </a>
    </div>
</div>
@endsection

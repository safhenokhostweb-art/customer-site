@extends('admin.layout')
@section('title', 'محصولات')
@section('header', 'محصولات')
@section('content')

{{-- Breadcrumb inline --}}
@php
    $crumbs = [
        ['label' => 'داشبورد', 'url' => route('admin.dashboard')],
        ['label' => 'محصولات', 'url' => null],
    ];
@endphp
@if(!empty($crumbs))
<nav class="admin-breadcrumb" aria-label="مسیر صفحه">
    <ol class="admin-breadcrumb-list">
        @foreach($crumbs as $i => $crumb)
            @php
                $label = $crumb['label'] ?? '';
                $url = $crumb['url'] ?? null;
                $isLast = $i === array_key_last($crumbs);
            @endphp
            <li class="admin-breadcrumb-item">
                @if($isLast || empty($url))
                    <span class="admin-breadcrumb-current" aria-current="page">{{ $label }}</span>
                @else
                    <a href="{{ $url }}" class="admin-breadcrumb-link">{{ $label }}</a>
                @endif
                @unless($isLast)
                    <span class="admin-breadcrumb-sep" aria-hidden="true">›</span>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>
<style>
    .admin-breadcrumb { margin: 0 0 0.85rem; font-size: 0.78rem; color: #64748b; }
    .admin-breadcrumb-list { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem; }
    .admin-breadcrumb-item { display: inline-flex; align-items: center; gap: 0.35rem; }
    .admin-breadcrumb-link { color: #4f46e5; text-decoration: none; }
    .admin-breadcrumb-link:hover { text-decoration: underline; }
    .admin-breadcrumb-current { color: #334155; font-weight: 600; }
    .admin-breadcrumb-sep { color: #94a3b8; }
</style>
@endif

@include('admin.products.guide')

<div class="admin-page">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">محصولات</h1>
            <p class="admin-section-lead" style="margin-top:0.35rem">فهرست محصولات با وضعیت انتشار، موجودی و مسیر ویرایش سریع.</p>
        </div>
        <div class="admin-page-tools">
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">محصول جدید</a>
        </div>
    </div>

    <div class="admin-kpi-grid">
        <div class="admin-kpi-card">
            <p class="admin-kpi-card__label">تعداد کل</p>
            <p class="admin-kpi-card__value">{{ method_exists($products, 'total') ? number_format($products->total()) : number_format($products->count()) }}</p>
        </div>
        <div class="admin-kpi-card">
            <p class="admin-kpi-card__label">منتشر شده</p>
            <p class="admin-kpi-card__value">{{ number_format($products->where('status', \App\Models\Product::STATUS_PUBLISHED)->count()) }}</p>
        </div>
        <div class="admin-kpi-card">
            <p class="admin-kpi-card__label">پیش‌نویس</p>
            <p class="admin-kpi-card__value">{{ number_format($products->where('status', '!=', \App\Models\Product::STATUS_PUBLISHED)->count()) }}</p>
        </div>
    </div>

    <div class="card admin-table-wrap">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>عنوان</th><th>قیمت</th><th>موجودی</th><th>وضعیت</th><th>تاریخ‌ها (شمسی)</th><th>عملیات</th></tr>
                </thead>
                <tbody>
                    @forelse($products as $p)
                        <tr>
                            <td>
                                <div class="font-semibold">{{ $p->title }}</div>
                                @if($p->slug)
                                    <div class="text-xs text-slate-500" dir="ltr">{{ $p->slug }}</div>
                                @endif
                            </td>
                            <td>{!! \App\Helpers\CurrencyHelper::formatPrice($p->price) !!} {{ \App\Helpers\CurrencyHelper::unitLabel() }}</td>
                            <td>
                                @if($p->stock === null)
                                    <span class="text-xs text-slate-500">نامحدود</span>
                                @else
                                    {{ $p->stock }}
                                @endif
                            </td>
                            <td>
                                @if($p->status === \App\Models\Product::STATUS_PUBLISHED)
                                    <span class="badge badge-published">منتشر شده</span>
                                @else
                                    <span class="badge badge-draft">پیش‌نویس</span>
                                @endif
                            </td>
                            <td class="text-xs text-slate-600">
                                <div>ثبت: {{ \App\Helpers\Jalali::formatFa($p->created_at, 'Y/m/d H:i') }}</div>
                                @if($p->updated_at && $p->updated_at->ne($p->created_at))
                                    <div>ویرایش: {{ \App\Helpers\Jalali::formatFa($p->updated_at, 'Y/m/d H:i') }}</div>
                                @endif
                                @if($p->sale_ends_at)
                                    <div>پایان حراج: {{ \App\Helpers\Jalali::formatFa($p->sale_ends_at, 'Y/m/d H:i') }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="admin-table-actions">
                                <a href="{{ route('admin.products.edit', $p) }}" class="btn btn-primary btn-sm">ویرایش</a>
                                <form method="post" action="{{ route('admin.products.destroy', $p) }}" style="display:inline;" onsubmit="return confirm('حذف شود؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-0">
                                <div class="admin-empty-state">
                                    <svg class="admin-empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <p class="admin-empty-state__title">محصولی ثبت نشده</p>
                                    <p class="admin-empty-state__text">با دکمهٔ «محصول جدید» اولین محصول را اضافه کنید.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(method_exists($products, 'links'))
        <div class="admin-pagination">{{ $products->links() }}</div>
    @endif
</div>
@endsection

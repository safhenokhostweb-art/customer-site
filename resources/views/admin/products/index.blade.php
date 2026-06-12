@extends('admin.layout')
@section('title', 'ظ…ط­طµظˆظ„ط§طھ')
@section('header', 'ظ…ط­طµظˆظ„ط§طھ')
@section('content')

{{-- Breadcrumb inline --}}
@php
    $crumbs = [
        ['label' => 'ط¯ط§ط´ط¨ظˆط±ط¯', 'url' => route('admin.dashboard')],
        ['label' => 'ظ…ط­طµظˆظ„ط§طھ', 'url' => null],
    ];
@endphp
@if(!empty($crumbs))
<nav class="admin-breadcrumb" aria-label="ظ…ط³غŒط± طµظپط­ظ‡">
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
                    <span class="admin-breadcrumb-sep" aria-hidden="true">â€؛</span>
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
            <h1 class="admin-page-title">ظ…ط­طµظˆظ„ط§طھ</h1>
            <p class="admin-section-lead" style="margin-top:0.35rem">ظپظ‡ط±ط³طھ ظ…ط­طµظˆظ„ط§طھ ط¨ط§ ظˆط¶ط¹غŒطھ ط§ظ†طھط´ط§ط±طŒ ظ…ظˆط¬ظˆط¯غŒ ظˆ ظ…ط³غŒط± ظˆغŒط±ط§غŒط´ ط³ط±غŒط¹.</p>
        </div>
        <div class="admin-page-tools">
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">ظ…ط­طµظˆظ„ ط¬ط¯غŒط¯</a>
        </div>
    </div>

    <div class="admin-kpi-grid">
        <div class="admin-kpi-card">
            <p class="admin-kpi-card__label">طھط¹ط¯ط§ط¯ ع©ظ„</p>
            <p class="admin-kpi-card__value">{{ method_exists($products, 'total') ? number_format($products->total()) : number_format($products->count()) }}</p>
        </div>
        <div class="admin-kpi-card">
            <p class="admin-kpi-card__label">ظ…ظ†طھط´ط± ط´ط¯ظ‡</p>
            <p class="admin-kpi-card__value">{{ number_format($products->where('status', \App\Models\Product::STATUS_PUBLISHED)->count()) }}</p>
        </div>
        <div class="admin-kpi-card">
            <p class="admin-kpi-card__label">ظ¾غŒط´â€Œظ†ظˆغŒط³</p>
            <p class="admin-kpi-card__value">{{ number_format($products->where('status', '!=', \App\Models\Product::STATUS_PUBLISHED)->count()) }}</p>
        </div>
    </div>

    <div class="card admin-table-wrap">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>ط¹ظ†ظˆط§ظ†</th><th>ظ‚غŒظ…طھ</th><th>ظ…ظˆط¬ظˆط¯غŒ</th><th>ظˆط¶ط¹غŒطھ</th><th>طھط§ط±غŒط®â€Œظ‡ط§ (ط´ظ…ط³غŒ)</th><th>ط¹ظ…ظ„غŒط§طھ</th></tr>
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
                                    <span class="text-xs text-slate-500">ظ†ط§ظ…ط­ط¯ظˆط¯</span>
                                @else
                                    {{ $p->stock }}
                                @endif
                            </td>
                            <td>
                                @if($p->status === \App\Models\Product::STATUS_PUBLISHED)
                                    <span class="badge badge-published">ظ…ظ†طھط´ط± ط´ط¯ظ‡</span>
                                @else
                                    <span class="badge badge-draft">ظ¾غŒط´â€Œظ†ظˆغŒط³</span>
                                @endif
                            </td>
                            <td class="text-xs text-slate-600">
                                <div>ط«ط¨طھ: {{ \App\Helpers\Jalali::formatFa($p->created_at, 'Y/m/d H:i') }}</div>
                                @if($p->updated_at && $p->updated_at->ne($p->created_at))
                                    <div>ظˆغŒط±ط§غŒط´: {{ \App\Helpers\Jalali::formatFa($p->updated_at, 'Y/m/d H:i') }}</div>
                                @endif
                                @if($p->sale_ends_at)
                                    <div>ظ¾ط§غŒط§ظ† ط­ط±ط§ط¬: {{ \App\Helpers\Jalali::formatFa($p->sale_ends_at, 'Y/m/d H:i') }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="admin-table-actions">
                                <a href="{{ route('admin.products.edit', $p) }}" class="btn btn-primary btn-sm">ظˆغŒط±ط§غŒط´</a>
                                <form method="post" action="{{ route('admin.products.destroy', $p) }}" style="display:inline;" onsubmit="return confirm('ط­ط°ظپ ط´ظˆط¯طں');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">ط­ط°ظپ</button>
                                </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-0">
                                <div class="admin-empty-state">
                                    <x-core-ui::icon name="package" class="admin-empty-state__icon" />
                                    <p class="admin-empty-state__title">ظ…ط­طµظˆظ„غŒ ط«ط¨طھ ظ†ط´ط¯ظ‡</p>
                                    <p class="admin-empty-state__text">ط¨ط§ ط¯ع©ظ…ظ‡ظ” آ«ظ…ط­طµظˆظ„ ط¬ط¯غŒط¯آ» ط§ظˆظ„غŒظ† ظ…ط­طµظˆظ„ ط±ط§ ط§ط¶ط§ظپظ‡ ع©ظ†غŒط¯.</p>
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



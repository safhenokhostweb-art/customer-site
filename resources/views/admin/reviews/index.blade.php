@extends('admin.layout')
@section('title', 'ظ†ط¸ط±ط§طھ ظˆ ط§ظ…طھغŒط§ط²ط¯ظ‡غŒ')
@section('header', 'ظ†ط¸ط±ط§طھ ظˆ ط§ظ…طھغŒط§ط²ط¯ظ‡غŒ')
@push('head')
<style>
.rev-stat{padding:.875rem 1.25rem;border-radius:.875rem;background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(15,23,42,.05)}
.rev-stat__label{font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.25rem}
.rev-stat__value{font-size:1.6rem;font-weight:800;color:#1e293b;line-height:1}

.stars{display:inline-flex;gap:1px;align-items:center}
.star{font-size:.875rem}
.star--on{color:#f59e0b}
.star--off{color:#e2e8f0}

.rev-badge{display:inline-flex;align-items:center;padding:.2rem .6rem;border-radius:9999px;font-size:.7rem;font-weight:700}
.rev-badge--approved{background:#dcfce7;color:#166534}
.rev-badge--pending{background:#fef9c3;color:#92400e}
</style>
@endpush
@section('content')

@include('admin.partials.breadcrumb', ['crumbs' => [
    ['label' => 'ط¯ط§ط´ط¨ظˆط±ط¯', 'url' => route('admin.dashboard')],
    ['label' => 'ظ†ط¸ط±ط§طھ', 'url' => null],
]])

@if(session('success'))
    <div class="alert alert-success mb-4">{{ session('success') }}</div>
@endif

<div class="admin-page">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">ظ†ط¸ط±ط§طھ ظˆ ط§ظ…طھغŒط§ط²ط¯ظ‡غŒ</h1>
            <p class="admin-section-lead" style="margin-top:.3rem">ظ…ط¯غŒط±غŒطھ ظˆ طھط£غŒغŒط¯ ظ†ط¸ط±ط§طھ ط«ط¨طھâ€Œط´ط¯ظ‡ ط¨ط±ط§غŒ ظ…ط·ط§ظ„ط¨ ظˆ ظ…ط­طµظˆظ„ط§طھ.</p>
        </div>
    </div>

    {{-- KPIs --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:1rem;margin-bottom:1.5rem">
        <div class="rev-stat">
            <div class="rev-stat__label">ع©ظ„ ظ†ط¸ط±ط§طھ</div>
            <div class="rev-stat__value">{{ \App\Helpers\NumberHelper::formatFa($stats['total']) }}</div>
        </div>
        <div class="rev-stat">
            <div class="rev-stat__label">ط¯ط± ط§ظ†طھط¸ط§ط±</div>
            <div class="rev-stat__value" style="color:#d97706">{{ \App\Helpers\NumberHelper::formatFa($stats['pending']) }}</div>
        </div>
        <div class="rev-stat">
            <div class="rev-stat__label">طھط§غŒغŒط¯ ط´ط¯ظ‡</div>
            <div class="rev-stat__value" style="color:#16a34a">{{ \App\Helpers\NumberHelper::formatFa($stats['approved']) }}</div>
        </div>
        <div class="rev-stat">
            <div class="rev-stat__label">ظ…غŒط§ظ†ع¯غŒظ† ط§ظ…طھغŒط§ط²</div>
            <div class="rev-stat__value" style="color:#f59e0b">{{ $stats['avg'] ? \App\Helpers\NumberHelper::formatFa($stats['avg']) : 'â€”' }}<span style="font-size:.9rem">/غµ</span></div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="get" action="{{ route('admin.reviews.index') }}" class="card" style="padding:.875rem 1.25rem;margin-bottom:1rem;display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end">
        <div>
            <label class="form-label" style="font-size:.75rem">ظˆط¶ط¹غŒطھ</label>
            <select name="approved" class="form-control" style="height:2.25rem;font-size:.85rem">
                <option value="">ظ‡ظ…ظ‡</option>
                <option value="1" {{ request('approved') === '1' ? 'selected' : '' }}>طھط§غŒغŒط¯ ط´ط¯ظ‡</option>
                <option value="0" {{ request('approved') === '0' ? 'selected' : '' }}>ط¯ط± ط§ظ†طھط¸ط§ط±</option>
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size:.75rem">ط§ظ…طھغŒط§ط²</label>
            <select name="rating" class="form-control" style="height:2.25rem;font-size:.85rem">
                <option value="">ظ‡ظ…ظ‡ ط§ظ…طھغŒط§ط²ظ‡ط§</option>
                @for($i=5;$i>=1;$i--)
                    <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>{{ $i }} ط³طھط§ط±ظ‡</option>
                @endfor
            </select>
        </div>
        <div style="display:flex;gap:.5rem">
            <button type="submit" class="btn btn-primary" style="height:2.25rem;padding:0 1rem;font-size:.85rem">ظپغŒظ„طھط±</button>
            @if(request()->hasAny(['approved','rating']))
                <a href="{{ route('admin.reviews.index') }}" class="btn btn-secondary" style="height:2.25rem;padding:0 .75rem;font-size:.85rem">ظ¾ط§ع©</a>
            @endif
        </div>
    </form>

    @if($stats['pending'] > 0)
        <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:.75rem;padding:.75rem 1rem;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;font-size:.85rem;color:#92400e;font-weight:600">
            <x-core-ui::icon name="alert-triangle" class="w-4 h-4" />
            {{ \App\Helpers\NumberHelper::formatFa($stats['pending']) }} ظ†ط¸ط± ط¯ط± ط§ظ†طھط¸ط§ط± طھط£غŒغŒط¯ ط¯ط§ط±غŒط¯.
            <a href="{{ route('admin.reviews.index', ['approved'=>'0']) }}" style="color:#6366f1;text-decoration:underline">ظ…ط´ط§ظ‡ط¯ظ‡</a>
        </div>
    @endif

    {{-- Table --}}
    <div class="card admin-table-wrap">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ظ…ظ†ط¨ط¹ / ع©ط§ط±ط¨ط±</th>
                        <th>ط§ظ…طھغŒط§ط²</th>
                        <th>ظ…طھظ† ظ†ط¸ط±</th>
                        <th>طھط§ط±غŒط®</th>
                        <th>ظˆط¶ط¹غŒطھ</th>
                        <th style="width:140px">ط¹ظ…ظ„غŒط§طھ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviews as $r)
                        <tr>
                            <td>
                                <div class="text-sm font-medium text-slate-700">
                                    {{ class_basename($r->reviewable_type) }}
                                    @if($r->reviewable)
                                        <span class="text-slate-400">#{{ $r->reviewable_id }}</span>
                                    @endif
                                </div>
                                @if($r->user)
                                    <div class="text-xs text-slate-500">{{ $r->user->name }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="stars">
                                    @for($i=1;$i<=5;$i++)
                                        <span class="star {{ $i <= $r->rating ? 'star--on' : 'star--off' }}">âک…</span>
                                    @endfor
                                </div>
                                <div class="text-xs text-slate-500 mt-1">{{ \App\Helpers\NumberHelper::formatFa($r->rating) }}/غµ</div>
                            </td>
                            <td style="max-width:260px">
                                <p class="text-sm text-slate-700" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                    {{ $r->body ? Str::limit($r->body, 80) : 'â€”' }}
                                </p>
                            </td>
                            <td class="text-xs text-slate-500">
                                {{ \App\Helpers\Jalali::formatFa($r->created_at,'Y/m/d H:i') }}
                            </td>
                            <td>
                                @if($r->is_approved)
                                    <span class="rev-badge rev-badge--approved">طھط§غŒغŒط¯ ط´ط¯ظ‡</span>
                                @else
                                    <span class="rev-badge rev-badge--pending">ط¯ط± ط§ظ†طھط¸ط§ط±</span>
                                @endif
                            </td>
                            <td>
                                <div class="admin-table-actions" style="flex-wrap:wrap">
                                    @if(!$r->is_approved)
                                        <form method="post" action="{{ route('admin.reviews.approve', $r) }}" style="display:inline">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm">âœ“ طھط§غŒغŒط¯</button>
                                        </form>
                                    @else
                                        <form method="post" action="{{ route('admin.reviews.reject', $r) }}" style="display:inline">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary btn-sm">ط±ط¯</button>
                                        </form>
                                    @endif
                                    <form method="post" action="{{ route('admin.reviews.destroy', $r) }}" style="display:inline"
                                          onsubmit="return confirm('ط§غŒظ† ظ†ط¸ط± ط­ط°ظپ ط´ظˆط¯طں');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">ط­ط°ظپ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-0">
                                <div class="admin-empty-state">
                                    <x-core-ui::icon name="message-circle" class="admin-empty-state__icon" />
                                    <p class="admin-empty-state__title">ظ†ط¸ط±غŒ غŒط§ظپطھ ظ†ط´ط¯</p>
                                    <p class="admin-empty-state__text">ظپغŒظ„طھط±ظ‡ط§ ط±ط§ طھط؛غŒغŒط± ط¯ظ‡غŒط¯ غŒط§ ظ…ظ†طھط¸ط± ط«ط¨طھ ظ†ط¸ط± ط¬ط¯غŒط¯ ط¨ط§ط´غŒط¯.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($reviews->hasPages())
        <div class="admin-pagination">{{ $reviews->links() }}</div>
    @endif
</div>
@endsection



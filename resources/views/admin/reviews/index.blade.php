@extends('admin.layout')
@section('title', 'نظرات و امتیازدهی')
@section('header', 'نظرات و امتیازدهی')
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
    ['label' => 'داشبورد', 'url' => route('admin.dashboard')],
    ['label' => 'نظرات', 'url' => null],
]])

@if(session('success'))
    <div class="alert alert-success mb-4">{{ session('success') }}</div>
@endif

<div class="admin-page">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">نظرات و امتیازدهی</h1>
            <p class="admin-section-lead" style="margin-top:.3rem">مدیریت و تأیید نظرات ثبت‌شده برای مطالب و محصولات.</p>
        </div>
    </div>

    {{-- KPIs --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:1rem;margin-bottom:1.5rem">
        <div class="rev-stat">
            <div class="rev-stat__label">کل نظرات</div>
            <div class="rev-stat__value">{{ \App\Helpers\NumberHelper::formatFa($stats['total']) }}</div>
        </div>
        <div class="rev-stat">
            <div class="rev-stat__label">در انتظار</div>
            <div class="rev-stat__value" style="color:#d97706">{{ \App\Helpers\NumberHelper::formatFa($stats['pending']) }}</div>
        </div>
        <div class="rev-stat">
            <div class="rev-stat__label">تایید شده</div>
            <div class="rev-stat__value" style="color:#16a34a">{{ \App\Helpers\NumberHelper::formatFa($stats['approved']) }}</div>
        </div>
        <div class="rev-stat">
            <div class="rev-stat__label">میانگین امتیاز</div>
            <div class="rev-stat__value" style="color:#f59e0b">{{ $stats['avg'] ? \App\Helpers\NumberHelper::formatFa($stats['avg']) : '—' }}<span style="font-size:.9rem">/۵</span></div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="get" action="{{ route('admin.reviews.index') }}" class="card" style="padding:.875rem 1.25rem;margin-bottom:1rem;display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end">
        <div>
            <label class="form-label" style="font-size:.75rem">وضعیت</label>
            <select name="approved" class="form-control" style="height:2.25rem;font-size:.85rem">
                <option value="">همه</option>
                <option value="1" {{ request('approved') === '1' ? 'selected' : '' }}>تایید شده</option>
                <option value="0" {{ request('approved') === '0' ? 'selected' : '' }}>در انتظار</option>
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size:.75rem">امتیاز</label>
            <select name="rating" class="form-control" style="height:2.25rem;font-size:.85rem">
                <option value="">همه امتیازها</option>
                @for($i=5;$i>=1;$i--)
                    <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>{{ $i }} ستاره</option>
                @endfor
            </select>
        </div>
        <div style="display:flex;gap:.5rem">
            <button type="submit" class="btn btn-primary" style="height:2.25rem;padding:0 1rem;font-size:.85rem">فیلتر</button>
            @if(request()->hasAny(['approved','rating']))
                <a href="{{ route('admin.reviews.index') }}" class="btn btn-secondary" style="height:2.25rem;padding:0 .75rem;font-size:.85rem">پاک</a>
            @endif
        </div>
    </form>

    @if($stats['pending'] > 0)
        <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:.75rem;padding:.75rem 1rem;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;font-size:.85rem;color:#92400e;font-weight:600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            {{ \App\Helpers\NumberHelper::formatFa($stats['pending']) }} نظر در انتظار تأیید دارید.
            <a href="{{ route('admin.reviews.index', ['approved'=>'0']) }}" style="color:#6366f1;text-decoration:underline">مشاهده</a>
        </div>
    @endif

    {{-- Table --}}
    <div class="card admin-table-wrap">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>منبع / کاربر</th>
                        <th>امتیاز</th>
                        <th>متن نظر</th>
                        <th>تاریخ</th>
                        <th>وضعیت</th>
                        <th style="width:140px">عملیات</th>
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
                                        <span class="star {{ $i <= $r->rating ? 'star--on' : 'star--off' }}">★</span>
                                    @endfor
                                </div>
                                <div class="text-xs text-slate-500 mt-1">{{ \App\Helpers\NumberHelper::formatFa($r->rating) }}/۵</div>
                            </td>
                            <td style="max-width:260px">
                                <p class="text-sm text-slate-700" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                    {{ $r->body ? Str::limit($r->body, 80) : '—' }}
                                </p>
                            </td>
                            <td class="text-xs text-slate-500">
                                {{ \App\Helpers\Jalali::formatFa($r->created_at,'Y/m/d H:i') }}
                            </td>
                            <td>
                                @if($r->is_approved)
                                    <span class="rev-badge rev-badge--approved">تایید شده</span>
                                @else
                                    <span class="rev-badge rev-badge--pending">در انتظار</span>
                                @endif
                            </td>
                            <td>
                                <div class="admin-table-actions" style="flex-wrap:wrap">
                                    @if(!$r->is_approved)
                                        <form method="post" action="{{ route('admin.reviews.approve', $r) }}" style="display:inline">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm">✓ تایید</button>
                                        </form>
                                    @else
                                        <form method="post" action="{{ route('admin.reviews.reject', $r) }}" style="display:inline">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary btn-sm">رد</button>
                                        </form>
                                    @endif
                                    <form method="post" action="{{ route('admin.reviews.destroy', $r) }}" style="display:inline"
                                          onsubmit="return confirm('این نظر حذف شود؟');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-0">
                                <div class="admin-empty-state">
                                    <svg class="admin-empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                    <p class="admin-empty-state__title">نظری یافت نشد</p>
                                    <p class="admin-empty-state__text">فیلترها را تغییر دهید یا منتظر ثبت نظر جدید باشید.</p>
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

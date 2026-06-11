@extends('admin.layout')

@section('title', 'پشتیبانی')

@push('head')
<style>
.ticket-stat-card{background:linear-gradient(135deg,rgba(255,255,255,0.85),rgba(255,255,255,0.65));backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,0.3);border-radius:1rem;padding:1.25rem;transition:all 0.3s ease;box-shadow:0 4px 16px rgba(15,23,42,0.05)}
.ticket-stat-card:hover{transform:translateY(-3px);box-shadow:0 12px 32px rgba(99,102,241,0.1)}
.ticket-stat-card__icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.25rem}
.glass-filter-bar{background:rgba(255,255,255,0.75);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.2);border-radius:1rem;padding:1rem 1.5rem;box-shadow:0 8px 24px rgba(15,23,42,0.04)}
.ticket-row{transition:all 0.2s ease}
.ticket-row:hover{background:rgba(99,102,241,0.03)}
.status-badge{display:inline-flex;align-items:center;gap:0.4rem;padding:0.3rem 0.85rem;border-radius:9999px;font-size:0.75rem;font-weight:600;white-space:nowrap;border:1px solid transparent;transition:all 0.2s ease}
.status-badge:hover{transform:scale(1.05)}
.priority-dot{display:inline-block;width:8px;height:8px;border-radius:50%;animation:pulse-priority 2s infinite}
@keyframes pulse-priority{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.5;transform:scale(1.3)}}
.btn-gradient{display:inline-flex;align-items:center;gap:0.5rem;padding:0.65rem 1.5rem;border-radius:0.75rem;font-weight:600;font-size:0.875rem;transition:all 0.25s ease;background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;border:none;cursor:pointer;box-shadow:0 4px 14px rgba(99,102,241,0.25)}
.btn-gradient:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(99,102,241,0.35)}
.btn-ghost{display:inline-flex;align-items:center;gap:0.5rem;padding:0.65rem 1.5rem;border-radius:0.75rem;font-weight:600;font-size:0.875rem;transition:all 0.2s ease;background:transparent;color:#64748b;border:1.5px solid #e2e8f0;cursor:pointer;text-decoration:none}
.btn-ghost:hover{background:#f8fafc;border-color:#cbd5e1;color:#475569}
.error-glass{background:rgba(254,242,242,0.85);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid rgba(239,68,68,0.2);border-radius:1rem;padding:1rem 1.25rem}
.glass-card-strong{background:rgba(255,255,255,0.82);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.3);border-radius:1.25rem;box-shadow:0 8px 32px rgba(15,23,42,0.06)}
</style>
@endpush

@section('content')

@if(session('success'))
<div class="glass-card-strong mb-6 p-4 border-r-4 border-r-green-400 flex items-center gap-3 rounded-2xl" x-data="{ show: true }" x-show="show" x-transition>
    <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span class="text-sm text-green-800 font-medium">{{ session('success') }}</span>
    <button @click="show = false" class="mr-auto text-green-400 hover:text-green-600 transition-colors">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
</div>
@endif

@if($error)
<div class="error-glass mb-6 flex items-start gap-3">
    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <p class="text-sm text-red-700 m-0">{{ $error }}</p>
</div>
@endif

{{-- وضعیت پشتیبانی --}}
@if($license && $license->license_key)
    @php
        $supportExpires = $license->support_expires_at;
        $supportStatus = !$supportExpires ? 'no_date' : ($supportExpires->isPast() ? 'expired' : (now()->diffInDays($supportExpires) <= 30 ? 'expiring_soon' : 'active'));
    @endphp
    <div class="glass-card-strong mb-6 p-4 flex items-center gap-3" style="{{ $supportStatus === 'expired' ? 'border-color:#fecaca;background:rgba(254,242,242,0.8)' : ($supportStatus === 'expiring_soon' ? 'border-color:#fde68a;background:rgba(255,251,235,0.8)' : 'border-color:#a7f3d0;background:rgba(236,253,245,0.8)') }}">
        <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md text-lg">🛡️</span>
        <div class="flex-1">
            <div class="font-bold text-gray-900 text-sm">وضعیت پشتیبانی</div>
            <div class="flex flex-wrap items-center gap-2 mt-1">
                @if($supportExpires)
                    <span class="text-xs text-gray-600">انقضا: {{ \App\Helpers\Jalali::formatFa($supportExpires, 'Y/m/d') }}</span>
                    @if($supportStatus === 'expired')
                        <span class="status-badge bg-red-50 text-red-700 border-red-200"><span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span> منقضی شده</span>
                        <a href="{{ route('platform-products.index') }}" class="text-xs text-indigo-600 font-medium">تمدید پشتیبانی</a>
                    @elseif($supportStatus === 'expiring_soon')
                        <span class="status-badge bg-amber-50 text-amber-700 border-amber-200"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> به‌زودی منقضی</span>
                        <a href="{{ route('platform-products.index') }}" class="text-xs text-indigo-600 font-medium">تمدید پشتیبانی</a>
                    @else
                        <span class="status-badge bg-green-50 text-green-700 border-green-200"><svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> فعال</span>
                    @endif
                @else
                    <span class="text-xs text-gray-500">تاریخ انقضا تعیین نشده</span>
                @endif
                @if($license->support_level ?? null)
                    <span class="text-xs text-gray-400">سطح: {{ $license->support_level }}</span>
                @endif
            </div>
        </div>
    </div>
@endif

{{-- کاشی‌های آمار --}}
@php
$openCount = $tickets->filter(fn($t) => ($t['status'] ?? '') === 'open')->count();
$inProgressCount = $tickets->filter(fn($t) => ($t['status'] ?? '') === 'in_progress')->count();
$closedCount = $tickets->filter(fn($t) => ($t['status'] ?? '') === 'closed')->count();
$totalCount = $tickets->count();
$breachCount = 0; // SLA data not available via API - kept as 0
@endphp

<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-8">
    <div class="ticket-stat-card"><div class="flex items-center gap-3"><div class="ticket-stat-card__icon bg-indigo-100 text-indigo-700">📫</div><div><div class="text-2xl font-bold text-gray-900">{{ \App\Helpers\NumberHelper::formatFa($totalCount) }}</div><div class="text-xs text-gray-500 mt-0.5">کل تیکت‌ها</div></div></div></div>
    <div class="ticket-stat-card"><div class="flex items-center gap-3"><div class="ticket-stat-card__icon bg-yellow-100 text-yellow-700">🟡</div><div><div class="text-2xl font-bold text-gray-900">{{ \App\Helpers\NumberHelper::formatFa($openCount) }}</div><div class="text-xs text-gray-500 mt-0.5">باز</div></div></div></div>
    <div class="ticket-stat-card"><div class="flex items-center gap-3"><div class="ticket-stat-card__icon bg-blue-100 text-blue-700">🔵</div><div><div class="text-2xl font-bold text-gray-900">{{ \App\Helpers\NumberHelper::formatFa($inProgressCount) }}</div><div class="text-xs text-gray-500 mt-0.5">در حال بررسی</div></div></div></div>
    <div class="ticket-stat-card"><div class="flex items-center gap-3"><div class="ticket-stat-card__icon bg-green-100 text-green-700">✅</div><div><div class="text-2xl font-bold text-gray-900">{{ \App\Helpers\NumberHelper::formatFa($closedCount) }}</div><div class="text-xs text-gray-500 mt-0.5">بسته شده</div></div></div></div>
    <div class="ticket-stat-card"><div class="flex items-center gap-3"><div class="ticket-stat-card__icon bg-gray-100 text-gray-600">⚠️</div><div><div class="text-2xl font-bold text-gray-900">{{ \App\Helpers\NumberHelper::formatFa($breachCount) }}</div><div class="text-xs text-gray-500 mt-0.5">SLA نقض شده</div></div></div></div>
</div>

{{-- Actions --}}
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <a href="{{ route('admin.support.create') }}" class="btn-gradient">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
        ثبت تیکت جدید
    </a>
    <a href="{{ route('admin.support.history') }}" class="btn-ghost">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        تاریخچه کامل
    </a>
</div>

{{-- Filter Bar --}}
<div class="glass-filter-bar mb-6" x-data="{ search: '', status: '', priority: '' }">
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
        <div class="relative flex-1 w-full sm:w-auto">
            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" x-model="search" placeholder="جستجوی تیکت‌ها..." class="w-full pr-10 pl-4 py-2.5 rounded-xl border border-gray-200 bg-white/70 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all">
        </div>
        <select x-model="status" class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white/70 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all w-full sm:w-auto">
            <option value="">همه وضعیت‌ها</option>
            <option value="open">باز</option>
            <option value="in_progress">در حال بررسی</option>
            <option value="closed">بسته</option>
        </select>
        <select x-model="priority" class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white/70 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all w-full sm:w-auto">
            <option value="">همه اولویت‌ها</option>
            <option value="high">بالا</option>
            <option value="normal">عادی</option>
            <option value="low">کم</option>
        </select>
    </div>
</div>

{{-- Tickets Table --}}
<div class="glass-card-strong overflow-hidden rounded-2xl">
<div class="overflow-x-auto scrollbar-thin">
<table class="w-full text-right border-collapse">
<thead><tr><th class="p-3 pr-5">شماره</th><th class="p-3">موضوع</th><th class="p-3">وضعیت</th><th class="p-3">اولویت</th><th class="p-3">تاریخ</th><th class="p-3 pl-5">عملیات</th></tr></thead>
<tbody>
@forelse($tickets as $t)
<tr class="ticket-row border-b border-gray-100" x-show="
    (!search || '{{ $t['subject'] }}'.includes(search) || '{{ $t['ticket_number'] ?? '' }}'.includes(search)) &&
    (!status || '{{ $t['status'] }}' === status) &&
    (!priority || '{{ $t['priority'] ?? 'normal' }}' === priority)
">
    <td class="p-3 pr-5"><span class="font-mono font-bold text-indigo-700 text-xs bg-indigo-50 px-2 py-1 rounded-lg">{{ $t['ticket_number'] ?? '—' }}</span></td>
    <td class="p-3"><span class="text-sm font-medium text-gray-800">{{ Str::limit($t['subject'], 50) }}</span></td>
    <td class="p-3">
        @if(($t['status'] ?? '') === 'open')
        <span class="status-badge bg-yellow-50 text-yellow-700 border-yellow-200"><span class="w-1.5 h-1.5 rounded-full bg-yellow-500 animate-pulse"></span> باز</span>
        @elseif(($t['status'] ?? '') === 'in_progress')
        <span class="status-badge bg-blue-50 text-blue-700 border-blue-200"><span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span> در حال بررسی</span>
        @else
        <span class="status-badge bg-gray-50 text-gray-600 border-gray-200"><svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> بسته</span>
        @endif
    </td>
    <td class="p-3">
        @if(($t['priority'] ?? 'normal') === 'high')
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200"><span class="priority-dot bg-red-500"></span> بالا</span>
        @elseif(($t['priority'] ?? 'normal') === 'low')
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-50 text-gray-500 border border-gray-200"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> کم</span>
        @else
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-600 border border-blue-200"><span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span> عادی</span>
        @endif
    </td>
    <td class="p-3 text-sm text-gray-500">{{ \App\Helpers\Jalali::formatFa(\Carbon\Carbon::parse($t['created_at']), 'Y/m/d H:i') }}</td>
    <td class="p-3 pl-5">
        <a href="{{ route('admin.support.show', $t['id']) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 transition-all hover:shadow-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            مشاهده
        </a>
    </td>
</tr>
@empty
<tr><td colspan="6" class="text-center py-12"><div class="flex flex-col items-center gap-3"><span class="text-5xl">📭</span><span class="text-gray-400 font-medium">هیچ تیکتی یافت نشد</span><p class="text-gray-400 text-sm">هنوز تیکتی ثبت نشده است.</p><a href="{{ route('admin.support.create') }}" class="btn-gradient !text-sm !py-2 !px-4 mt-2"><svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg> ثبت تیکت جدید</a></div></td></tr>
@endforelse
</tbody>
</table>
</div>
</div>

{{-- Pagination --}}
@if(method_exists($tickets, 'links'))
<div class="mt-6 flex justify-center">
{{ $tickets->links() }}
</div>
@endif

<x-module-features module="support" />
@include('admin.support.guide')
@endsection

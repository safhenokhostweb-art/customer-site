@extends('admin.layout')
@section('title', 'پنل کاربران')

@push('head')
@include('partials.ticket-styles')
<style>
.user-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    padding: 1.25rem;
    transition: all .2s;
    position: relative;
    overflow: hidden;
}
.user-card:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,.08);
    transform: translateY(-2px);
    border-color: #c7d2fe;
}
.user-card__accent {
    position: absolute; top: 0; left: 0;
    width: 4px; height: 100%;
    border-radius: 1rem 0 0 1rem;
}
.user-avatar {
    width: 3rem; height: 3rem; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.125rem; font-weight: 800; color: white;
    flex-shrink: 0;
}
.active-badge {
    display: inline-flex; align-items: center; gap: .25rem;
    padding: .2rem .55rem; border-radius: 9999px;
    font-size: .65rem; font-weight: 700;
    background: #dcfce7; color: #166534;
    border: 1px solid #bbf7d0;
}
.no-chat-badge {
    display: inline-flex; align-items: center; gap: .25rem;
    padding: .2rem .55rem; border-radius: 9999px;
    font-size: .65rem; font-weight: 600;
    background: #f3f4f6; color: #6b7280;
    border: 1px solid #e5e7eb;
}
</style>
@endpush

@section('content')
<div x-data="{ search: '{{ request('q') }}' }">

{{-- Flash --}}
@if(session('success'))
<div class="flex items-center gap-3 bg-green-50 border border-green-200 rounded-2xl px-5 py-3.5 mb-5"
     x-data="{ show: true }" x-show="show" x-transition.duration.300ms>
    <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span class="text-sm text-green-700 font-medium">{{ session('success') }}</span>
    <button @click="show=false" class="mr-auto text-green-500 hover:text-green-700"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
</div>
@endif

{{-- Header --}}
<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h1 class="text-xl font-bold text-gray-900">پنل کاربران</h1>
        <p class="text-sm text-gray-500 mt-0.5">مدیریت کاربران سایت و چت مستقیم با آن‌ها</p>
    </div>
    <a href="{{ route('admin.user-tickets.index') }}"
       class="btn-ghost text-xs">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        صندوق تیکت‌ها
    </a>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="tkt-stat">
        <div class="tkt-stat__accent" style="background:linear-gradient(180deg,#6366f1,#4f46e5)"></div>
        <div class="flex items-center gap-3">
            <div class="tkt-stat__icon" style="background:#eef2ff">
                <span>👤</span>
            </div>
            <div>
                <div class="tkt-stat__val text-indigo-600">{{ $totalUsers }}</div>
                <div class="tkt-stat__label text-gray-500">کل کاربران</div>
            </div>
        </div>
    </div>
    <div class="tkt-stat">
        <div class="tkt-stat__accent" style="background:linear-gradient(180deg,#10b981,#059669)"></div>
        <div class="flex items-center gap-3">
            <div class="tkt-stat__icon" style="background:#ecfdf5">
                <span>💬</span>
            </div>
            <div>
                <div class="tkt-stat__val text-emerald-600">{{ $usersWithActive }}</div>
                <div class="tkt-stat__label text-gray-500">چت فعال</div>
            </div>
        </div>
    </div>
    <div class="tkt-stat">
        <div class="tkt-stat__accent" style="background:linear-gradient(180deg,#f59e0b,#d97706)"></div>
        <div class="flex items-center gap-3">
            <div class="tkt-stat__icon" style="background:#fffbeb">
                <span>📋</span>
            </div>
            <div>
                <div class="tkt-stat__val text-amber-600">{{ $users->total() }}</div>
                <div class="tkt-stat__label text-gray-500">نتیجه جستجو</div>
            </div>
        </div>
    </div>
    <div class="tkt-stat">
        <div class="tkt-stat__accent" style="background:linear-gradient(180deg,#8b5cf6,#7c3aed)"></div>
        <div class="flex items-center gap-3">
            <div class="tkt-stat__icon" style="background:#f5f3ff">
                <span>🕐</span>
            </div>
            <div>
                <div class="tkt-stat__val text-violet-600">{{ $totalUsers > 0 ? round(($usersWithActive / $totalUsers) * 100) : 0 }}%</div>
                <div class="tkt-stat__label text-gray-500">نرخ تعامل</div>
            </div>
        </div>
    </div>
</div>

{{-- Search --}}
<div class="glass-filter-bar mb-6">
    <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center gap-3">
        <div class="flex-1 relative">
            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="جستجو بر اساس نام، ایمیل یا موبایل..."
                   class="tkt-form-input pr-9 text-sm">
        </div>
        <button type="submit" class="btn-gradient text-xs px-4 py-2">
            جستجو
        </button>
        @if(request('q'))
        <a href="{{ route('admin.users.index') }}" class="btn-ghost text-xs">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            پاک‌سازی
        </a>
        @endif
    </form>
</div>

{{-- User Grid --}}
@if($users->isEmpty())
<div class="glass-card text-center py-20">
    <div class="text-5xl mb-4">👥</div>
    <h3 class="text-base font-semibold text-gray-700 mb-1">کاربری یافت نشد</h3>
    <p class="text-sm text-gray-400">{{ request('q') ? 'با این عبارت جستجو نتیجه‌ای وجود ندارد.' : 'هنوز کاربری در سیستم ثبت‌نام نکرده است.' }}</p>
</div>
@else
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-6">
    @foreach($users as $u)
    @php
        $stats = $ticketStats[$u->id] ?? null;
        $activeCount = $stats ? (int)$stats->active_count : 0;
        $totalTickets = $stats ? (int)$stats->total : 0;
        $avatarColors = ['#4f46e5','#0891b2','#059669','#d97706','#dc2626','#7c3aed','#db2777'];
        $color = $avatarColors[crc32($u->name ?? $u->mobile ?? '') % count($avatarColors)];
        $initial = mb_strtoupper(mb_substr($u->name ?? '؟', 0, 1));
    @endphp
    <div class="user-card">
        <div class="user-card__accent" style="background: {{ $activeCount > 0 ? 'linear-gradient(180deg,#10b981,#059669)' : 'linear-gradient(180deg,#d1d5db,#9ca3af)' }}"></div>
        <div class="flex items-start gap-3 mb-3">
            @if($u->avatar)
            <img src="{{ $u->avatar_url }}" class="user-avatar object-cover" alt="{{ $u->name }}">
            @else
            <div class="user-avatar" style="background:{{ $color }}">{{ $initial }}</div>
            @endif
            <div class="flex-1 min-w-0">
                <h3 class="font-bold text-sm text-gray-900 truncate">{{ $u->name ?? '—' }}</h3>
                <p class="text-xs text-gray-500 truncate">{{ $u->mobile ?? '—' }}</p>
            </div>
            @if($activeCount > 0)
            <span class="active-badge shrink-0">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                فعال
            </span>
            @else
            <span class="no-chat-badge shrink-0">بدون چت</span>
            @endif
        </div>

        <div class="flex items-center justify-between text-xs text-gray-400 mb-3 pt-2 border-t border-gray-50">
            <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                {{ $totalTickets }} مکالمه
            </span>
            @if($activeCount > 0)
            <span class="text-emerald-600 font-semibold">{{ $activeCount }} باز</span>
            @endif
            <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                {{ $u->created_at->diffForHumans() }}
            </span>
        </div>

        <a href="{{ route('admin.users.chat', $u->id) }}"
           class="w-full flex items-center justify-center gap-2 py-2 rounded-xl text-xs font-semibold transition-all
                  {{ $activeCount > 0 ? 'bg-gradient-to-r from-indigo-500 to-violet-500 text-white shadow-sm hover:shadow-indigo-200 hover:shadow-md' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            {{ $activeCount > 0 ? 'ادامه چت' : 'شروع چت' }}
        </a>
    </div>
    @endforeach
</div>

{{-- Pagination --}}
@if($users->hasPages())
<div class="flex justify-center mt-4">
    {{ $users->links() }}
</div>
@endif
@endif

</div>
@endsection

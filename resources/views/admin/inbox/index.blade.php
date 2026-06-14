@extends('admin.layout')

@section('title', 'مرکز اعلان‌ها')
@section('header', 'مرکز اعلان‌ها')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6">

    {{-- Header row --}}
    <div class="flex items-center justify-between mb-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <div>
                <h2 class="text-base font-bold text-slate-800">مرکز اعلان‌ها</h2>
                @if($unreadCount > 0)
                <p class="text-xs text-slate-500">{{ $unreadCount }} اعلان خوانده‌نشده</p>
                @else
                <p class="text-xs text-slate-400">همه اعلان‌ها خوانده شده‌اند</p>
                @endif
            </div>
        </div>
        @if($unreadCount > 0)
        <form method="POST" action="{{ route('admin.system-alerts.read-all') }}">
            @csrf
            <button type="submit"
                    class="flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                همه را خواندم
            </button>
        </form>
        @endif
    </div>

    {{-- Filter legend --}}
    <div class="flex gap-2 mb-4 flex-wrap">
        @foreach([['order','سفارش','bg-emerald-100 text-emerald-700','🛒'],['ticket','تیکت','bg-blue-100 text-blue-700','💬'],['subscription','اشتراک','bg-violet-100 text-violet-700','⭐'],['system','سیستم','bg-gray-100 text-gray-600','🔔']] as [$t,$l,$cls,$emoji])
        <span class="flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $cls }}">{{ $emoji }} {{ $l }}</span>
        @endforeach
    </div>

    {{-- Notification list --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden divide-y divide-slate-100">
        @forelse($notifications as $n)
        <div class="flex items-start gap-3 px-4 py-3 {{ $n->is_read ? '' : 'bg-indigo-50/40' }} hover:bg-slate-50 transition group">
            {{-- Type icon --}}
            <div class="mt-0.5 w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center text-base
                        {{ $n->type === 'order' ? 'bg-emerald-100' : ($n->type === 'ticket' ? 'bg-blue-100' : ($n->type === 'subscription' ? 'bg-violet-100' : 'bg-gray-100')) }}">
                {{ $n->type === 'order' ? '🛒' : ($n->type === 'ticket' ? '💬' : ($n->type === 'subscription' ? '⭐' : '🔔')) }}
            </div>

            {{-- Content --}}
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    @if(! $n->is_read)
                    <span class="w-2 h-2 rounded-full bg-indigo-500 flex-shrink-0"></span>
                    @endif
                    <p class="text-sm font-semibold text-slate-800 truncate">{{ $n->title }}</p>
                </div>
                @if($n->body)
                <p class="text-xs text-slate-500 mt-0.5 truncate">{{ $n->body }}</p>
                @endif
                <p class="text-[11px] text-slate-400 mt-1">{{ $n->created_at->diffForHumans() }}</p>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition">
                @if($n->link)
                <a href="{{ $n->link }}"
                   class="text-[11px] text-indigo-600 hover:underline whitespace-nowrap">مشاهده</a>
                @endif
                @if(! $n->is_read)
                <form method="POST" action="{{ route('admin.system-alerts.mark-read', $n) }}">
                    @csrf
                    <button type="submit" class="text-[11px] text-slate-500 hover:text-slate-800 whitespace-nowrap">✓ خواندم</button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="py-16 text-center">
            <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-slate-100 flex items-center justify-center text-3xl">🔔</div>
            <p class="text-sm text-slate-500">هیچ اعلانی وجود ندارد.</p>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($notifications->hasPages())
    <div class="mt-4">{{ $notifications->links() }}</div>
    @endif

</div>
@endsection

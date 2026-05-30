@extends('account.layout')
@section('title', 'پشتیبانی')
@section('header', 'پشتیبانی و تیکت‌ها')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <div class="account-card p-4 text-center">
        <div class="w-10 h-10 mx-auto mb-2 rounded-xl bg-slate-100 flex items-center justify-center">
            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <div class="text-2xl font-bold text-slate-800">{{ $stats['total'] }}</div>
        <div class="text-xs text-slate-500 mt-0.5">کل تیکت‌ها</div>
    </div>
    <div class="account-card p-4 text-center" style="background:linear-gradient(135deg,#fef3c7,#fde68a);border-color:#fde68a;">
        <div class="w-10 h-10 mx-auto mb-2 rounded-xl bg-amber-100 flex items-center justify-center">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="text-2xl font-bold text-amber-700">{{ $stats['open'] }}</div>
        <div class="text-xs text-amber-600 mt-0.5">منتظر بررسی</div>
    </div>
    <div class="account-card p-4 text-center" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe);border-color:#bfdbfe;">
        <div class="w-10 h-10 mx-auto mb-2 rounded-xl bg-blue-100 flex items-center justify-center">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <div class="text-2xl font-bold text-blue-700">{{ $stats['in_progress'] }}</div>
        <div class="text-xs text-blue-600 mt-0.5">در حال بررسی</div>
    </div>
    <div class="account-card p-4 text-center" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);border-color:#bbf7d0;">
        <div class="w-10 h-10 mx-auto mb-2 rounded-xl bg-emerald-100 flex items-center justify-center">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="text-2xl font-bold text-emerald-700">{{ $stats['closed'] }}</div>
        <div class="text-xs text-emerald-600 mt-0.5">پاسخ داده شده</div>
    </div>
</div>

{{-- Ticket List --}}
<div class="account-card">
    <div class="account-card-header flex items-center justify-between">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            <span>تیکت‌های من</span>
        </div>
        <a href="{{ route('account.tickets.create') }}" class="btn btn-primary btn-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            تیکت جدید
        </a>
    </div>

    @if($tickets->isEmpty())
        <div class="account-card-body text-center py-16">
            <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-slate-100 flex items-center justify-center">
                <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <h3 class="font-semibold text-slate-600 mb-1">هنوز تیکتی ثبت نکرده‌اید</h3>
            <p class="text-sm text-slate-400 mb-5">سوال یا مشکلی دارید؟ یک تیکت پشتیبانی ثبت کنید.</p>
            <a href="{{ route('account.tickets.create') }}" class="btn btn-primary">ثبت اولین تیکت</a>
        </div>
    @else
        <div class="divide-y divide-slate-100">
            @foreach($tickets as $ticket)
                <a href="{{ route('account.tickets.show', $ticket->id) }}"
                   class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50/80 transition-colors group">

                    {{-- Status indicator --}}
                    <div class="shrink-0 pt-0.5">
                        @if($ticket->status === 'open')
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
                            </span>
                        @elseif($ticket->status === 'in_progress')
                            <span class="h-2.5 w-2.5 rounded-full bg-blue-500 block"></span>
                        @else
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-400 block"></span>
                        @endif
                    </div>

                    {{-- Main content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                            <span class="font-semibold text-slate-800 group-hover:text-indigo-700 transition-colors truncate text-sm">
                                {{ $ticket->subject }}
                            </span>
                            @if($ticket->priority === 'high')
                                <span class="badge badge-danger text-[10px] shrink-0">فوری</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 text-xs text-slate-400 flex-wrap">
                            <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-[10px]">{{ $ticket->ticket_number }}</span>
                            @if($ticket->category)
                                <span>· {{ ['technical'=>'فنی','billing'=>'مالی','product'=>'محصول','other'=>'سایر'][$ticket->category] ?? $ticket->category }}</span>
                            @endif
                            <span>· {{ \App\Helpers\Jalali::formatFa($ticket->created_at, 'Y/m/d') }}</span>
                        </div>
                    </div>

                    {{-- Status badge + arrow --}}
                    <div class="shrink-0 flex items-center gap-2">
                        @if($ticket->status === 'open')
                            <span class="badge badge-warning hidden sm:inline-flex">باز</span>
                        @elseif($ticket->status === 'in_progress')
                            <span class="badge badge-info hidden sm:inline-flex">در بررسی</span>
                        @else
                            <span class="badge badge-success hidden sm:inline-flex">بسته</span>
                        @endif
                        <svg class="w-4 h-4 text-slate-300 group-hover:text-indigo-400 transition-colors -scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
            @endforeach
        </div>
        @if($tickets->hasPages())
            <div class="px-5 py-4 border-t border-slate-100">{{ $tickets->links() }}</div>
        @endif
    @endif
</div>
@endsection

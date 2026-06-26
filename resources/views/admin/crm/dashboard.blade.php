@extends('admin.layout')
@section('title', 'CRM — مدیریت ارتباط با مشتری')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">CRM — مدیریت ارتباط با مشتری</h1>
            <p class="text-slate-500 text-sm mt-0.5">مخاطبین، معاملات، و فعالیت‌های فروش در یک نگاه</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.crm.contacts.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                مخاطب جدید
            </a>
            <a href="{{ route('admin.crm.deals.pipeline') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                پایپ‌لاین فروش
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-medium">+{{ num_fa($newThisMonth) }} این ماه</span>
            </div>
            <div class="text-2xl font-bold text-slate-800">@numFa($totalContacts)</div>
            <div class="text-xs text-slate-500 mt-0.5">کل مخاطبین</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full bg-violet-50 text-violet-700 font-medium">{{ num_fa($activeDeals) }} فعال</span>
            </div>
            <div class="text-2xl font-bold text-slate-800">@numFa($pipelineValue)</div>
            <div class="text-xs text-slate-500 mt-0.5">ارزش پایپ‌لاین (تومان)</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-medium">این ماه</span>
            </div>
            <div class="text-2xl font-bold text-slate-800">@numFa($wonThisMonth)</div>
            <div class="text-xs text-slate-500 mt-0.5">معامله برنده شده</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 font-medium">این ماه</span>
            </div>
            <div class="text-2xl font-bold text-slate-800">@numFa($wonValue)</div>
            <div class="text-xs text-slate-500 mt-0.5">ارزش معاملات برنده (تومان)</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Contact Status Breakdown --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <h2 class="text-base font-semibold text-slate-800 mb-4">وضعیت مخاطبین</h2>
            @foreach([['lead','سرنخ','blue'],['prospect','بالقوه','amber'],['customer','مشتری','emerald'],['churned','از دست رفته','red']] as [$key,$label,$color])
            @php $cnt = $statusBreakdown[$key]->count ?? 0; @endphp
            <div class="flex items-center gap-3 mb-3">
                <span class="w-24 text-sm text-slate-600 shrink-0">{{ $label }}</span>
                <div class="flex-1 bg-slate-100 rounded-full h-2">
                    @if($totalContacts > 0)
                    <div class="h-2 rounded-full bg-{{ $color }}-500 transition-all" style="width: {{ round($cnt / $totalContacts * 100) }}%"></div>
                    @endif
                </div>
                <span class="text-sm font-semibold text-slate-700 w-8 text-left">{{ $cnt }}</span>
            </div>
            @endforeach
        </div>

        {{-- Pipeline Stage Breakdown --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <h2 class="text-base font-semibold text-slate-800 mb-4">پایپ‌لاین فروش</h2>
            @foreach([['lead','سرنخ'],['contacted','تماس گرفته'],['proposal','پیشنهاد'],['negotiation','مذاکره']] as [$stage,$label])
            @php $s = $stageBreakdown[$stage] ?? null; $cnt = $s->count ?? 0; $val = $s->total_value ?? 0; @endphp
            <div class="flex items-center justify-between py-2 border-b border-slate-100 last:border-0">
                <span class="text-sm text-slate-700">{{ $label }}</span>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-medium text-slate-800">{{ $cnt }} معامله</span>
                    <span class="text-xs text-slate-500">{{ num_fa($val) }} ت</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Recent Activities --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold text-slate-800">آخرین فعالیت‌ها</h2>
            <a href="{{ route('admin.crm.contacts.index') }}" class="text-sm text-indigo-600 hover:underline">همه مخاطبین ←</a>
        </div>
        @forelse($recentActivities as $activity)
        <div class="flex items-start gap-3 py-3 border-b border-slate-100 last:border-0">
            <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    @if($activity->type === 'call')
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    @elseif($activity->type === 'meeting')
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    @else
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    @endif
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-800">{{ $activity->subject }}</p>
                <p class="text-xs text-slate-500">
                    {{ \App\Models\CrmActivity::typeLabel($activity->type) }}
                    @if($activity->contact) · {{ $activity->contact->name }} @endif
                    @if($activity->deal) · {{ $activity->deal->title }} @endif
                </p>
            </div>
            <span class="text-xs text-slate-400 shrink-0">{{ \App\Helpers\Jalali::diffForHumansFa($activity->created_at) }}</span>
        </div>
        @empty
        <p class="text-slate-400 text-sm py-4 text-center">هنوز فعالیتی ثبت نشده است.</p>
        @endforelse
    </div>

</div>
@endsection

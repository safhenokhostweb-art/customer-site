@extends('admin.layouts.app')
@section('title', $campaign->name)

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div class="flex items-start gap-3">
            <a href="{{ route('admin.sms.campaigns.index') }}" class="p-2 text-gray-400 hover:text-gray-600 mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $campaign->name }}</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $campaign->statusColor() }}-100 text-{{ $campaign->statusColor() }}-700">
                        {{ $campaign->statusLabel() }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">{{ $campaign->audienceLabel() }} · {{ $campaign->smsCount() }} پیامک · ایجاد شده {{ \App\Helpers\Jalali::format($campaign->created_at, 'Y/m/d') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($campaign->isDraft())
            <a href="{{ route('admin.sms.campaigns.edit', $campaign) }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                ویرایش
            </a>
            <form method="POST" action="{{ route('admin.sms.campaigns.send', $campaign) }}">
                @csrf
                <button type="submit" onclick="return confirm('آیا از ارسال کمپین اطمینان دارید؟')"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    ارسال کمپین
                </button>
            </form>
            @endif

            @if($campaign->isRunning())
            <form method="POST" action="{{ route('admin.sms.campaigns.cancel', $campaign) }}">
                @csrf
                <button type="submit" onclick="return confirm('آیا از لغو کمپین اطمینان دارید؟')"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    لغو کمپین
                </button>
            </form>
            @endif

            <form method="POST" action="{{ route('admin.sms.campaigns.duplicate', $campaign) }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-sm text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    کپی
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach([
            ['label'=>'کل مخاطبین', 'value'=>num_fa($stats['total']), 'color'=>'indigo'],
            ['label'=>'ارسال موفق', 'value'=>num_fa($stats['sent']), 'color'=>'emerald'],
            ['label'=>'ارسال ناموفق', 'value'=>num_fa($stats['failed']), 'color'=>'red'],
            ['label'=>'نرخ تحویل', 'value'=>$stats['delivery'].'%', 'color'=>'blue'],
        ] as $stat)
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
            <p class="text-3xl font-bold text-{{ $stat['color'] }}-600">{{ $stat['value'] }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $stat['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Progress bar --}}
    @if($campaign->total_recipients > 0)
    <div class="bg-white rounded-xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">پیشرفت ارسال</span>
            <span class="text-sm text-gray-500">{{ num_fa($campaign->sent_count) }} از {{ num_fa($campaign->total_recipients) }}</span>
        </div>
        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-2 bg-emerald-500 rounded-full transition-all" style="width: {{ $campaign->deliveryRate() }}%"></div>
        </div>
        @if($campaign->isRunning())
        <p class="text-xs text-amber-600 mt-2 flex items-center gap-1">
            <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            کمپین در حال ارسال است...
        </p>
        @endif
    </div>
    @endif

    <div class="grid md:grid-cols-2 gap-6">
        {{-- Message preview --}}
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                متن پیامک
            </h2>
            <div class="bg-gray-50 rounded-lg p-4 text-sm leading-relaxed whitespace-pre-wrap">{{ $campaign->message }}</div>
            <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
                <span>{{ mb_strlen($campaign->message) }} کاراکتر</span>
                <span>{{ $campaign->smsCount() }} پیامک</span>
                <span>{{ $campaign->audienceLabel() }}</span>
            </div>
        </div>

        {{-- Timeline --}}
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                تاریخچه زمانی
            </h2>
            <div class="space-y-3 text-sm">
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-gray-300 shrink-0"></div>
                    <span class="text-gray-500">ایجاد شده:</span>
                    <span class="text-gray-800">{{ \App\Helpers\Jalali::format($campaign->created_at, 'Y/m/d H:i') }}</span>
                </div>
                @if($campaign->started_at)
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></div>
                    <span class="text-gray-500">شروع ارسال:</span>
                    <span class="text-gray-800">{{ \App\Helpers\Jalali::format($campaign->started_at, 'Y/m/d H:i') }}</span>
                </div>
                @endif
                @if($campaign->completed_at)
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></div>
                    <span class="text-gray-500">پایان ارسال:</span>
                    <span class="text-gray-800">{{ \App\Helpers\Jalali::format($campaign->completed_at, 'Y/m/d H:i') }}</span>
                </div>
                @endif
                @if($campaign->scheduled_at)
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-blue-400 shrink-0"></div>
                    <span class="text-gray-500">زمان‌بندی:</span>
                    <span class="text-gray-800">{{ \App\Helpers\Jalali::format($campaign->scheduled_at, 'Y/m/d H:i') }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Recipients table --}}
    @if($recipients->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">لیست مخاطبین</h2>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500">{{ num_fa($campaign->total_recipients) }} نفر</span>
                {{-- GAP-L03: CSV export button --}}
                <a href="{{ route('admin.sms.campaigns.export-csv', $campaign) }}"
                   class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-emerald-300 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    دانلود CSV
                </a>
            </div>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">شماره</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">نام</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">وضعیت</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">زمان ارسال</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($recipients as $r)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2.5 font-mono text-gray-700">{{ $r->mobile }}</td>
                    <td class="px-4 py-2.5 text-gray-600">{{ $r->name ?? '—' }}</td>
                    <td class="px-4 py-2.5">
                        @if($r->status === 'sent')
                        <span class="inline-flex items-center gap-1 text-xs text-emerald-700">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            ارسال شد
                        </span>
                        @elseif($r->status === 'failed')
                        <span class="inline-flex items-center gap-1 text-xs text-red-600">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            ناموفق
                        </span>
                        @else
                        <span class="text-xs text-gray-400">در انتظار</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-xs text-gray-500">
                        {{ $r->sent_at ? \App\Helpers\Jalali::format($r->sent_at, 'Y/m/d H:i') : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $recipients->links() }}
        </div>
    </div>
    @endif

    {{-- Delete (draft only) --}}
    @if($campaign->isDraft())
    <div class="pt-2">
        <form method="POST" action="{{ route('admin.sms.campaigns.destroy', $campaign) }}" onsubmit="return confirm('آیا از حذف کمپین اطمینان دارید؟')">
            @csrf @method('DELETE')
            <button type="submit" class="text-sm text-red-500 hover:text-red-700">حذف این کمپین</button>
        </form>
    </div>
    @endif

</div>
@endsection

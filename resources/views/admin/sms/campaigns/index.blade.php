@extends('admin.layouts.app')
@section('title', 'کمپین‌های پیامکی')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">کمپین‌های پیامکی</h1>
            <p class="text-sm text-gray-500 mt-1">ارسال پیامک گروهی به مشتریان و مخاطبین</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.sms.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                تنظیمات پیامک
            </a>
            <a href="{{ route('admin.sms.campaigns.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                کمپین جدید
            </a>
        </div>
    </div>

    @if(!$smsConfigured)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <div>
            <p class="font-medium text-amber-800">تنظیمات پیامک ناقص است</p>
            <p class="text-sm text-amber-700 mt-1">برای ارسال کمپین ابتدا <a href="{{ route('admin.sms.index') }}" class="underline font-medium">تنظیمات پیامک</a> را تکمیل کنید.</p>
        </div>
    </div>
    @endif

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach([
            ['label'=>'کل کمپین‌ها', 'value'=>$stats['total'], 'icon'=>'M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l-3-3m3 3l3-3', 'color'=>'indigo'],
            ['label'=>'پیش‌نویس', 'value'=>$stats['draft'], 'icon'=>'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', 'color'=>'slate'],
            ['label'=>'در حال ارسال', 'value'=>$stats['running'], 'icon'=>'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color'=>'amber'],
            ['label'=>'ارسال شده', 'value'=>$stats['completed'], 'icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color'=>'emerald'],
        ] as $card)
        <div class="bg-white rounded-xl border border-gray-100 p-4 flex items-center gap-4">
            <div class="p-2.5 bg-{{ $card['color'] }}-50 rounded-lg">
                <svg class="w-5 h-5 text-{{ $card['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($card['value']) }}</p>
                <p class="text-xs text-gray-500">{{ $card['label'] }}</p>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filter --}}
    <form method="GET" class="flex items-center gap-3">
        <select name="status" onchange="this.form.submit()" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            <option value="">همه وضعیت‌ها</option>
            @foreach(['draft'=>'پیش‌نویس','scheduled'=>'زمان‌بندی شده','running'=>'در حال ارسال','completed'=>'ارسال شده','failed'=>'ناموفق','cancelled'=>'لغو شده'] as $val=>$lbl)
            <option value="{{ $val }}" @selected(request('status')===$val)>{{ $lbl }}</option>
            @endforeach
        </select>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        @if($campaigns->isEmpty())
        <div class="text-center py-16">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            <p class="text-gray-400 font-medium">هنوز کمپینی ندارید</p>
            <p class="text-sm text-gray-400 mt-1">اولین کمپین پیامکی خود را بسازید</p>
            <a href="{{ route('admin.sms.campaigns.create') }}" class="mt-4 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                ایجاد کمپین
            </a>
        </div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">نام کمپین</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">مخاطبین</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">ارسال</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">وضعیت</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">تاریخ</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($campaigns as $campaign)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.sms.campaigns.show', $campaign) }}" class="font-medium text-gray-900 hover:text-indigo-600">{{ $campaign->name }}</a>
                        <p class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ mb_substr($campaign->message, 0, 50) }}...</p>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-gray-700">{{ number_format($campaign->total_recipients) }}</span>
                        <span class="text-xs text-gray-400 block">{{ $campaign->audienceLabel() }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($campaign->total_recipients > 0)
                        <div class="flex items-center gap-2">
                            <div class="w-16 bg-gray-100 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full bg-emerald-500" style="width:{{ $campaign->deliveryRate() }}%"></div>
                            </div>
                            <span class="text-xs text-gray-600">{{ $campaign->deliveryRate() }}%</span>
                        </div>
                        <span class="text-xs text-gray-400">{{ number_format($campaign->sent_count) }} / {{ number_format($campaign->total_recipients) }}</span>
                        @else
                        <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $campaign->statusColor() }}-100 text-{{ $campaign->statusColor() }}-700">
                            {{ $campaign->statusLabel() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">
                        {{ \App\Helpers\Jalali::format($campaign->created_at, 'Y/m/d') }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('admin.sms.campaigns.show', $campaign) }}" class="p-1.5 text-gray-400 hover:text-indigo-600 rounded">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            @if($campaign->isDraft())
                            <a href="{{ route('admin.sms.campaigns.edit', $campaign) }}" class="p-1.5 text-gray-400 hover:text-blue-600 rounded">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            @endif
                            <form method="POST" action="{{ route('admin.sms.campaigns.duplicate', $campaign) }}" class="inline">
                                @csrf
                                <button type="submit" class="p-1.5 text-gray-400 hover:text-green-600 rounded" title="کپی">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $campaigns->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@extends('admin.layout')
@section('title', 'مخاطب: ' . $contact->name)

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500 mb-5">
        <a href="{{ route('admin.crm.index') }}" class="hover:text-indigo-600">CRM</a>
        <span>/</span>
        <a href="{{ route('admin.crm.contacts.index') }}" class="hover:text-indigo-600">مخاطبین</a>
        <span>/</span>
        <span class="text-slate-800">{{ $contact->name }}</span>
    </nav>

    @if(session('success'))
    <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-700 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Contact Card --}}
        <div class="lg:col-span-1 space-y-5">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-2xl bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xl">
                            {{ mb_substr($contact->name, 0, 1) }}
                        </div>
                        <div>
                            <h1 class="text-lg font-bold text-slate-800">{{ $contact->name }}</h1>
                            @if($contact->position)
                            <p class="text-sm text-slate-500">{{ $contact->position }}{{ $contact->company ? ' · ' . $contact->company : '' }}</p>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('admin.crm.contacts.edit', $contact) }}" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                </div>

                @php $color = \App\Models\CrmContact::statusColor($contact->status); @endphp
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-{{ $color }}-50 text-{{ $color }}-700 border border-{{ $color }}-200 mb-4">
                    {{ \App\Models\CrmContact::statusLabel($contact->status) }}
                </span>

                <div class="space-y-2.5 text-sm">
                    @if($contact->mobile)
                    <div class="flex items-center gap-2 text-slate-600">
                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        {{ $contact->mobile }}
                    </div>
                    @endif
                    @if($contact->email)
                    <div class="flex items-center gap-2 text-slate-600">
                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        {{ $contact->email }}
                    </div>
                    @endif
                    @if($contact->city)
                    <div class="flex items-center gap-2 text-slate-600">
                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $contact->city }}
                    </div>
                    @endif
                </div>

                @if($contact->notes)
                <div class="mt-4 p-3 bg-slate-50 rounded-lg text-sm text-slate-600">
                    {{ $contact->notes }}
                </div>
                @endif
            </div>

            {{-- New Deal Form --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-4">ثبت معامله جدید</h3>
                <form method="POST" action="{{ route('admin.crm.deals.store') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="contact_id" value="{{ $contact->id }}">
                    <input name="title" placeholder="عنوان معامله" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
                    <input name="value" type="number" placeholder="ارزش (تومان)" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
                    <select name="stage" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
                        @foreach(\App\Models\CrmDeal::stages() as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="w-full py-2 rounded-lg bg-violet-600 text-white text-sm font-medium hover:bg-violet-700 transition">ثبت معامله</button>
                </form>
            </div>
        </div>

        {{-- Activities + Deals --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Log Activity --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-4">ثبت فعالیت</h3>
                <form method="POST" action="{{ route('admin.crm.activities.store', $contact) }}" class="space-y-3">
                    @csrf
                    <div class="flex gap-3">
                        <select name="type" class="px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
                            <option value="note">یادداشت</option>
                            <option value="call">تماس تلفنی</option>
                            <option value="meeting">جلسه</option>
                            <option value="email">ایمیل</option>
                            <option value="task">وظیفه</option>
                        </select>
                        <input name="subject" placeholder="موضوع" required class="flex-1 px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
                    </div>
                    <textarea name="body" placeholder="جزئیات (اختیاری)..." rows="2" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none resize-none"></textarea>
                    <div class="flex items-center justify-between">
                        <select name="outcome" class="px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
                            <option value="none">بدون نتیجه خاص</option>
                            <option value="interested">علاقه‌مند</option>
                            <option value="not_interested">علاقه‌مند نیست</option>
                            <option value="follow_up">نیاز به پیگیری</option>
                        </select>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">ثبت فعالیت</button>
                    </div>
                </form>
            </div>

            {{-- Deals --}}
            @if($contact->deals->isNotEmpty())
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-4">معاملات ({{ $contact->deals->count() }})</h3>
                <div class="space-y-3">
                    @foreach($contact->deals as $deal)
                    <div class="flex items-center justify-between p-3 rounded-lg border border-slate-100 hover:border-slate-200 transition">
                        <div>
                            <p class="text-sm font-medium text-slate-800">{{ $deal->title }}</p>
                            <p class="text-xs text-slate-500">{{ number_format($deal->value) }} تومان · {{ \App\Models\CrmDeal::stages()[$deal->stage] ?? $deal->stage }}</p>
                        </div>
                        @php $dc = \App\Models\CrmDeal::stageColor($deal->stage); @endphp
                        <span class="text-xs px-2 py-0.5 rounded-full bg-{{ $dc }}-50 text-{{ $dc }}-700 border border-{{ $dc }}-200">
                            {{ \App\Models\CrmDeal::stages()[$deal->stage] ?? $deal->stage }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Activity Feed --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-4">تاریخچه فعالیت‌ها</h3>
                @forelse($contact->activities as $activity)
                <div class="relative flex gap-3 pb-5 last:pb-0">
                    <div class="absolute right-3.5 top-8 bottom-0 w-px bg-slate-100 last:hidden"></div>
                    <div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 shrink-0 z-10">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($activity->type === 'call')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            @endif
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-slate-800">{{ $activity->subject }}</p>
                            <span class="text-xs text-slate-400 shrink-0 mr-2">{{ \App\Helpers\Jalali::diffForHumansFa($activity->created_at) }}</span>
                        </div>
                        <p class="text-xs text-slate-500">{{ \App\Models\CrmActivity::typeLabel($activity->type) }}</p>
                        @if($activity->body)
                        <p class="text-xs text-slate-600 mt-1">{{ $activity->body }}</p>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-slate-400 text-sm text-center py-6">هنوز فعالیتی ثبت نشده است.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

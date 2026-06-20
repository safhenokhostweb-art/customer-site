@extends('admin.layout')
@section('title', 'مخاطبین CRM')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">مخاطبین</h1>
            <p class="text-slate-500 text-sm mt-0.5">{{ number_format($contacts->total()) }} مخاطب</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <form method="POST" action="{{ route('admin.crm.contacts.import') }}" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    وارد کردن از سفارشات
                </button>
            </form>
            <a href="{{ route('admin.crm.contacts.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                مخاطب جدید
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <input name="q" value="{{ request('q') }}" placeholder="جستجو نام، موبایل، شرکت..." class="flex-1 min-w-48 px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
        <select name="status" class="px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
            <option value="">همه وضعیت‌ها</option>
            <option value="lead" {{ request('status') === 'lead' ? 'selected' : '' }}>سرنخ</option>
            <option value="prospect" {{ request('status') === 'prospect' ? 'selected' : '' }}>بالقوه</option>
            <option value="customer" {{ request('status') === 'customer' ? 'selected' : '' }}>مشتری</option>
            <option value="churned" {{ request('status') === 'churned' ? 'selected' : '' }}>از دست رفته</option>
        </select>
        <select name="source" class="px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
            <option value="">همه منابع</option>
            <option value="manual" {{ request('source') === 'manual' ? 'selected' : '' }}>دستی</option>
            <option value="order" {{ request('source') === 'order' ? 'selected' : '' }}>سفارش</option>
            <option value="ticket" {{ request('source') === 'ticket' ? 'selected' : '' }}>تیکت</option>
            <option value="form" {{ request('source') === 'form' ? 'selected' : '' }}>فرم</option>
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">فیلتر</button>
        @if(request('q') || request('status') || request('source'))
        <a href="{{ route('admin.crm.contacts.index') }}" class="px-3 py-2 rounded-lg border border-slate-200 text-slate-600 text-sm hover:bg-slate-50 transition">پاک کردن</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">نام</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600 hidden sm:table-cell">موبایل</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600 hidden md:table-cell">شرکت</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">وضعیت</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600 hidden lg:table-cell">معاملات</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600 hidden lg:table-cell">فعالیت‌ها</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($contacts as $contact)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-semibold text-xs shrink-0">
                                {{ mb_substr($contact->name, 0, 1) }}
                            </div>
                            <div>
                                <a href="{{ route('admin.crm.contacts.show', $contact) }}" class="font-medium text-slate-800 hover:text-indigo-600 transition">{{ $contact->name }}</a>
                                @if($contact->position)
                                <p class="text-xs text-slate-400">{{ $contact->position }}</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-slate-600 hidden sm:table-cell">{{ $contact->mobile ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-600 hidden md:table-cell">{{ $contact->company ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @php $color = \App\Models\CrmContact::statusColor($contact->status); @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $color }}-50 text-{{ $color }}-700 border border-{{ $color }}-200">
                            {{ \App\Models\CrmContact::statusLabel($contact->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-600 hidden lg:table-cell">{{ $contact->deals_count }}</td>
                    <td class="px-4 py-3 text-slate-600 hidden lg:table-cell">{{ $contact->activities_count }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1">
                            <a href="{{ route('admin.crm.contacts.show', $contact) }}" class="p-1.5 rounded hover:bg-indigo-50 text-slate-400 hover:text-indigo-600 transition" title="مشاهده">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <a href="{{ route('admin.crm.contacts.edit', $contact) }}" class="p-1.5 rounded hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition" title="ویرایش">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                        <svg class="w-12 h-12 mx-auto mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <p class="font-medium">هنوز مخاطبی ثبت نشده است</p>
                        <p class="text-sm mt-1">اولین مخاطب را اضافه کنید یا از سفارشات وارد نمایید.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($contacts->hasPages())
    <div class="mt-4">{{ $contacts->links() }}</div>
    @endif

</div>
@endsection

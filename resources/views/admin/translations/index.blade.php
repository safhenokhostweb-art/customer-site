@extends('admin.layout')
@section('title', 'مدیریت ترجمه‌های هوش مصنوعی')

@section('content')
<div class="admin-page-container">
    <div class="admin-page-header flex items-center justify-between mb-6">
        <div>
            <h1 class="admin-page-title">ترجمه‌های هوش مصنوعی</h1>
            <p class="admin-page-subtitle text-sm text-slate-500 mt-1">محتوای فارسی به عربی و انگلیسی — یک‌بار ترجمه، ذخیره دائمی</p>
        </div>
        <div class="flex items-center gap-3">
            <form method="POST" action="{{ route('admin.translations.process') }}">
                @csrf
                <input type="hidden" name="batch" value="30">
                <button type="submit" class="btn btn-primary gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    پردازش ۳۰ آیتم
                </button>
            </form>
        </div>
    </div>

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 text-center shadow-sm">
            <p class="text-3xl font-bold text-green-600">@numFa($stats['done'])</p>
            <p class="text-sm text-slate-500 mt-1">ترجمه‌شده</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 text-center shadow-sm">
            <p class="text-3xl font-bold text-blue-600">@numFa($stats['pending'])</p>
            <p class="text-sm text-slate-500 mt-1">در صف</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 text-center shadow-sm">
            <p class="text-3xl font-bold text-amber-600">@numFa($stats['processing'])</p>
            <p class="text-sm text-slate-500 mt-1">در حال پردازش</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 text-center shadow-sm">
            <p class="text-3xl font-bold text-red-600">@numFa($stats['failed'])</p>
            <p class="text-sm text-slate-500 mt-1">ناموفق</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent translations --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-semibold text-slate-800">آخرین ترجمه‌ها</h3>
                <form method="POST" action="{{ route('admin.translations.clear-done') }}">
                    @csrf
                    <button type="submit" class="text-xs text-slate-400 hover:text-red-500 transition">پاک‌سازی صف تکمیل</button>
                </form>
            </div>
            @if($recentTranslations->isEmpty())
                <p class="p-6 text-slate-400 text-sm text-center">هنوز ترجمه‌ای ثبت نشده است.</p>
            @else
                <div class="divide-y divide-slate-50">
                    @foreach($recentTranslations as $t)
                    <div class="px-5 py-3 flex items-center gap-3">
                        <span class="text-xs px-2 py-0.5 rounded-full font-bold
                            {{ $t->locale === 'ar' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ strtoupper($t->locale) }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-slate-700 truncate">{{ class_basename($t->translatable_type) }} #{{ $t->translatable_id }} · {{ $t->field_name }}</p>
                            <p class="text-xs text-slate-400 truncate mt-0.5" dir="auto">{{ Str::limit($t->translated_text, 60) }}</p>
                        </div>
                        <span class="text-xs text-slate-400 shrink-0">{{ $t->generated_at ? \App\Helpers\Jalali::formatFa($t->generated_at, 'H:i') : '' }}</span>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Failed items --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-semibold text-slate-800">آیتم‌های ناموفق</h3>
                @if($failedItems->isNotEmpty())
                <form method="POST" action="{{ route('admin.translations.retry') }}">
                    @csrf
                    <button type="submit" class="text-xs text-blue-500 hover:text-blue-700 transition font-medium">تلاش مجدد همه</button>
                </form>
                @endif
            </div>
            @if($failedItems->isEmpty())
                <div class="p-6 text-center">
                    <svg class="w-10 h-10 text-green-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-slate-400 text-sm">هیچ خطایی وجود ندارد.</p>
                </div>
            @else
                <div class="divide-y divide-slate-50">
                    @foreach($failedItems as $item)
                    <div class="px-5 py-3">
                        <p class="text-sm text-slate-700">{{ class_basename($item->translatable_type) }} #{{ $item->translatable_id }} · {{ $item->field_name }} · {{ strtoupper($item->locale) }}</p>
                        @if($item->last_error)
                        <p class="text-xs text-red-500 mt-0.5 truncate" title="{{ $item->last_error }}">{{ Str::limit($item->last_error, 80) }}</p>
                        @endif
                        <p class="text-xs text-slate-400 mt-0.5">تلاش: {{ $item->attempts }}</p>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="mt-6 p-4 bg-blue-50 rounded-2xl border border-blue-200 text-sm text-blue-800">
        <strong>نحوه کار:</strong> هر بار که محتوای فارسی ذخیره می‌شود، به‌صورت خودکار به صف ترجمه اضافه می‌شود.
        صف هر ۱۵ دقیقه پردازش می‌شود. برای ترجمه فوری، دکمه «پردازش ۳۰ آیتم» را کلیک کنید.
        ترجمه‌ها یک‌بار ذخیره می‌شوند و در دفعات بعد مجدداً ترجمه نمی‌شوند مگر محتوا تغییر کند.
    </div>
</div>
@endsection

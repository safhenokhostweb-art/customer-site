@extends('admin.layout')
@section('title', 'تاریخچه نسخه ماژول‌ها')
@section('header', 'تاریخچه نسخه ماژول‌ها')
@section('content')
<div class="admin-page">
    <div class="card mb-6 admin-table-wrap">
        <div class="card-body flex flex-wrap items-center justify-between gap-3">
            <p class="admin-section-lead" style="margin:0;max-width:42rem">
                در این صفحه تمام تغییرات منتشرشدهٔ ماژول‌ها از پلتفرم نمایش داده می‌شود. با همگام‌سازی، آخرین لاگ‌ها از سرور دریافت می‌شود.
            </p>
            <a href="{{ route('admin.modules.changelog', array_filter(['sync' => 1, 'module' => $moduleSlug ?? null])) }}" class="btn btn-secondary shrink-0">همگام‌سازی لاگ‌ها</a>
        </div>
    </div>

    @if(!empty($moduleSlug))
        <div class="card mb-4">
            <div class="card-body text-sm text-slate-700">
                فیلتر فعال: <span class="badge badge-secondary">{{ $moduleSlug }}</span>
                <a href="{{ route('admin.modules.changelog') }}" class="text-indigo-600 hover:underline mr-2">حذف فیلتر</a>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">لاگ تغییرات ماژول‌ها</div>
        <div class="card-body">
            @if($logs->count() === 0)
                <div class="admin-empty-state" role="status">
                    <svg class="admin-empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="admin-empty-state__title">لاگی ثبت نشده</p>
                    <p class="admin-empty-state__text">هنوز لاگی برای ماژول‌ها ثبت نشده است. از دکمهٔ همگام‌سازی بالا برای دریافت از پلتفرم استفاده کنید.</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($logs as $log)
                        @php
                            $installed = $installedVersions[$log->module_slug] ?? null;
                            $isNewer = $installed ? version_compare($log->version, $installed, '>') : true;
                        @endphp
                        <article class="rounded-xl border border-slate-200 p-4">
                            <div class="flex items-center justify-between gap-2">
                                <div class="font-semibold text-slate-900">
                                    {{ str_replace('_', ' ', $log->module_slug) }}
                                    <span class="text-xs text-slate-500">(slug: {{ $log->module_slug }})</span>
                                </div>
                                <div class="text-xs flex items-center gap-2">
                                    <span class="badge {{ $isNewer ? 'badge-warning' : 'badge-success' }}">
                                        {{ $isNewer ? 'نسخه جدید' : 'نصب‌شده/قدیمی‌تر' }}
                                    </span>
                                    <span class="badge badge-secondary">{{ strtoupper($log->release_type) }}</span>
                                    <span class="font-mono">v{{ $log->version }}</span>
                                </div>
                            </div>
                            @if($log->title)
                                <p class="mt-2 text-sm font-medium text-slate-800">{{ $log->title }}</p>
                            @endif
                            @if($log->changelog)
                                <p class="mt-2 text-sm text-slate-700 whitespace-pre-line">{{ $log->changelog }}</p>
                            @endif
                            <div class="mt-2 text-xs text-slate-500">
                                انتشار: {{ $log->released_at ? \App\Helpers\Jalali::formatFa($log->released_at, 'Y/m/d H:i') : '—' }}
                                |
                                همگام‌سازی: {{ $log->synced_at ? \App\Helpers\Jalali::formatFa($log->synced_at, 'Y/m/d H:i') : '—' }}
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="mt-4">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

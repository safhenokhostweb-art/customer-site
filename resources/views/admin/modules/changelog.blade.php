@extends('admin.layout')
@section('title', 'تاریخچه نسخه ماژول‌ها')
@section('header', 'تاریخچه نسخه ماژول‌ها')
@section('content')
<div class="admin-page">
    <div class="card mb-6">
        <div class="card-body flex items-center justify-between">
            <p class="text-sm text-slate-600">
                در این صفحه تمام تغییرات منتشرشدهٔ ماژول‌ها از پلتفرم نمایش داده می‌شود.
            </p>
            <a href="{{ route('admin.modules.changelog', ['sync' => 1]) }}" class="btn btn-secondary">همگام‌سازی لاگ‌ها</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">لاگ تغییرات ماژول‌ها</div>
        <div class="card-body">
            @if($logs->count() === 0)
                <p class="text-sm text-slate-500">هنوز لاگی برای ماژول‌ها ثبت نشده است.</p>
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

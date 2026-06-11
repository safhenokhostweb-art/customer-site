@extends('admin.layout')
@section('title', 'لاگ فعالیت ادمین')
@section('header', 'لاگ فعالیت ادمین')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">لاگ فعالیت ادمین</h1>
            <p class="text-slate-600 text-sm mt-1">عملیات‌های حساس انجام‌شده توسط مدیران سایت (شامل تغییر تنظیمات ماژول، بروزرسانی لایسنس، …).</p>
        </div>
    </div>

    @include('admin.activity-log.guide')

    @if(session('error'))
        <div class="alert alert-danger mb-4">{{ session('error') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.activity-log.index') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="form-label text-xs">مدیر</label>
                    <select name="admin_user_id" class="form-select" style="min-width:200px;">
                        <option value="">همه</option>
                        @foreach($admins as $a)
                            <option value="{{ $a->id }}" {{ (string) request('admin_user_id') === (string) $a->id ? 'selected' : '' }}>
                                {{ $a->name ?: ($a->mobile ?: ('#' . $a->id)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label text-xs">نوع عملیات</label>
                    <select name="action" class="form-select" style="min-width:220px;">
                        <option value="">همه</option>
                        @foreach($actionOptions as $key => $label)
                            <option value="{{ $key }}" {{ request('action') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label text-xs">از تاریخ (شمسی)</label>
                    <x-persian-date-input name="from" :value="request('from')" placeholder="۱۴۰۵/۰۲/۰۱" class="form-control" />
                </div>
                <div>
                    <label class="form-label text-xs">تا تاریخ (شمسی)</label>
                    <x-persian-date-input name="to" :value="request('to')" placeholder="۱۴۰۵/۱۲/۲۹" class="form-control" />
                </div>
                <button type="submit" class="btn btn-primary">فیلتر</button>
                <a href="{{ route('admin.activity-log.index') }}" class="btn btn-secondary">پاک کردن فیلتر</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0 overflow-x-auto">
            <table class="admin-table w-full text-sm">
                <thead>
                    <tr>
                        <th>تاریخ (شمسی)</th>
                        <th>مدیر</th>
                        <th>عملیات</th>
                        <th>توضیح</th>
                        <th>subject</th>
                        <th>IP</th>
                        <th>جزئیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap">{{ \App\Helpers\Jalali::formatFa($log->created_at, 'Y/m/d H:i') }}</td>
                            <td>
                                @if($log->adminUser)
                                    {{ $log->adminUser->name ?: ($log->adminUser->mobile ?: ('#' . $log->adminUser->id)) }}
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td><code class="text-xs">{{ $log->action }}</code></td>
                            <td>{{ $log->description ?: \App\Models\AdminActivityLog::actionLabel($log->action) }}</td>
                            <td>
                                @if($log->subject_type)
                                    <span class="text-xs text-slate-500">
                                        {{ class_basename($log->subject_type) }}#{{ $log->subject_id }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="text-xs text-slate-500 font-mono">{{ $log->ip_address ?: '—' }}</td>
                            <td>
                                @if(!empty($log->properties))
                                    <details>
                                        <summary class="cursor-pointer text-xs text-indigo-600">نمایش</summary>
                                        <pre class="text-xs mt-1 p-2 bg-slate-50 rounded overflow-auto" style="max-width:520px;">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-slate-500 py-8">رکوردی برای نمایش وجود ندارد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="card-body">{{ $logs->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection

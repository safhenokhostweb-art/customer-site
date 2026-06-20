@extends('admin.layout')
@section('title', 'لاگ Webhook')
@section('header', 'لاگ رویدادهای Webhook')
@section('content')
<div class="admin-page max-w-5xl">

    {{-- Endpoint summary card --}}
    <div class="card mb-6">
        <div class="card-header flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <span>اطلاعات Endpoint</span>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.webhooks.edit', $webhook) }}" class="btn btn-primary btn-sm">ویرایش</a>
                <a href="{{ route('admin.webhooks.index') }}" class="btn btn-secondary btn-sm">بازگشت</a>
            </div>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-slate-400 mb-1">آدرس URL</p>
                    <p class="font-mono text-sm text-slate-700 break-all">{{ $webhook->url }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 mb-1">وضعیت</p>
                    @if($webhook->is_active)
                        <span class="badge badge-success">فعال</span>
                    @else
                        <span class="badge badge-danger">غیرفعال</span>
                    @endif
                </div>
                <div>
                    <p class="text-xs text-slate-400 mb-1">رویدادهای عضو</p>
                    <div class="flex flex-wrap gap-1 mt-1">
                        @foreach($webhook->events as $evt)
                            <span class="inline-block px-2 py-0.5 bg-indigo-50 text-indigo-700 text-xs rounded-full font-mono">{{ $evt }}</span>
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="text-xs text-slate-400 mb-1">Secret Key</p>
                    <code class="font-mono text-xs text-slate-500 break-all">{{ $webhook->secret }}</code>
                </div>
            </div>
        </div>
    </div>

    {{-- Event log table --}}
    <div class="card">
        <div class="card-header">لاگ رویدادها</div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>رویداد</th>
                        <th>وضعیت</th>
                        <th>تلاش</th>
                        <th>کد پاسخ</th>
                        <th>زمان ارسال</th>
                        <th>ایجاد</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        <tr x-data="{ open: false }">
                            <td>
                                <span class="font-mono text-sm text-slate-700">{{ $event->event_type }}</span>
                            </td>
                            <td>
                                @if($event->status === 'sent')
                                    <span class="badge badge-success">ارسال شد</span>
                                @elseif($event->status === 'failed')
                                    <span class="badge badge-danger">خطا</span>
                                @else
                                    <span class="badge badge-warning">در صف</span>
                                @endif
                            </td>
                            <td class="text-sm text-slate-500">{{ $event->attempts }}</td>
                            <td>
                                @if($event->response_code)
                                    <span class="font-mono text-xs px-2 py-0.5 rounded {{ $event->response_code >= 200 && $event->response_code < 300 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $event->response_code }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="text-sm text-slate-500">
                                {{ $event->last_attempted_at ? \App\Helpers\Jalali::diffForHumansFa($event->last_attempted_at) : '—' }}
                            </td>
                            <td class="text-sm text-slate-500">
                                {{ \App\Helpers\Jalali::formatFa($event->created_at, 'Y/m/d H:i') }}
                            </td>
                        </tr>
                        @if($event->response_body)
                            <tr class="bg-slate-50">
                                <td colspan="6" class="px-4 py-2">
                                    <details>
                                        <summary class="text-xs text-slate-500 cursor-pointer">نمایش پاسخ</summary>
                                        <pre class="mt-2 text-xs font-mono text-slate-600 whitespace-pre-wrap break-all bg-white border rounded p-3">{{ $event->response_body }}</pre>
                                    </details>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="admin-empty">هنوز رویدادی ثبت نشده است.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($events->hasPages())
        <div class="admin-pagination">{{ $events->links() }}</div>
    @endif
</div>
@endsection

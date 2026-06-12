@extends('admin.layout')
@section('title', 'مدیریت Webhooks')
@section('header', 'Webhook Endpoints')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <p class="text-slate-600 text-sm">اطلاع‌رسانی خودکار به سیستم‌های خارجی هنگام تغییر اشتراک کاربران.</p>
        <div class="admin-page-tools">
            <a href="{{ route('admin.webhooks.create') }}" class="btn btn-primary">
                <x-core-ui::icon name="webhook" class="w- />
                Endpoint جدید
            </a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>آدرس</th>
                        <th>رویدادها</th>
                        <th>وضعیت</th>
                        <th>آخرین فراخوانی</th>
                        <th>لاگ‌ها</th>
                        <th style="width:160px;">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($endpoints as $endpoint)
                        <tr>
                            <td>
                                <div class="font-medium text-sm text-slate-800 break-all">{{ $endpoint->url }}</div>
                                @if($endpoint->description)
                                    <div class="text-xs text-slate-400 mt-0.5">{{ $endpoint->description }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($endpoint->events as $evt)
                                        <span class="inline-block px-2 py-0.5 bg-indigo-50 text-indigo-700 text-xs rounded-full font-mono">{{ $evt }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                @if($endpoint->is_active)
                                    <span class="badge badge-success">فعال</span>
                                @else
                                    <span class="badge badge-danger">غیرفعال</span>
                                @endif
                            </td>
                            <td class="text-sm text-slate-500">
                                @if($endpoint->last_called_at)
                                    {{ $endpoint->last_called_at->diffForHumans() }}
                                    @if($endpoint->last_status_code)
                                        <span class="inline-block px-1.5 py-0.5 rounded text-xs font-mono {{ $endpoint->last_status_code >= 200 && $endpoint->last_status_code < 300 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $endpoint->last_status_code }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-slate-500 text-sm">{{ number_format($endpoint->webhook_events_count) }}</span>
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    <a href="{{ route('admin.webhooks.show', $endpoint) }}" class="btn btn-secondary btn-sm">لاگ</a>
                                    <a href="{{ route('admin.webhooks.edit', $endpoint) }}" class="btn btn-primary btn-sm">ویرایش</a>
                                    <form method="post" action="{{ route('admin.webhooks.destroy', $endpoint) }}" class="inline" onsubmit="return confirm('حذف این webhook؟');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="admin-empty">
                                هنوز webhook endpoint تعریف نشده است.
                                <a href="{{ route('admin.webhooks.create') }}" class="text-indigo-600 hover:underline">اولین endpoint را ایجاد کنید</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($endpoints->hasPages())
        <div class="admin-pagination">{{ $endpoints->links() }}</div>
    @endif
</div>
@endsection


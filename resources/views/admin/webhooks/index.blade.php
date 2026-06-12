@extends('admin.layout')
@section('title', 'ظ…ط¯غŒط±غŒطھ Webhooks')
@section('header', 'Webhook Endpoints')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <p class="text-slate-600 text-sm">ط§ط·ظ„ط§ط¹â€Œط±ط³ط§ظ†غŒ ط®ظˆط¯ع©ط§ط± ط¨ظ‡ ط³غŒط³طھظ…â€Œظ‡ط§غŒ ط®ط§ط±ط¬غŒ ظ‡ظ†ع¯ط§ظ… طھط؛غŒغŒط± ط§ط´طھط±ط§ع© ع©ط§ط±ط¨ط±ط§ظ†.</p>
        <div class="admin-page-tools">
            <a href="{{ route('admin.webhooks.create') }}" class="btn btn-primary">
                <x-core-ui::icon name="webhook" class="w- />
                Endpoint ط¬ط¯غŒط¯
            </a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ط¢ط¯ط±ط³</th>
                        <th>ط±ظˆغŒط¯ط§ط¯ظ‡ط§</th>
                        <th>ظˆط¶ط¹غŒطھ</th>
                        <th>ط¢ط®ط±غŒظ† ظپط±ط§ط®ظˆط§ظ†غŒ</th>
                        <th>ظ„ط§ع¯â€Œظ‡ط§</th>
                        <th style="width:160px;">ط¹ظ…ظ„غŒط§طھ</th>
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
                                    <span class="badge badge-success">ظپط¹ط§ظ„</span>
                                @else
                                    <span class="badge badge-danger">ط؛غŒط±ظپط¹ط§ظ„</span>
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
                                    <span class="text-slate-400">â€”</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-slate-500 text-sm">{{ number_format($endpoint->webhook_events_count) }}</span>
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    <a href="{{ route('admin.webhooks.show', $endpoint) }}" class="btn btn-secondary btn-sm">ظ„ط§ع¯</a>
                                    <a href="{{ route('admin.webhooks.edit', $endpoint) }}" class="btn btn-primary btn-sm">ظˆغŒط±ط§غŒط´</a>
                                    <form method="post" action="{{ route('admin.webhooks.destroy', $endpoint) }}" class="inline" onsubmit="return confirm('ط­ط°ظپ ط§غŒظ† webhookطں');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">ط­ط°ظپ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="admin-empty">
                                ظ‡ظ†ظˆط² webhook endpoint طھط¹ط±غŒظپ ظ†ط´ط¯ظ‡ ط§ط³طھ.
                                <a href="{{ route('admin.webhooks.create') }}" class="text-indigo-600 hover:underline">ط§ظˆظ„غŒظ† endpoint ط±ط§ ط§غŒط¬ط§ط¯ ع©ظ†غŒط¯</a>
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


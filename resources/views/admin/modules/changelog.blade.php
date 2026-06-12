@extends('admin.layout')
@section('title', 'طھط§ط±غŒط®ع†ظ‡ ظ†ط³ط®ظ‡ ظ…ط§عکظˆظ„â€Œظ‡ط§')
@section('header', 'طھط§ط±غŒط®ع†ظ‡ ظ†ط³ط®ظ‡ ظ…ط§عکظˆظ„â€Œظ‡ط§')
@section('content')
<div class="admin-page">
    <div class="card mb-6 admin-table-wrap">
        <div class="card-body flex flex-wrap items-center justify-between gap-3">
            <p class="admin-section-lead" style="margin:0;max-width:42rem">
                ط¯ط± ط§غŒظ† طµظپط­ظ‡ طھظ…ط§ظ… طھط؛غŒغŒط±ط§طھ ظ…ظ†طھط´ط±ط´ط¯ظ‡ظ” ظ…ط§عکظˆظ„â€Œظ‡ط§ ط§ط² ظ¾ظ„طھظپط±ظ… ظ†ظ…ط§غŒط´ ط¯ط§ط¯ظ‡ ظ…غŒâ€Œط´ظˆط¯. ط¨ط§ ظ‡ظ…ع¯ط§ظ…â€Œط³ط§ط²غŒطŒ ط¢ط®ط±غŒظ† ظ„ط§ع¯â€Œظ‡ط§ ط§ط² ط³ط±ظˆط± ط¯ط±غŒط§ظپطھ ظ…غŒâ€Œط´ظˆط¯.
            </p>
            <a href="{{ route('admin.modules.changelog', array_filter(['sync' => 1, 'module' => $moduleSlug ?? null])) }}" class="btn btn-secondary shrink-0">ظ‡ظ…ع¯ط§ظ…â€Œط³ط§ط²غŒ ظ„ط§ع¯â€Œظ‡ط§</a>
        </div>
    </div>

    @if(!empty($moduleSlug))
        <div class="card mb-4">
            <div class="card-body text-sm text-slate-700">
                ظپغŒظ„طھط± ظپط¹ط§ظ„: <span class="badge badge-secondary">{{ $moduleSlug }}</span>
                <a href="{{ route('admin.modules.changelog') }}" class="text-indigo-600 hover:underline mr-2">ط­ط°ظپ ظپغŒظ„طھط±</a>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">ظ„ط§ع¯ طھط؛غŒغŒط±ط§طھ ظ…ط§عکظˆظ„â€Œظ‡ط§</div>
        <div class="card-body">
            @if($logs->count() === 0)
                <div class="admin-empty-state" role="status">
                    <x-core-ui::icon name="file-text" class="admin-empty-state__icon" />
                    <p class="admin-empty-state__title">ظ„ط§ع¯غŒ ط«ط¨طھ ظ†ط´ط¯ظ‡</p>
                    <p class="admin-empty-state__text">ظ‡ظ†ظˆط² ظ„ط§ع¯غŒ ط¨ط±ط§غŒ ظ…ط§عکظˆظ„â€Œظ‡ط§ ط«ط¨طھ ظ†ط´ط¯ظ‡ ط§ط³طھ. ط§ط² ط¯ع©ظ…ظ‡ظ” ظ‡ظ…ع¯ط§ظ…â€Œط³ط§ط²غŒ ط¨ط§ظ„ط§ ط¨ط±ط§غŒ ط¯ط±غŒط§ظپطھ ط§ط² ظ¾ظ„طھظپط±ظ… ط§ط³طھظپط§ط¯ظ‡ ع©ظ†غŒط¯.</p>
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
                                        {{ $isNewer ? 'ظ†ط³ط®ظ‡ ط¬ط¯غŒط¯' : 'ظ†طµط¨â€Œط´ط¯ظ‡/ظ‚ط¯غŒظ…غŒâ€Œطھط±' }}
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
                                ط§ظ†طھط´ط§ط±: {{ $log->released_at ? \App\Helpers\Jalali::formatFa($log->released_at, 'Y/m/d H:i') : 'â€”' }}
                                |
                                ظ‡ظ…ع¯ط§ظ…â€Œط³ط§ط²غŒ: {{ $log->synced_at ? \App\Helpers\Jalali::formatFa($log->synced_at, 'Y/m/d H:i') : 'â€”' }}
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


@extends('admin.layout')
@section('title', 'ظ…ط·ط§ظ„ط¨ ظˆط¨ظ„ط§ع¯')
@section('header', 'ظ…ط·ط§ظ„ط¨ ظˆط¨ظ„ط§ع¯')
@section('content')

@include('admin.partials.breadcrumb', ['crumbs' => [
    ['label' => 'ط¯ط§ط´ط¨ظˆط±ط¯', 'url' => route('admin.dashboard')],
    ['label' => 'ظ…ط·ط§ظ„ط¨ ظˆط¨ظ„ط§ع¯', 'url' => null],
]])

@include('admin.posts.guide')

<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">ظ…ط·ط§ظ„ط¨ ظˆط¨ظ„ط§ع¯</h1>
        <div class="admin-page-tools">
            <a href="{{ route('admin.posts.create') }}" class="btn btn-primary">ظ…ط·ظ„ط¨ ط¬ط¯غŒط¯</a>
        </div>
    </div>
    <div class="card mb-4 admin-sticky-filters">
        <div class="card-body">
            <form method="get" class="admin-filter-bar" aria-label="ظپغŒظ„طھط± ظ…ط·ط§ظ„ط¨">
                <div class="form-group mb-0">
                    <label class="form-label" for="posts-filter-q">ط¬ط³طھط¬ظˆ</label>
                    <input id="posts-filter-q" type="search" name="q" value="{{ request('q') }}" placeholder="ط¹ظ†ظˆط§ظ†طŒ ظ†ط§ظ…ع© غŒط§ ط®ظ„ط§طµظ‡" autocomplete="off">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label" for="posts-filter-status">ظˆط¶ط¹غŒطھ ط§ظ†طھط´ط§ط±</label>
                    <select id="posts-filter-status" name="status">
                        <option value="">ظ‡ظ…ظ‡ ظˆط¶ط¹غŒطھâ€Œظ‡ط§</option>
                        <option value="draft" @selected(request('status') === 'draft')>ظ¾غŒط´â€Œظ†ظˆغŒط³</option>
                        <option value="pending_review" @selected(request('status') === 'pending_review')>ط¯ط± ط§ظ†طھط¸ط§ط± ط¨ط§ط²ط¨غŒظ†غŒ</option>
                        <option value="approved" @selected(request('status') === 'approved')>طھط§غŒغŒط¯ط´ط¯ظ‡ (ظ…ظ†طھط´ط±ظ†ط´ط¯ظ‡)</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>ط±ط¯ط´ط¯ظ‡</option>
                        <option value="scheduled" @selected(request('status') === 'scheduled')>ط²ظ…ط§ظ†â€Œط¨ظ†ط¯غŒâ€Œط´ط¯ظ‡</option>
                        <option value="published" @selected(request('status') === 'published')>ظ…ظ†طھط´ط±ط´ط¯ظ‡</option>
                    </select>
                </div>
                <div class="form-group mb-0 flex items-end">
                    <button class="btn btn-secondary w-full" style="min-height:2.75rem" type="submit">ط§ط¹ظ…ط§ظ„ ظپغŒظ„طھط±</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card admin-table-wrap">
        <div class="table-responsive">
            <table>
                <thead><tr><th>ط¹ظ†ظˆط§ظ†</th><th>ط¯ط³طھظ‡</th><th>ظˆط¶ط¹غŒطھ</th><th>ط²ظ…ط§ظ† ط§ظ†طھط´ط§ط± / ط²ظ…ط§ظ†â€Œط¨ظ†ط¯غŒ (ط´ظ…ط³غŒ)</th><th>ط«ط¨طھ (ط´ظ…ط³غŒ)</th><th>ط¢ط®ط±غŒظ† ظˆغŒط±ط§غŒط´ (ط´ظ…ط³غŒ)</th><th>ط¢ظ…ط§ط±</th><th>ع©غŒظپغŒطھ ظ…ط­طھظˆط§</th><th>ط¹ظ…ظ„غŒط§طھ</th></tr></thead>
                <tbody>
                    @forelse($posts as $p)
                        @php
                            $status = $p->publicationStatus();
                            $hasSeo = !empty($p->meta_title) && !empty($p->meta_description);
                            $hasContent = !empty(trim(strip_tags((string) $p->body)));
                        @endphp
                        <tr>
                            <td>
                                <div class="font-semibold">{{ $p->title }}</div>
                                <div class="text-xs text-slate-500">{{ $p->slug }}</div>
                                @if($p->tags->isNotEmpty())
                                    <div class="text-xs text-slate-500 mt-1">{{ $p->tags->pluck('name')->implode('طŒ ') }}</div>
                                @endif
                            </td>
                            <td>{{ $p->category?->name ?? 'â€”' }}</td>
                            <td>
                                @if($status === 'scheduled')
                                    <span class="badge badge-scheduled">ط²ظ…ط§ظ†â€Œط¨ظ†ط¯غŒâ€Œط´ط¯ظ‡</span>
                                @elseif($status === 'published')
                                    <span class="badge badge-published">ظ…ظ†طھط´ط±ط´ط¯ظ‡</span>
                                @elseif($status === 'pending_review')
                                    <span class="badge badge-pending">ط¨ط§ط²ط¨غŒظ†غŒ</span>
                                @elseif($status === 'approved')
                                    <span class="badge badge-approved">طھط§غŒغŒط¯ ط´ط¯ظ‡</span>
                                @elseif($status === 'rejected')
                                    <span class="badge badge-rejected">ط±ط¯ ط´ط¯ظ‡</span>
                                @else
                                    <span class="badge badge-draft">ظ¾غŒط´â€Œظ†ظˆغŒط³</span>
                                @endif
                            </td>
                            <td>{{ $p->published_at ? \App\Helpers\Jalali::formatFa($p->published_at, 'Y/m/d H:i') : 'â€”' }}</td>
                            <td class="text-xs text-slate-600">{{ \App\Helpers\Jalali::formatFa($p->created_at, 'Y/m/d H:i') }}</td>
                            <td class="text-xs text-slate-600">@if($p->updated_at && $p->updated_at->ne($p->created_at)){{ \App\Helpers\Jalali::formatFa($p->updated_at, 'Y/m/d H:i') }}@elseâ€”@endif</td>
                            <td class="text-xs">
                                <div>ط¨ط§ط²ط¯غŒط¯: {{ number_format($p->view_count) }}</div>
                                <div>ظ„ط§غŒع©: {{ number_format($p->likes_count) }}</div>
                            </td>
                            <td class="text-xs">
                                <div>{{ $p->estimatedReadingMinutes() }} ط¯ظ‚غŒظ‚ظ‡ ظ…ط·ط§ظ„ط¹ظ‡</div>
                                <div class="{{ $hasSeo ? 'text-emerald-700' : 'text-amber-700' }}">{{ $hasSeo ? 'SEO ع©ط§ظ…ظ„' : 'SEO ظ†ط§ظ‚طµ' }}</div>
                                <div class="{{ $hasContent ? 'text-emerald-700' : 'text-red-700' }}">{{ $hasContent ? 'ظ…ط­طھظˆط§ ط¢ظ…ط§ط¯ظ‡' : 'ط¨ط¯ظˆظ† ظ…ط­طھظˆط§' }}</div>
                            </td>
                            <td>
                                <a href="{{ route('admin.posts.edit', $p) }}" class="btn btn-primary btn-sm">ظˆغŒط±ط§غŒط´</a>
                                <form method="post" action="{{ route('admin.posts.destroy', $p) }}" style="display:inline;" onsubmit="return confirm('ط­ط°ظپ ط´ظˆط¯طں');">@csrf @method('DELETE')<button type="submit" class="btn btn-danger btn-sm">ط­ط°ظپ</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-0">
                                <div class="admin-empty-state">
                                    <x-core-ui::icon name="file-text" class="admin-empty-state__icon" />
                                    <p class="admin-empty-state__title">ظ…ط·ظ„ط¨غŒ ط«ط¨طھ ظ†ط´ط¯ظ‡</p>
                                    <p class="admin-empty-state__text">ط¨ط§ ط¯ع©ظ…ظ‡ظ” آ«ظ…ط·ظ„ط¨ ط¬ط¯غŒط¯آ» ط§ظˆظ„غŒظ† ظ†ظˆط´طھظ‡ ط±ط§ ط§ط¶ط§ظپظ‡ ع©ظ†غŒط¯.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(method_exists($posts, 'links'))
        <div class="admin-pagination">{{ $posts->links() }}</div>
    @endif
</div>
@endsection


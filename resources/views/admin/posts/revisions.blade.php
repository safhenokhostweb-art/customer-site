@extends('admin.layout')
@section('title', 'طھط§ط±غŒط®ع†ظ‡ ظ†ط³ط®ظ‡â€Œظ‡ط§')
@section('header', 'طھط§ط±غŒط®ع†ظ‡ ظ†ط³ط®ظ‡â€Œظ‡ط§')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">ظ†ط³ط®ظ‡â€Œظ‡ط§غŒ آ«{{ $post->title }}آ»</h1>
        <div class="admin-page-tools">
            <a href="{{ route('admin.posts.edit', $post) }}" class="btn btn-secondary">ط¨ط§ط²ع¯ط´طھ ط¨ظ‡ ظˆغŒط±ط§غŒط´</a>
        </div>
    </div>

    <div class="card admin-table-wrap">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ط²ظ…ط§ظ†</th>
                        <th>ع©ط§ط±ط¨ط±</th>
                        <th>ط¹ظ†ظˆط§ظ† ط¯ط± ظ†ط³ط®ظ‡</th>
                        <th>ط¹ظ…ظ„غŒط§طھ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($revisions as $rev)
                        @php $snap = $rev->snapshot ?? []; @endphp
                        <tr>
                            <td>{{ \App\Helpers\Jalali::formatFa($rev->created_at, 'Y/m/d H:i') }}</td>
                            <td>{{ $rev->user?->name ?? 'â€”' }}</td>
                            <td>{{ $snap['title'] ?? 'â€”' }}</td>
                            <td>
                                <details class="admin-revision-details">
                                    <summary>ظ…ط´ط§ظ‡ط¯ظ‡ ط®ظ„ط§طµظ‡</summary>
                                    <pre dir="ltr">{{ json_encode($snap, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                </details>
                                @if(auth()->user()->hasPermission('posts_moderate'))
                                    <form method="post" action="{{ route('admin.posts.revisions.restore', [$post, $rev]) }}" class="mt-2" onsubmit="return confirm('ط§غŒظ† ظ†ط³ط®ظ‡ ط¬ط§غŒع¯ط²غŒظ† ظ…ط­طھظˆط§غŒ ظپط¹ظ„غŒ ط´ظˆط¯طں');">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary btn-sm">ط¨ط§ط²ع¯ط±ط¯ط§ظ†غŒ ط§غŒظ† ظ†ط³ط®ظ‡</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-0">
                                <div class="admin-empty-state">
                                    <x-core-ui::icon name="clock" class="admin-empty-state__icon" />
                                    <p class="admin-empty-state__title">ظ†ط³ط®ظ‡â€Œط§غŒ ط«ط¨طھ ظ†ط´ط¯ظ‡</p>
                                    <p class="admin-empty-state__text">ط¨ط§ ط°ط®غŒط±ظ‡ظ” ظ…ط·ظ„ط¨طŒ ظ†ط³ط®ظ‡â€Œظ‡ط§ ط¨ظ‡â€Œظ…ط±ظˆط± ط¯ط± ط§غŒظ†ط¬ط§ ظپظ‡ط±ط³طھ ظ…غŒâ€Œط´ظˆظ†ط¯.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(method_exists($revisions, 'links'))
        <div class="admin-pagination">{{ $revisions->links() }}</div>
    @endif
</div>
@endsection



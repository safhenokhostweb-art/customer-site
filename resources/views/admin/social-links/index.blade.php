@extends('admin.layout')
@section('title', 'ط´ط¨ع©ظ‡â€Œظ‡ط§غŒ ط§ط¬طھظ…ط§ط¹غŒ')
@section('header', 'ظ„غŒظ†ع©â€Œظ‡ط§غŒ ط´ط¨ع©ظ‡â€Œظ‡ط§غŒ ط§ط¬طھظ…ط§ط¹غŒ')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <p class="text-slate-600 text-sm">ظ…ط¯غŒط±غŒطھ ظ„غŒظ†ع©â€Œظ‡ط§غŒ ط´ط¨ع©ظ‡â€Œظ‡ط§غŒ ط§ط¬طھظ…ط§ط¹غŒ (ط®ط§ط±ط¬غŒ ظˆ ط§غŒط±ط§ظ†غŒ) ط¨ط±ط§غŒ ظ†ظ…ط§غŒط´ ط¯ط± ط³ط§غŒطھ.</p>
        <div class="admin-page-tools">
            <a href="{{ route('admin.social-links.create') }}" class="btn btn-primary">
                <x-core-ui::icon name="share" class="w- />
                ظ„غŒظ†ع© ط¬ط¯غŒط¯
            </a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
            <thead>
                <tr>
                    <th>ظ¾ظ„طھظپط±ظ…</th>
                    <th>ط¢ط¯ط±ط³</th>
                    <th>ظˆط¶ط¹غŒطھ</th>
                    <th style="width:180px">ط¹ظ…ظ„غŒط§طھ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($links as $l)
                    <tr>
                        <td class="font-medium">{{ \App\Models\SocialLink::platforms()[$l->platform] ?? $l->platform }}</td>
                        <td>
                            <a href="{{ $l->url }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline text-sm truncate max-w-[200px] block">
                                {{ Str::limit($l->url, 45) }}
                            </a>
                        </td>
                        <td>
                            @if($l->is_active)
                                <span class="badge badge-success">ظپط¹ط§ظ„</span>
                            @else
                                <span class="badge badge-danger">ط؛غŒط±ظپط¹ط§ظ„</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.social-links.edit', $l) }}" class="btn btn-primary btn-sm">ظˆغŒط±ط§غŒط´</a>
                                <form method="post" action="{{ route('admin.social-links.destroy', $l) }}" class="inline" onsubmit="return confirm('ط­ط°ظپ ط§غŒظ† ظ„غŒظ†ع©طں');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">ط­ط°ظپ</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="admin-empty">ظ‡ظ†ظˆط² ظ„غŒظ†ع©غŒ ط«ط¨طھ ظ†ط´ط¯ظ‡ ط§ط³طھ. <a href="{{ route('admin.social-links.create') }}" class="text-indigo-600 hover:underline">ط§ظˆظ„غŒظ† ظ„غŒظ†ع© ط±ط§ ط§ط¶ط§ظپظ‡ ع©ظ†غŒط¯</a></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($links->hasPages())
        <div class="admin-pagination">{{ $links->links() }}</div>
    @endif
</div>
@endsection


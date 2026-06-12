@extends('admin.layout')
@section('title', 'ع©ط§ط±طھâ€Œظ‡ط§غŒ ظˆغŒط²غŒطھ')
@section('header', 'ع©ط§ط±طھ ظˆغŒط²غŒطھ ط¯غŒط¬غŒطھط§ظ„')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <p class="text-slate-600 text-sm">ظ…ط¯غŒط±غŒطھ ع©ط§ط±طھâ€Œظ‡ط§غŒ ظˆغŒط²غŒطھ ط¯غŒط¬غŒطھط§ظ„ ط¨ط§ ظ„غŒظ†ع© ط§ط®طھطµط§طµغŒ.</p>
        <div class="admin-page-tools">
            <a href="{{ route('admin.business-cards.create') }}" class="btn btn-primary">
                <x-core-ui::icon name="profile" class="w- />
                ع©ط§ط±طھ ط¬ط¯غŒط¯
            </a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
            <thead>
                <tr>
                    <th>ظ†ط§ظ…</th>
                    <th>ط¹ظ†ظˆط§ظ†</th>
                    <th>ط´ط±ع©طھ</th>
                    <th>ظ„غŒظ†ع©</th>
                    <th>ظˆط¶ط¹غŒطھ</th>
                    <th style="width:180px">ط¹ظ…ظ„غŒط§طھ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cards as $c)
                    <tr>
                        <td class="font-medium">{{ $c->name }}</td>
                        <td>{{ $c->title ?? 'â€”' }}</td>
                        <td>{{ $c->company ?? 'â€”' }}</td>
                        <td>
                            <a href="{{ route('business-card.show', $c->slug) }}" target="_blank" class="text-indigo-600 hover:underline text-sm">
                                /card/{{ $c->slug }}
                            </a>
                        </td>
                        <td>
                            @if($c->is_active)
                                <span class="badge badge-success">ظپط¹ط§ظ„</span>
                            @else
                                <span class="badge badge-danger">ط؛غŒط±ظپط¹ط§ظ„</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.business-cards.edit', $c) }}" class="btn btn-primary btn-sm">ظˆغŒط±ط§غŒط´</a>
                                <form method="post" action="{{ route('admin.business-cards.destroy', $c) }}" class="inline" onsubmit="return confirm('ط­ط°ظپ ط§غŒظ† ع©ط§ط±طھطں');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">ط­ط°ظپ</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="admin-empty">ظ‡ظ†ظˆط² ع©ط§ط±طھغŒ ط«ط¨طھ ظ†ط´ط¯ظ‡ ط§ط³طھ. <a href="{{ route('admin.business-cards.create') }}" class="text-indigo-600 hover:underline">ط§ظˆظ„غŒظ† ع©ط§ط±طھ ط±ط§ ط§غŒط¬ط§ط¯ ع©ظ†غŒط¯</a></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($cards->hasPages())
        <div class="admin-pagination">{{ $cards->links() }}</div>
    @endif
</div>
@endsection


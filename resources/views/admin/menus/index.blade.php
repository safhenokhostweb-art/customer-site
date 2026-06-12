@extends('admin.layout')
@section('title', 'ظ…ظ†ظˆظ‡ط§')
@section('header', 'ظ…ظ†ظˆظ‡ط§')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">ظ…ظ†ظˆظ‡ط§</h1>
            <p class="admin-section-lead" style="margin-top:0.35rem">ظ…ط¯غŒط±غŒطھ ظ…ظ†ظˆظ‡ط§غŒ ظ‡ط¯ط±طŒ ظپظˆطھط±طŒ ظ…ظˆط¨ط§غŒظ„طŒ ط«ط§ظ†ظˆغŒظ‡ ظˆ ظ†ظˆط§ط± ط¨ط§ظ„ط§.</p>
        </div>
        <div class="admin-page-tools">
            <a href="{{ route('admin.menus.create') }}" class="btn btn-primary">ظ…ظ†ظˆغŒ ط¬ط¯غŒط¯</a>
        </div>
    </div>

    <form method="get" class="card mb-4 admin-sticky-filters" aria-label="ظپغŒظ„طھط± ظ…ظ†ظˆظ‡ط§">
        <div class="card-body admin-filter-bar">
            <div class="form-group mb-0">
                <label class="form-label" for="menu-filter-location">ظپغŒظ„طھط± ظ…ظˆظ‚ط¹غŒطھ</label>
                <select id="menu-filter-location" name="location" onchange="this.form.submit()">
                    <option value="">ظ‡ظ…ظ‡</option>
                    @foreach($locations as $k => $lbl)
                        <option value="{{ $k }}" @selected(request('location') === $k)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div></div>
            <div class="flex items-end">
                <noscript><button type="submit" class="btn btn-secondary" style="min-height:2.75rem">ط§ط¹ظ…ط§ظ„</button></noscript>
            </div>
        </div>
    </form>

    <div class="card admin-table-wrap">
        <div class="table-responsive">
            <table>
                <thead><tr><th>ظ†ط§ظ…</th><th>ظ…ظˆظ‚ط¹غŒطھ</th><th>ظˆط¶ط¹غŒطھ</th><th>ط¹ظ…ظ„غŒط§طھ</th></tr></thead>
                <tbody>
                    @forelse($menus as $menu)
                        <tr>
                            <td>{{ $menu->name }}</td>
                            <td>{{ $locations[$menu->location] ?? $menu->location }}</td>
                            <td>
                                @if($menu->status === 'published')
                                    <span class="badge badge-published">ظ…ظ†طھط´ط±</span>
                                @else
                                    <span class="badge badge-draft">ظ¾غŒط´â€Œظ†ظˆغŒط³</span>
                                @endif
                            </td>
                            <td>
                                <div class="admin-table-actions">
                                    <a href="{{ route('admin.menus.edit', $menu) }}" class="btn btn-primary btn-sm">ظˆغŒط±ط§غŒط´ ظˆ ظ…ط¯غŒط±غŒطھ ط¢غŒطھظ…â€Œظ‡ط§</a>
                                    <form method="post" action="{{ route('admin.menus.destroy', $menu) }}" class="inline" onsubmit="return confirm('ط­ط°ظپ ط§غŒظ† ظ…ظ†ظˆطں');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">ط­ط°ظپ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-0">
                                <div class="admin-empty-state">
                                    <x-core-ui::icon name="menu" class="admin-empty-state__icon" />
                                    <p class="admin-empty-state__title">ظ‡ظ†ظˆط² ظ…ظ†ظˆغŒغŒ ط§غŒط¬ط§ط¯ ظ†ط´ط¯ظ‡ ط§ط³طھ</p>
                                    <p class="admin-empty-state__text">ط¨ط§ آ«ظ…ظ†ظˆغŒ ط¬ط¯غŒط¯آ» غŒع© ظ…ظ†ظˆ ط¨ط±ط§غŒ ظ…ظˆظ‚ط¹غŒطھ ظ…ظˆط±ط¯ ظ†ط¸ط± ط¨ط³ط§ط²غŒط¯.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="admin-section-lead" style="margin-top:0.5rem">ظ…ظˆظ‚ط¹غŒطھâ€Œظ‡ط§: ظ…ظ†ظˆغŒ ط§طµظ„غŒ (ظ‡ط¯ط±)طŒ ظپظˆطھط±طŒ ظ…ظˆط¨ط§غŒظ„طŒ ظ†ظˆط§ط± ط¨ط§ظ„ط§. ط§ع¯ط± ط¨ط±ط§غŒ ظ…ظˆظ‚ط¹غŒطھ آ«ظ…ظ†ظˆغŒ ط§طµظ„غŒآ» ظ…ظ†ظˆ ط¨ط³ط§ط²غŒط¯طŒ ط¯ط± ظ‡ط¯ط± ط³ط§غŒطھ ظ†ظ…ط§غŒط´ ط¯ط§ط¯ظ‡ ظ…غŒâ€Œط´ظˆط¯ط› ط¯ط± ط؛غŒط± ط§غŒظ† طµظˆط±طھ ط§ط² ظ…ظ†ظˆغŒ ظ¾ظ„طھظپط±ظ… ط§ط³طھظپط§ط¯ظ‡ ظ…غŒâ€Œط´ظˆط¯.</p>
</div>
@endsection



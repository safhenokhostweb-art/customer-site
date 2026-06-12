@extends('admin.layout')
@section('title', 'ط§ط¹ظ„ط§ظ†â€Œظ‡ط§')
@section('header', 'ط§ط¹ظ„ط§ظ†â€Œظ‡ط§')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">ط§ط¹ظ„ط§ظ†â€Œظ‡ط§ ظˆ ظ†ظˆط§ط± ظ¾غŒط§ظ…</h1>
            <p class="text-slate-600 text-sm mt-1">ظ…ط¯غŒط±غŒطھ ط§ط¹ظ„ط§ظ†â€Œظ‡ط§غŒ ظ†ظˆط§ط± ط§ط·ظ„ط§ط¹â€Œط±ط³ط§ظ†غŒ (طھظˆظ„ط¨ط§ط±) ط¨ط±ط§غŒ ط¨ط§ط²ط¯غŒط¯ع©ظ†ظ†ط¯ع¯ط§ظ† ط³ط§غŒطھ ظˆ ط§ط¹ظ„ط§ظ†â€Œظ‡ط§غŒ ط¯ط§ط®ظ„غŒ ع©ط§ط±ط¨ط±ط§ظ†.</p>
            <p class="text-slate-500 text-xs mt-1">
                ط§ط¹ظ„ط§ظ†â€Œظ‡ط§غŒ ط§ط¯ظ…غŒظ† ط§غŒظ† ط³ط§غŒطھ ط§ط² ظ¾ظ„طھظپط±ظ… ط§طµظ„غŒ (saas-platform) ط¯ط± ط¯ع©ظ…ظ‡ظ” ط²ظ†ع¯ظˆظ„ظ‡ظ” ظ¾ط§غŒغŒظ† ط±ط§ط³طھ ظ¾ظ†ظ„ ظ…ط¯غŒط±غŒطھ ظ†ظ…ط§غŒط´ ط¯ط§ط¯ظ‡ ظ…غŒâ€Œط´ظˆظ†ط¯.
                ط¨ط±ط§غŒ ظ‡ظ…ع¯ط§ظ…â€Œط³ط§ط²غŒ ط§ط¹ظ„ط§ظ†â€Œظ‡ط§غŒ ط§ط¯ظ…غŒظ† ط§ط² ظ¾ظ„طھظپط±ظ…طŒ ط¯ط±
                <a href="{{ route('admin.license.index') }}" class="text-indigo-600 hover:underline">طµظپط­ظ‡ظ” ظ„ط§غŒط³ظ†ط³</a>
                ط±ظˆغŒ آ«ط¨ط§ط²ط®ظˆط§ظ†غŒ طھظ†ط¸غŒظ…ط§طھ ظˆ ط§ط¹ظ„ط§ظ†â€Œظ‡ط§آ» ع©ظ„غŒع© ع©ظ†غŒط¯.
            </p>
        </div>
        <div class="admin-page-tools">
            <a href="{{ route('admin.notifications.send-users.form') }}" class="btn btn-secondary">
                <x-core-ui::icon name="message" class="w-4 h-4" />
                ط§ط±ط³ط§ظ„ ط§ط¹ظ„ط§ظ† ط¨ظ‡ ع©ط§ط±ط¨ط±ط§ظ†
            </a>
            @if($canManage)
                <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary">
                    <x-core-ui::icon name="message" class="w-4 h-4" />
                    ط§ط¹ظ„ط§ظ† ط¬ط¯غŒط¯ (طھظˆظ„ط¨ط§ط±)
                </a>
            @else
                <button type="button" class="btn btn-primary opacity-60 cursor-not-allowed" disabled>
                    ط§ط¹ظ„ط§ظ† ط¬ط¯غŒط¯ (ظ„ط§غŒط³ظ†ط³ ظ…ظ†ظ‚ط¶غŒ)
                </button>
            @endif
        </div>
    </div>

<details class="mb-6 bg-white border border-slate-200 rounded-xl">
    <summary class="px-4 py-3 cursor-pointer flex items-center justify-between">
        <div class="flex items-center gap-2">
            <x-core-ui::icon name="message" class="w-4 h-4 text-indigo-600" />
            <span class="font-semibold text-sm">ط±ط§ظ‡ظ†ظ…ط§غŒ ظ…ط§عکظˆظ„ ط§ط¹ظ„ط§ظ†â€Œظ‡ط§</span>
        </div>
        <span class="text-xs text-slate-500">ع©ظ„غŒع© ط¨ط±ط§غŒ ط¨ط§ط² ظˆ ط¨ط³طھظ‡ ع©ط±ط¯ظ†</span>
    </summary>
    <div class="px-4 pb-4 pt-1 text-sm text-slate-600 space-y-2">
        <p>ط§غŒظ† ظ…ط§عکظˆظ„ ط¯ظˆ ظ†ظˆط¹ ط§ط¹ظ„ط§ظ† ط±ط§ ظ…ط¯غŒط±غŒطھ ظ…غŒâ€Œع©ظ†ط¯: غ±) ط§ط¹ظ„ط§ظ†â€Œظ‡ط§غŒ ظ†ظˆط§ط± ط¨ط§ظ„ط§غŒ ط³ط§غŒطھ (طھظˆظ„ط¨ط§ط±) ط¨ط±ط§غŒ ظ‡ظ…ظ‡ظ” ط¨ط§ط²ط¯غŒط¯ع©ظ†ظ†ط¯ع¯ط§ظ†طŒ غ²) ط§ط¹ظ„ط§ظ†â€Œظ‡ط§غŒ ط¯ط§ط®ظ„ آ«ط­ط³ط§ط¨ ظ…ظ†آ» ط¨ط±ط§غŒ ع©ط§ط±ط¨ط±ط§ظ† ط«ط¨طھâ€Œظ†ط§ظ…â€Œع©ط±ط¯ظ‡.</p>
        <ul class="list-disc pr-5 space-y-1 text-slate-600">
            <li>ط¨ط±ط§غŒ ط§ط¹ظ„ط§ظ†â€Œظ‡ط§غŒ ط¹ظ…ظˆظ…غŒ ط³ط§غŒطھ (ط¨ظ†ط± ط§ط·ظ„ط§ط¹غŒظ‡طŒ طھط®ظپغŒظپطŒ ظ‡ط´ط¯ط§ط± ط³ط±ظˆغŒط³)طŒ ط§ط² ظ„غŒط³طھ طھظˆظ„ط¨ط§ط± ط¯ط± ظ‡ظ…غŒظ† طµظپط­ظ‡ ط§ط³طھظپط§ط¯ظ‡ ع©ظ†غŒط¯.</li>
            <li>ط¨ط±ط§غŒ ط§ط±ط³ط§ظ„ ظ¾غŒط§ظ… ط¨ظ‡ ع©ط§ط±ط¨ط±ط§ظ† ط®ط§طµ (ظ…ط«ظ„ط§ظ‹ ظپظ‚ط· ط®ط±غŒط¯ط§ط±ط§ظ† غŒط§ ع©ط§ط±ط¨ط±ط§ظ† ط«ط¨طھâ€Œظ†ط§ظ…â€Œط´ط¯ظ‡ ط¯ط± ط¨ط§ط²ظ‡ظ” ط²ظ…ط§ظ†غŒ ظ…ط´ط®طµ)طŒ ط§ط² ط¯ع©ظ…ظ‡ظ” آ«ط§ط±ط³ط§ظ„ ط§ط¹ظ„ط§ظ† ط¨ظ‡ ع©ط§ط±ط¨ط±ط§ظ†آ» ط§ط³طھظپط§ط¯ظ‡ ع©ظ†غŒط¯.</li>
            <li>طھط§ط±غŒط®â€Œظ‡ط§ ط±ط§ ظ‡ظ…غŒط´ظ‡ ط¨ظ‡â€Œطµظˆط±طھ ط´ظ…ط³غŒ ظˆط§ط±ط¯ ع©ظ†غŒط¯ط› ط³غŒط³طھظ… ط¨ظ‡â€Œط·ظˆط± ط®ظˆط¯ع©ط§ط± ط¢ظ†â€Œظ‡ط§ ط±ط§ ط¨ظ‡ ظپط±ظ…طھ ط¯غŒطھط§ط¨غŒط³ طھط¨ط¯غŒظ„ ظ…غŒâ€Œع©ظ†ط¯.</li>
            <li>ط¨ظ‡â€Œط¬ط§غŒ ط­ط°ظپ ط§ط¹ظ„ط§ظ†â€Œظ‡ط§غŒ ظ‚ط¯غŒظ…غŒطŒ طھط±ط¬غŒط­ط§ظ‹ ط¢ظ†â€Œظ‡ط§ ط±ط§ ط؛غŒط±ظپط¹ط§ظ„ ع©ظ†غŒط¯ طھط§ طھط§ط±غŒط®ع†ظ‡ظ” ع©ظ…ظ¾غŒظ†â€Œظ‡ط§ ظˆ ط§ط·ظ„ط§ط¹غŒظ‡â€Œظ‡ط§ ط­ظپط¸ ط´ظˆط¯.</li>
        </ul>
    </div>
</details>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div class="card">
        <div class="card-body">
            <div class="text-xs text-slate-500 mb-1">طھط¹ط¯ط§ط¯ ع©ظ„ ط§ط¹ظ„ط§ظ†â€Œظ‡ط§غŒ طھظˆظ„ط¨ط§ط±</div>
            <div class="text-2xl font-bold text-slate-800">{{ number_format($stats['total'] ?? 0) }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="text-xs text-slate-500 mb-1">ط§ط¹ظ„ط§ظ†â€Œظ‡ط§غŒ ظپط¹ط§ظ„</div>
            <div class="text-2xl font-bold text-emerald-700">{{ number_format($stats['active'] ?? 0) }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="text-xs text-slate-500 mb-1">ظˆط¶ط¹غŒطھ ظ„ط§غŒط³ظ†ط³ ط§ط¹ظ„ط§ظ†â€Œظ‡ط§</div>
            @php
                $licenseValid = $license && $license->isValid();
            @endphp
            @if($license && is_array($license->modules ?? []) && in_array('notifications', $license->modules, true))
                @if($licenseValid)
                    <div class="text-sm text-emerald-700 font-semibold">ظپط¹ط§ظ„ ظˆ ظ‚ط§ط¨ظ„ ظ…ط¯غŒط±غŒطھ</div>
                @else
                    <div class="text-sm text-amber-700 font-semibold">ظ…ط§عکظˆظ„ ظ‚ط¨ظ„ط§ظ‹ ظپط¹ط§ظ„ ط¨ظˆط¯ظ‡ط› ظ„ط§غŒط³ظ†ط³ ظپط¹ظ„غŒ ظ…ط¹طھط¨ط± ظ†غŒط³طھ (ظپظ‚ط· ظ…ط´ط§ظ‡ط¯ظ‡ ظ…ط¬ط§ط² ط§ط³طھ).</div>
                @endif
            @else
                <div class="text-sm text-slate-500">ط§غŒظ† ظ…ط§عکظˆظ„ ط¯ط± ظ„ط§غŒط³ظ†ط³ ظپط¹ظ„غŒ ط«ط¨طھ ظ†ط´ط¯ظ‡ ط§ط³طھ.</div>
            @endif
        </div>
    </div>
</div>

<div class="card mb-4 admin-sticky-filters admin-hover-lift">
    <div class="card-body">
        <form method="get" action="{{ route('admin.notifications.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="form-group mb-0">
                <label class="form-label">ط¬ط³طھط¬ظˆ ط¯ط± ط¹ظ†ظˆط§ظ†/ظ…طھظ†</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control w-56" placeholder="ظ…ط«ظ„ط§ظ‹ طھط®ظپغŒظپطŒ ط§ط·ظ„ط§ط¹غŒظ‡ ظ…ظ‡ظ…...">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">ظˆط¶ط¹غŒطھ</label>
                <select name="status" class="form-control w-40">
                    <option value="">ظ‡ظ…ظ‡</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ظپظ‚ط· ظپط¹ط§ظ„</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>ظپظ‚ط· ط؛غŒط±ظپط¹ط§ظ„</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">ط§ط² طھط§ط±غŒط® (ط´ظ…ط³غŒ)</label>
                <x-persian-date-input name="from" :value="request('from')" placeholder="غ±غ´غ°غ³/غ°غ±/غ°غ±" class="form-control w-36" />
            </div>
            <div class="form-group mb-0">
                <label class="form-label">طھط§ طھط§ط±غŒط® (ط´ظ…ط³غŒ)</label>
                <x-persian-date-input name="to" :value="request('to')" placeholder="غ±غ´غ°غ³/غ±غ²/غ²غ¹" class="form-control w-36" />
            </div>
            <div class="form-group mb-0">
                <label class="form-label">ظ…ط±طھط¨â€Œط³ط§ط²غŒ</label>
                <select name="sort" class="form-control w-40">
                    <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>ط¬ط¯غŒط¯طھط±غŒظ† ط§ط¨طھط¯ط§</option>
                    <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>ظ‚ط¯غŒظ…غŒâ€Œطھط±غŒظ† ط§ط¨طھط¯ط§</option>
                </select>
            </div>
            <div class="flex items-center gap-2 mt-2">
                <button type="submit" class="btn btn-primary btn-sm">ط§ط¹ظ…ط§ظ„ ظپغŒظ„طھط±</button>
                <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary btn-sm">ظ¾ط§ع© ع©ط±ط¯ظ†</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('admin.notifications.bulk') }}">
            @csrf
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <div class="text-sm text-slate-600">ط§ط¹ظ„ط§ظ†â€Œظ‡ط§غŒ طھظˆظ„ط¨ط§ط± ط³ط§غŒطھ</div>
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <label class="flex items-center gap-1">
                        <input type="radio" name="action" value="activate" class="mr-1" checked>
                        <span>ظپط¹ط§ظ„ ع©ط±ط¯ظ† ط§ظ†طھط®ط§ط¨â€Œط´ط¯ظ‡â€Œظ‡ط§</span>
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="radio" name="action" value="deactivate" class="mr-1">
                        <span>ط؛غŒط±ظپط¹ط§ظ„ ع©ط±ط¯ظ† ط§ظ†طھط®ط§ط¨â€Œط´ط¯ظ‡â€Œظ‡ط§</span>
                    </label>
                    @if(!$canManage)
                        <span class="text-amber-600 ml-2">ظ„ط§غŒط³ظ†ط³ ظ…ظ†ظ‚ط¶غŒ ط§ط³طھط› ط§ط¹ظ…ط§ظ„ ع¯ط±ظˆظ‡غŒ ط؛غŒط±ظپط¹ط§ظ„ ط´ط¯ظ‡ ط§ط³طھ.</span>
                    @endif
                    <button type="submit" class="btn btn-secondary btn-sm {{ $canManage ? '' : 'opacity-50 cursor-not-allowed' }}" {{ $canManage ? '' : 'disabled' }}>ط§ط¹ظ…ط§ظ„ ط¨ط±ع¯ط²غŒط¯ظ‡â€Œظ‡ط§</button>
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                    <tr>
                        <th style="width:40px">
                            <input type="checkbox" onclick="document.querySelectorAll('.notif-row-checkbox').forEach(cb => cb.checked = this.checked);">
                        </th>
                        <th>ط¹ظ†ظˆط§ظ†</th>
                        <th class="hidden md:table-cell">ظ…ط®ط§ط·ط¨</th>
                        <th>ظ†ظˆط¹</th>
                        <th>ظ„ظˆع¯ظˆغŒ ظ„ط±ط²ط§ظ†</th>
                        <th>ط¨ط§ط²ظ‡ ظ†ظ…ط§غŒط´</th>
                        <th>ظˆط¶ط¹غŒطھ</th>
                        <th>ظ†ظ…ط§غŒط´ ط¯ط± ط³ط§غŒطھ</th>
                        <th style="width:200px">ط¹ظ…ظ„غŒط§طھ</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($notifications as $n)
                        <tr>
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $n->id }}" class="notif-row-checkbox">
                            </td>
                            <td class="font-medium">{{ $n->title }}</td>
                            <td class="hidden md:table-cell"><span class="badge badge-success">ع©ط§ط±ط¨ط± ظ†ظ‡ط§غŒغŒ</span></td>
                            <td>
                                @php
                                    $types = ['info' => 'ط§ط·ظ„ط§ط¹', 'success' => 'ظ…ظˆظپظ‚', 'warning' => 'ظ‡ط´ط¯ط§ط±', 'danger' => 'ظ…ظ‡ظ…'];
                                @endphp
                                <span class="badge badge-{{ $n->type === 'danger' ? 'danger' : ($n->type === 'warning' ? 'warning' : 'success') }}">{{ $types[$n->type] ?? $n->type }}</span>
                            </td>
                            <td>{{ $n->show_shaking_logo ? 'âœ“' : 'â€”' }}</td>
                            <td class="text-sm">
                                @if($n->starts_at || $n->expires_at)
                                    {{ $n->starts_at ? \App\Helpers\Jalali::formatFa($n->starts_at, 'Y/m/d H:i') : 'â€”' }}
                                    طھط§
                                    {{ $n->expires_at ? \App\Helpers\Jalali::formatFa($n->expires_at, 'Y/m/d H:i') : 'â€”' }}
                                @else
                                    ظ‡ظ…غŒط´ظ‡
                                @endif
                            </td>
                            <td>
                                @if($n->is_active)
                                    <span class="badge badge-success">ظپط¹ط§ظ„</span>
                                @else
                                    <span class="badge badge-danger">ط؛غŒط±ظپط¹ط§ظ„</span>
                                @endif
                            </td>
                            <td>
                                @if($canManage)
                                    <form method="post" action="{{ route('admin.notifications.toggle', $n) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-secondary btn-sm">
                                            {{ $n->is_active ? 'ط؛غŒط±ظپط¹ط§ظ„ ع©ظ†' : 'ظپط¹ط§ظ„ ع©ظ†' }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">ظپظ‚ط· ظ‚ط§ط¨ظ„ ظ…ط´ط§ظ‡ط¯ظ‡</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.notifications.edit', $n) }}" class="btn btn-primary btn-sm {{ $canManage ? '' : 'opacity-50 cursor-not-allowed' }}" @if(!$canManage) aria-disabled="true" @endif>ظˆغŒط±ط§غŒط´</a>
                                    @if($canManage)
                                        <form method="post" action="{{ route('admin.notifications.destroy', $n) }}" class="inline" onsubmit="return confirm('ط­ط°ظپ ط§غŒظ† ط§ط¹ظ„ط§ظ†طں ط§غŒظ† ع©ط§ط± ط±ظˆغŒ ط§ط¹ظ„ط§ظ†â€Œظ‡ط§غŒ ع©ط§ط±ط¨ط± طھط§ط«غŒط±غŒ ظ†ط¯ط§ط±ط¯طŒ ط§ظ…ط§ ط¨ظ†ط± ظ…ط±ط¨ظˆط·ظ‡ ط¯ط± ط³ط§غŒطھ ظ†ظ…ط§غŒط´ ط¯ط§ط¯ظ‡ ظ†ط®ظˆط§ظ‡ط¯ ط´ط¯.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">ط­ط°ظپ</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="admin-empty">
                                ظ‡ظ†ظˆط² ط§ط¹ظ„ط§ظ†غŒ ط«ط¨طھ ظ†ط´ط¯ظ‡ ط§ط³طھ.
                                @if($canManage)
                                    <a href="{{ route('admin.notifications.create') }}" class="text-indigo-600 hover:underline">ط§ظˆظ„غŒظ† ط§ط¹ظ„ط§ظ† ط±ط§ ط§غŒط¬ط§ط¯ ع©ظ†غŒط¯</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>
    @if($notifications->hasPages())
        <div class="admin-pagination">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection



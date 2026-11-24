@extends('admin.layout')
@section('title', 'صفحات')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">صفحات</h1>
        <div class="admin-page-tools">
            <a href="{{ route('admin.pages.create') }}" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align:-2px;margin-left:4px"><path d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2z"/></svg>
                صفحه جدید
            </a>
        </div>
    </div>

    {{-- Bulk action toolbar --}}
    <div id="bulk-toolbar" style="display:none;background:#f0f4ff;border:1px solid #c7d2fe;border-radius:10px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <span id="bulk-count" style="font-size:13px;color:#4338ca;font-weight:600;min-width:90px;"></span>

        {{-- Bulk status --}}
        <form id="form-bulk-status" method="POST" action="{{ route('admin.pages.bulk-status') }}" style="display:flex;gap:8px;align-items:center;">
            @csrf
            <div id="bulk-ids-status"></div>
            <select name="status" style="border:1px solid #c7d2fe;border-radius:7px;padding:6px 10px;font-size:13px;background:#fff;color:#374151;cursor:pointer;outline:none;">
                <option value="published">منتشر شده</option>
                <option value="draft">پیش‌نویس</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm" style="padding:6px 14px;font-size:13px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" style="vertical-align:-2px;margin-left:4px"><path d="M12.354 4.354a.5.5 0 0 0-.708-.708L5 10.293 1.854 7.146a.5.5 0 1 0-.708.708l3.5 3.5a.5.5 0 0 0 .708 0l7-7z"/></svg>
                تغییر وضعیت
            </button>
        </form>

        {{-- Bulk delete --}}
        <form id="form-bulk-delete" method="POST" action="{{ route('admin.pages.bulk-delete') }}" style="display:flex;align-items:center;" onsubmit="return confirmBulkDelete()">
            @csrf
            <div id="bulk-ids-delete"></div>
            <button type="submit" class="btn btn-danger btn-sm" style="padding:6px 14px;font-size:13px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" style="vertical-align:-2px;margin-left:4px"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                حذف انتخاب‌شده‌ها
            </button>
        </form>

        <button type="button" onclick="clearSelection()" style="margin-right:auto;background:none;border:none;color:#6b7280;cursor:pointer;font-size:13px;padding:4px 8px;border-radius:6px;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='none'">
            لغو انتخاب
        </button>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width:40px;text-align:center;">
                            <input type="checkbox" id="check-all" title="انتخاب همه"
                                style="width:16px;height:16px;cursor:pointer;accent-color:#4f46e5;">
                        </th>
                        <th>عنوان</th>
                        <th>نامک</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $p)
                        <tr id="row-{{ $p->id }}">
                            <td style="text-align:center;">
                                <input type="checkbox" class="row-check" value="{{ $p->id }}"
                                    style="width:16px;height:16px;cursor:pointer;accent-color:#4f46e5;">
                            </td>
                            <td>
                                <span style="font-weight:500;">{{ $p->title }}</span>
                                @if($p->is_protected)
                                    <span title="صفحه قالب" style="display:inline-flex;align-items:center;background:#fef3c7;color:#92400e;font-size:11px;padding:1px 7px;border-radius:20px;margin-right:6px;gap:3px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="currentColor" viewBox="0 0 16 16"><path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2m3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2"/></svg>
                                        قالب
                                    </span>
                                @endif
                            </td>
                            <td><code style="font-size:12px;color:#6b7280;background:#f3f4f6;padding:2px 6px;border-radius:4px;">{{ $p->slug }}</code></td>
                            <td>
                                @if($p->is_published)
                                    <span style="display:inline-flex;align-items:center;gap:4px;background:#d1fae5;color:#065f46;font-size:12px;padding:3px 10px;border-radius:20px;font-weight:500;">
                                        <span style="width:6px;height:6px;border-radius:50%;background:#10b981;display:inline-block;"></span>
                                        منتشر شده
                                    </span>
                                @else
                                    <span style="display:inline-flex;align-items:center;gap:4px;background:#f3f4f6;color:#6b7280;font-size:12px;padding:3px 10px;border-radius:20px;font-weight:500;">
                                        <span style="width:6px;height:6px;border-radius:50%;background:#9ca3af;display:inline-block;"></span>
                                        پیش‌نویس
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;align-items:center;">
                                    @if($p->slug && !str_contains($p->slug, '{') && !str_contains($p->slug, '}'))
                                    <a href="{{ url('/page/' . rawurlencode($p->slug)) }}" class="btn btn-secondary btn-sm" target="_blank" title="مشاهده در سایت">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0"/><path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7"/></svg>
                                    </a>
                                    @endif
                                    <a href="{{ route('admin.pages.edit', $p) }}" class="btn btn-secondary btn-sm" title="ویرایش مشخصات (عنوان/سئو)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/></svg>
                                    </a>
                                    <a href="{{ route('admin.theme-design.canvas-edit', ['page' => $p->slug]) }}" class="btn btn-primary btn-sm" title="طراحی ظاهر با Canvas">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zm12-1a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2z"/><path d="M5 4a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1zm0 3a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1zm0 3a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1z"/></svg>
                                        <span style="margin-right:3px;font-size:11px;">طراحی Canvas</span>
                                    </a>
                                    <form method="post" action="{{ route('admin.pages.destroy', $p) }}" style="display:inline;" onsubmit="return confirm('صفحه «{{ $p->title }}» حذف شود؟');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="حذف">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="admin-empty">صفحه‌ای ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(method_exists($pages, 'links'))
        <div class="admin-pagination">{{ $pages->links() }}</div>
    @endif
</div>

<script>
(function () {
    const checkAll   = document.getElementById('check-all');
    const toolbar    = document.getElementById('bulk-toolbar');
    const countLabel = document.getElementById('bulk-count');
    const idsStatus  = document.getElementById('bulk-ids-status');
    const idsDelete  = document.getElementById('bulk-ids-delete');

    function getChecked() {
        return [...document.querySelectorAll('.row-check:checked')].map(c => c.value);
    }

    function syncHiddenInputs(ids) {
        [idsStatus, idsDelete].forEach(container => {
            container.innerHTML = '';
            ids.forEach(id => {
                const inp = document.createElement('input');
                inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
                container.appendChild(inp);
            });
        });
    }

    function updateToolbar() {
        const ids = getChecked();
        if (ids.length > 0) {
            toolbar.style.display = 'flex';
            countLabel.textContent = ids.length + ' مورد انتخاب شد';
            syncHiddenInputs(ids);
        } else {
            toolbar.style.display = 'none';
        }
        checkAll.indeterminate = ids.length > 0 && ids.length < document.querySelectorAll('.row-check').length;
        checkAll.checked = ids.length > 0 && ids.length === document.querySelectorAll('.row-check').length;
    }

    checkAll.addEventListener('change', function () {
        document.querySelectorAll('.row-check').forEach(c => c.checked = this.checked);
        updateToolbar();
    });

    document.querySelectorAll('.row-check').forEach(c => {
        c.addEventListener('change', updateToolbar);
    });

    window.clearSelection = function () {
        document.querySelectorAll('.row-check').forEach(c => c.checked = false);
        checkAll.checked = false;
        updateToolbar();
    };

    window.confirmBulkDelete = function () {
        const count = getChecked().length;
        return confirm(count + ' صفحه حذف شود؟ این عمل قابل بازگشت نیست.');
    };
})();
</script>
@endsection

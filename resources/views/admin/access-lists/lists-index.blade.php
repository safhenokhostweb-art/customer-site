@extends('admin.layout')
@section('title', 'لیست‌های دسترسی')

@section('content')
<div class="admin-page-container">

    <div class="admin-page-header flex items-center justify-between mb-6">
        <div>
            <h1 class="admin-page-title">لیست‌های دسترسی</h1>
            <p class="admin-page-subtitle text-sm text-slate-500 mt-1">مدیریت لیست‌های سفید‌نویس براساس کد ملی یا نام خانوادگی</p>
        </div>
        <div style="display:flex;gap:.625rem;flex-wrap:wrap;align-items:center">
            <a href="{{ route('admin.access-lists.gates.index') }}" class="btn btn-secondary gap-2">
                <x-core-ui::icon name="lock" class="w-4 h-4" />
                گیت‌های دسترسی
            </a>
            <a href="{{ route('admin.access-lists.lists.create') }}" class="btn btn-primary gap-2">
                <x-core-ui::icon name="plus" class="w-4 h-4" />
                لیست جدید
            </a>
        </div>
    </div>

    @if($lists->isEmpty())
    <div class="admin-empty-state">
        <x-core-ui::icon name="list" class="w-14 h-14 text-indigo-200 mx-auto mb-4" />
        <p class="text-slate-500 font-medium">هنوز لیستی ایجاد نشده</p>
        <a href="{{ route('admin.access-lists.lists.create') }}" class="btn btn-primary btn-sm mt-3">ایجاد اولین لیست</a>
    </div>
    @else
    <div class="admin-card overflow-hidden">
        <div style="overflow-x:auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>نام لیست</th>
                        <th>نوع تطابق</th>
                        <th>تعداد ردیف</th>
                        <th>وضعیت</th>
                        <th>تاریخ ایجاد</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lists as $list)
                    @php
                        $mtMap = ['national_id' => 'کد ملی', 'last_name' => 'نام خانوادگی', 'both' => 'کد ملی + نام خانوادگی'];
                    @endphp
                    <tr>
                        <td>
                            <div class="font-semibold text-slate-800">{{ $list->name }}</div>
                            @if($list->description)
                            <div class="text-xs text-slate-400 mt-0.5">{{ Str::limit($list->description, 60) }}</div>
                            @endif
                        </td>
                        <td><span class="badge badge-info">{{ $mtMap[$list->match_type] ?? $list->match_type }}</span></td>
                        <td class="text-center font-bold text-indigo-600">
                            {{ \App\Helpers\NumberHelper::formatFa($list->entries_count ?? $list->entry_count) }}
                        </td>
                        <td>
                            @if($list->is_active)
                                <span class="badge badge-success">فعال</span>
                            @else
                                <span class="badge badge-secondary">غیرفعال</span>
                            @endif
                        </td>
                        <td class="text-sm text-slate-500">
                            {{ \App\Helpers\Jalali::formatFa($list->created_at, 'Y/m/d') }}
                        </td>
                        <td>
                            <div style="display:flex;gap:.375rem;flex-wrap:wrap">
                                {{-- Import file --}}
                                <button type="button" onclick="toggleImportForm('{{ $list->id }}')"
                                        class="btn btn-sm btn-secondary">
                                    <x-core-ui::icon name="upload" class="w-3.5 h-3.5 ml-1" />
                                    بارگذاری
                                </button>
                                <a href="{{ route('admin.access-lists.lists.edit', $list) }}" class="btn btn-sm btn-secondary">
                                    <x-core-ui::icon name="pencil" class="w-3.5 h-3.5" />
                                </a>
                                <form method="POST" action="{{ route('admin.access-lists.lists.destroy', $list) }}"
                                      onsubmit="return confirm('این لیست و تمام ردیف‌هایش حذف شود؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <x-core-ui::icon name="trash" class="w-3.5 h-3.5" />
                                    </button>
                                </form>
                            </div>
                            {{-- Import form (hidden) --}}
                            <div id="import-form-{{ $list->id }}" style="display:none;margin-top:.75rem">
                                <form method="POST" action="{{ route('admin.access-lists.lists.import', $list) }}" enctype="multipart/form-data"
                                      style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                                    @csrf
                                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                                           style="flex:1;font-size:.78rem;border:1px solid #e2e8f0;border-radius:.5rem;padding:.35rem .5rem">
                                    <button type="submit" class="btn btn-primary btn-sm">وارد‌سازی</button>
                                </form>
                                <p class="text-xs text-slate-400 mt-1">ستون‌ها باید عنوان فارسی یا انگلیسی داشته باشند</p>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

@push('scripts')
<script>
function toggleImportForm(id) {
    var el = document.getElementById('import-form-' + id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
@endpush
@endsection

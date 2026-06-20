@extends('admin.layout')
@section('title', 'گیت‌های دسترسی')

@section('content')
<div class="admin-page-container">

    <div class="admin-page-header flex items-center justify-between mb-6">
        <div>
            <h1 class="admin-page-title">گیت‌های دسترسی</h1>
            <p class="admin-page-subtitle text-sm text-slate-500 mt-1">صفحات محافظت‌شده با تایید هویت</p>
        </div>
        <div style="display:flex;gap:.625rem;align-items:center">
            <a href="{{ route('admin.access-lists.lists.index') }}" class="btn btn-secondary gap-2">
                <x-core-ui::icon name="list" class="w-4 h-4" />
                لیست‌های دسترسی
            </a>
            <a href="{{ route('admin.access-lists.gates.create') }}" class="btn btn-primary gap-2">
                <x-core-ui::icon name="plus" class="w-4 h-4" />
                گیت جدید
            </a>
        </div>
    </div>

    @if($gates->isEmpty())
    <div class="admin-empty-state">
        <x-core-ui::icon name="lock" class="w-14 h-14 text-indigo-200 mx-auto mb-4" />
        <p class="text-slate-500 font-medium">هنوز گیتی تعریف نشده</p>
        <a href="{{ route('admin.access-lists.gates.create') }}" class="btn btn-primary btn-sm mt-3">ایجاد اولین گیت</a>
    </div>
    @else
    <div class="admin-card overflow-hidden">
        <div style="overflow-x:auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>نام گیت</th>
                        <th>Slug / آدرس</th>
                        <th>لیست متصل</th>
                        <th>مقصد</th>
                        <th>مدت جلسه</th>
                        <th>وضعیت</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gates as $gate)
                    <tr>
                        <td class="font-semibold text-slate-800">{{ $gate->name }}</td>
                        <td>
                            <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">/gate/{{ $gate->slug }}</code>
                            <a href="{{ route('access-gate.show', $gate->slug) }}" target="_blank"
                               class="text-indigo-500 hover:underline text-xs mr-2">مشاهده ↗</a>
                        </td>
                        <td class="text-sm">
                            @if($gate->accessList)
                                <span class="badge badge-info">{{ $gate->accessList->name }}</span>
                            @else
                                <span class="text-red-400 text-xs">بدون لیست</span>
                            @endif
                        </td>
                        <td class="text-xs text-slate-500 max-w-xs truncate">{{ $gate->redirect_url }}</td>
                        <td class="text-sm text-slate-600">
                            {{ \App\Helpers\NumberHelper::formatFa($gate->session_duration) }} دقیقه
                        </td>
                        <td>
                            @if($gate->is_active)
                                <span class="badge badge-success">فعال</span>
                            @else
                                <span class="badge badge-secondary">غیرفعال</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:.375rem">
                                <a href="{{ route('admin.access-lists.gates.edit', $gate) }}" class="btn btn-sm btn-secondary">
                                    <x-core-ui::icon name="pencil" class="w-3.5 h-3.5" />
                                </a>
                                <form method="POST" action="{{ route('admin.access-lists.gates.destroy', $gate) }}"
                                      onsubmit="return confirm('این گیت حذف شود؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <x-core-ui::icon name="trash" class="w-3.5 h-3.5" />
                                    </button>
                                </form>
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
@endsection

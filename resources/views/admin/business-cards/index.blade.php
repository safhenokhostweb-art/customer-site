@extends('admin.layout')
@section('title', 'کارت‌های ویزیت')
@section('header', 'کارت ویزیت دیجیتال')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <p class="text-slate-600 text-sm">مدیریت کارت‌های ویزیت دیجیتال با لینک اختصاصی.</p>
        <div class="admin-page-tools">
            <a href="{{ route('admin.business-cards.create') }}" class="btn btn-primary">
                <x-core-ui::icon name="profile" class="w- />
                کارت جدید
            </a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
            <thead>
                <tr>
                    <th>نام</th>
                    <th>عنوان</th>
                    <th>شرکت</th>
                    <th>لینک</th>
                    <th>وضعیت</th>
                    <th style="width:180px">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cards as $c)
                    <tr>
                        <td class="font-medium">{{ $c->name }}</td>
                        <td>{{ $c->title ?? '—' }}</td>
                        <td>{{ $c->company ?? '—' }}</td>
                        <td>
                            <a href="{{ route('business-card.show', $c->slug) }}" target="_blank" class="text-indigo-600 hover:underline text-sm">
                                /card/{{ $c->slug }}
                            </a>
                        </td>
                        <td>
                            @if($c->is_active)
                                <span class="badge badge-success">فعال</span>
                            @else
                                <span class="badge badge-danger">غیرفعال</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.business-cards.edit', $c) }}" class="btn btn-primary btn-sm">ویرایش</a>
                                <form method="post" action="{{ route('admin.business-cards.destroy', $c) }}" class="inline" onsubmit="return confirm('حذف این کارت؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="admin-empty">هنوز کارتی ثبت نشده است. <a href="{{ route('admin.business-cards.create') }}" class="text-indigo-600 hover:underline">اولین کارت را ایجاد کنید</a></td>
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


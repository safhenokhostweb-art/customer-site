@extends('admin.layout')
@section('title', 'پلن‌های اشتراک')
@section('header', 'پلن‌های اشتراک')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <p class="text-slate-600 text-sm">مدیریت پلن‌های عضویت و اشتراک.</p>
        <div class="admin-page-tools">
            <a href="{{ route('admin.subscriptions.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                پلن جدید
            </a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
            <thead>
                <tr>
                    <th>نام</th>
                    <th>قیمت</th>
                    <th>مدت</th>
                    <th>وضعیت</th>
                    <th style="width:180px">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $p)
                    <tr>
                        <td class="font-medium">{{ $p->name }}</td>
                        <td>{{ number_format($p->price) }} تومان</td>
                        <td>{{ $p->duration_count }} {{ $p->duration === 'month' ? 'ماه' : 'سال' }}</td>
                        <td>
                            @if($p->is_active)
                                <span class="badge badge-success">فعال</span>
                            @else
                                <span class="badge badge-danger">غیرفعال</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.subscriptions.edit', $p) }}" class="btn btn-primary btn-sm">ویرایش</a>
                                <form method="post" action="{{ route('admin.subscriptions.destroy', $p) }}" class="inline" onsubmit="return confirm('حذف این پلن؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="admin-empty">هنوز پلنی ثبت نشده است. <a href="{{ route('admin.subscriptions.create') }}" class="text-indigo-600 hover:underline">اولین پلن را ایجاد کنید</a></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($plans->hasPages())
        <div class="admin-pagination">{{ $plans->links() }}</div>
    @endif
</div>
@endsection

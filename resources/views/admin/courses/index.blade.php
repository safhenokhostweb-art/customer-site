@extends('admin.layout')
@section('title', 'دوره‌ها و کلاس‌ها')
@section('header', 'دوره‌ها و کلاس‌ها')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <p class="text-slate-600 text-sm">مدیریت دوره‌ها و کلاس‌های آموزشی با امکان ثبت‌نام.</p>
    <a href="{{ route('admin.courses.create') }}" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        دوره جدید
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>عنوان</th>
                    <th>قیمت</th>
                    <th>وضعیت</th>
                    <th style="width:180px">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($courses as $c)
                    <tr>
                        <td class="font-medium">{{ $c->title }}</td>
                        <td>{{ number_format($c->price) }} تومان</td>
                        <td>
                            @if($c->is_active)
                                <span class="badge badge-success">فعال</span>
                            @else
                                <span class="badge badge-danger">غیرفعال</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.courses.edit', $c) }}" class="btn btn-primary btn-sm">ویرایش</a>
                                <form method="post" action="{{ route('admin.courses.destroy', $c) }}" class="inline" onsubmit="return confirm('حذف این دوره؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-12 text-slate-500">هنوز دوره‌ای ثبت نشده است. <a href="{{ route('admin.courses.create') }}" class="text-indigo-600 hover:underline">اولین دوره را ایجاد کنید</a></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($courses->hasPages())
    <div class="mt-4">{{ $courses->links() }}</div>
@endif
@endsection

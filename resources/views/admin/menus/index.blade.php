@extends('admin.layout')
@section('title', 'منوها')
@section('header', 'منوها')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <p class="text-slate-600 text-sm">مدیریت منوهای هدر، فوتر، موبایل و نوار بالا.</p>
        <div class="admin-page-tools">
            <a href="{{ route('admin.menus.create') }}" class="btn btn-primary">منوی جدید</a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead><tr><th>نام</th><th>موقعیت</th><th>عملیات</th></tr></thead>
                <tbody>
                    @forelse($menus as $menu)
                        <tr>
                            <td>{{ $menu->name }}</td>
                            <td>{{ $locations[$menu->location] ?? $menu->location }}</td>
                            <td>
                                <div class="admin-page-tools">
                                    <a href="{{ route('admin.menus.edit', $menu) }}" class="btn btn-primary btn-sm">ویرایش و مدیریت آیتم‌ها</a>
                                    <form method="post" action="{{ route('admin.menus.destroy', $menu) }}" class="inline" onsubmit="return confirm('حذف این منو؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="admin-empty">هنوز منویی ایجاد نشده است. با «منوی جدید» یک منو برای موقعیت مورد نظر بسازید.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-sm text-slate-500">موقعیت‌ها: منوی اصلی (هدر)، فوتر، موبایل، نوار بالا. اگر برای موقعیت «منوی اصلی» منو بسازید، در هدر سایت نمایش داده می‌شود؛ در غیر این صورت از منوی پلتفرم استفاده می‌شود.</p>
</div>
@endsection

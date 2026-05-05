@extends('admin.layout')
@section('title', 'منوها')
@section('content')
<div class="header">
    <h1>منوها</h1>
    <a href="{{ route('admin.menus.create') }}" class="btn btn-primary">منوی جدید</a>
</div>
@if(session('success'))
    <p style="color: green; margin-bottom: 1rem;">{{ session('success') }}</p>
@endif
@if(session('error'))
    <p style="color: #b91c1c; margin-bottom: 1rem;">{{ session('error') }}</p>
@endif
<table>
    <thead><tr><th>نام</th><th>موقعیت</th><th>عملیات</th></tr></thead>
    <tbody>
        @forelse($menus as $menu)
            <tr>
                <td>{{ $menu->name }}</td>
                <td>{{ $locations[$menu->location] ?? $menu->location }}</td>
                <td>
                    <a href="{{ route('admin.menus.edit', $menu) }}" class="btn btn-primary">ویرایش و مدیریت آیتم‌ها</a>
                    <form method="post" action="{{ route('admin.menus.destroy', $menu) }}" style="display:inline;" onsubmit="return confirm('حذف این منو؟');">@csrf @method('DELETE')<button type="submit" class="btn btn-danger">حذف</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="3">هنوز منویی ایجاد نشده است. با «منوی جدید» یک منو برای موقعیت مورد نظر بسازید.</td></tr>
        @endforelse
    </tbody>
</table>
<p style="margin-top: 1rem; font-size: 0.9rem; color: #6b7280;">موقعیت‌ها: منوی اصلی (هدر)، فوتر، موبایل، نوار بالا. اگر برای موقعیت «منوی اصلی» منو بسازید، در هدر سایت نمایش داده می‌شود؛ در غیر این صورت از منوی پلتفرم استفاده می‌شود.</p>
@endsection

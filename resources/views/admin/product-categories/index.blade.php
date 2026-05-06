@extends('admin.layout')
@section('title', 'دسته‌بندی محصولات')
@section('header', 'دسته‌بندی محصولات')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">دسته‌بندی محصولات</h1>
        <div class="admin-page-tools">
            <a href="{{ route('admin.product-categories.create') }}" class="btn btn-primary">دسته جدید</a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead><tr><th>نام</th><th>نامک</th><th>ترتیب</th><th>عملیات</th></tr></thead>
                <tbody>
                    @forelse($categories as $c)
                        <tr>
                            <td>{{ $c->name }}</td>
                            <td>{{ $c->slug }}</td>
                            <td>{{ $c->order }}</td>
                            <td>
                                <a href="{{ route('admin.product-categories.edit', $c) }}" class="btn btn-primary btn-sm">ویرایش</a>
                                <form method="post" action="{{ route('admin.product-categories.destroy', $c) }}" style="display:inline;" onsubmit="return confirm('حذف شود؟');">@csrf @method('DELETE')<button type="submit" class="btn btn-danger btn-sm">حذف</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="admin-empty">دسته‌بندی محصولی ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

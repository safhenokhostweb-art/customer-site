@extends('admin.layout')
@section('title', 'ویژگی‌های فنی محصول')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">ویژگی‌های فنی (مشخصات)</h1>
        <a href="{{ route('admin.product-attributes.create') }}" class="btn btn-primary">ویژگی جدید</a>
    </div>
    <div class="card table-responsive">
        <table>
            <thead>
                <tr><th>نام</th><th>نامک</th><th>مقادیر</th><th>فیلتر</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($attributes as $a)
                    <tr>
                        <td>{{ $a->name }}</td>
                        <td><code>{{ $a->slug }}</code></td>
                        <td>{{ $a->values_count }}</td>
                        <td>{{ $a->filterable ? 'بله' : 'خیر' }}</td>
                        <td>
                            <a href="{{ route('admin.product-attributes.edit', $a) }}" class="btn btn-sm btn-primary">ویرایش</a>
                            <form action="{{ route('admin.product-attributes.destroy', $a) }}" method="post" class="inline" onsubmit="return confirm('حذف؟');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">حذف</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="admin-empty">ویژگی‌ای ثبت نشده.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

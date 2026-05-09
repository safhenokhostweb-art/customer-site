@extends('admin.layout')
@section('title', 'مجموعه‌های مطالب')
@section('header', 'مجموعه‌های مطالب')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">مجموعه‌های مطالب (سری)</h1>
        <div class="admin-page-tools">
            <a href="{{ route('admin.post-series.create') }}" class="btn btn-primary">مجموعه جدید</a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead><tr><th>عنوان</th><th>نامک</th><th>تعداد مطالب</th><th>عملیات</th></tr></thead>
                <tbody>
                    @forelse($series as $s)
                        <tr>
                            <td>{{ $s->title }}</td>
                            <td>{{ $s->slug }}</td>
                            <td>{{ $s->posts_count }}</td>
                            <td>
                                <a href="{{ route('admin.post-series.edit', $s) }}" class="btn btn-primary btn-sm">ویرایش</a>
                                <form method="post" action="{{ route('admin.post-series.destroy', $s) }}" style="display:inline;" onsubmit="return confirm('حذف شود؟');">@csrf @method('DELETE')<button type="submit" class="btn btn-danger btn-sm">حذف</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="admin-empty">مجموعه‌ای ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

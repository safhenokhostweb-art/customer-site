@extends('admin.layout')
@section('title', 'مطالب وبلاگ')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">مطالب وبلاگ</h1>
        <div class="admin-page-tools">
            <a href="{{ route('admin.posts.create') }}" class="btn btn-primary">مطلب جدید</a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead><tr><th>عنوان</th><th>دسته</th><th>وضعیت</th><th>تاریخ</th><th>عملیات</th></tr></thead>
                <tbody>
                    @forelse($posts as $p)
                        <tr>
                            <td>{{ $p->title }}</td>
                            <td>{{ $p->category?->name ?? '—' }}</td>
                            <td>{{ $p->is_published ? 'منتشر شده' : 'پیش‌نویس' }}</td>
                            <td>{{ \App\Helpers\Jalali::formatFa($p->created_at, 'Y/m/d') }}</td>
                            <td>
                                <a href="{{ route('admin.posts.edit', $p) }}" class="btn btn-primary btn-sm">ویرایش</a>
                                <form method="post" action="{{ route('admin.posts.destroy', $p) }}" style="display:inline;" onsubmit="return confirm('حذف شود؟');">@csrf @method('DELETE')<button type="submit" class="btn btn-danger btn-sm">حذف</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="admin-empty">مطلبی ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(method_exists($posts, 'links'))
        <div class="admin-pagination">{{ $posts->links() }}</div>
    @endif
</div>
@endsection

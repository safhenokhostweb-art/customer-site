@extends('admin.layout')
@section('title', 'صفحات')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">صفحات</h1>
        <div class="admin-page-tools">
            <a href="{{ route('admin.pages.create') }}" class="btn btn-primary">صفحه جدید</a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead><tr><th>عنوان</th><th>نامک</th><th>وضعیت</th><th>عملیات</th></tr></thead>
                <tbody>
                    @forelse($pages as $p)
                        <tr>
                            <td>{{ $p->title }}</td>
                            <td>{{ $p->slug }}</td>
                            <td>{{ $p->is_published ? 'منتشر شده' : 'پیش‌نویس' }}</td>
                            <td>
                                <a href="{{ route('page.show', $p->slug) }}" class="btn btn-secondary btn-sm" target="_blank">مشاهده</a>
                                <a href="{{ route('admin.pages.edit', $p) }}" class="btn btn-primary btn-sm">ویرایش</a>
                                <form method="post" action="{{ route('admin.pages.destroy', $p) }}" style="display:inline;" onsubmit="return confirm('حذف شود؟');">@csrf @method('DELETE')<button type="submit" class="btn btn-danger btn-sm">حذف</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="admin-empty">صفحه‌ای ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(method_exists($pages, 'links'))
        <div class="admin-pagination">{{ $pages->links() }}</div>
    @endif
</div>
@endsection

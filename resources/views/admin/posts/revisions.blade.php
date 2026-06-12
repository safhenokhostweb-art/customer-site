@extends('admin.layout')
@section('title', 'تاریخچه نسخه‌ها')
@section('header', 'تاریخچه نسخه‌ها')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">نسخه‌های «{{ $post->title }}»</h1>
        <div class="admin-page-tools">
            <a href="{{ route('admin.posts.edit', $post) }}" class="btn btn-secondary">بازگشت به ویرایش</a>
        </div>
    </div>

    <div class="card admin-table-wrap">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>زمان</th>
                        <th>کاربر</th>
                        <th>عنوان در نسخه</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($revisions as $rev)
                        @php $snap = $rev->snapshot ?? []; @endphp
                        <tr>
                            <td>{{ \App\Helpers\Jalali::formatFa($rev->created_at, 'Y/m/d H:i') }}</td>
                            <td>{{ $rev->user?->name ?? '—' }}</td>
                            <td>{{ $snap['title'] ?? '—' }}</td>
                            <td>
                                <details class="admin-revision-details">
                                    <summary>مشاهده خلاصه</summary>
                                    <pre dir="ltr">{{ json_encode($snap, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                </details>
                                @if(auth()->user()->hasPermission('posts_moderate'))
                                    <form method="post" action="{{ route('admin.posts.revisions.restore', [$post, $rev]) }}" class="mt-2" onsubmit="return confirm('این نسخه جایگزین محتوای فعلی شود؟');">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary btn-sm">بازگردانی این نسخه</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-0">
                                <div class="admin-empty-state">
                                    <x-core-ui::icon name="clock" class="admin-empty-state__icon" />
                                    <p class="admin-empty-state__title">نسخه‌ای ثبت نشده</p>
                                    <p class="admin-empty-state__text">با ذخیرهٔ مطلب، نسخه‌ها به‌مرور در اینجا فهرست می‌شوند.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(method_exists($revisions, 'links'))
        <div class="admin-pagination">{{ $revisions->links() }}</div>
    @endif
</div>
@endsection



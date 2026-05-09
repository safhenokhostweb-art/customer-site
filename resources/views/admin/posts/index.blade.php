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
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="جستجو عنوان/نامک/خلاصه">
                <select name="status">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="draft" @selected(request('status') === 'draft')>پیش‌نویس</option>
                    <option value="pending_review" @selected(request('status') === 'pending_review')>در انتظار بازبینی</option>
                    <option value="approved" @selected(request('status') === 'approved')>تاییدشده (منتشرنشده)</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>ردشده</option>
                    <option value="scheduled" @selected(request('status') === 'scheduled')>زمان‌بندی‌شده</option>
                    <option value="published" @selected(request('status') === 'published')>منتشرشده</option>
                </select>
                <button class="btn btn-secondary" type="submit">اعمال فیلتر</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead><tr><th>عنوان</th><th>دسته</th><th>وضعیت</th><th>زمان انتشار</th><th>آمار</th><th>کیفیت محتوا</th><th>عملیات</th></tr></thead>
                <tbody>
                    @forelse($posts as $p)
                        @php
                            $status = $p->publicationStatus();
                            $hasSeo = !empty($p->meta_title) && !empty($p->meta_description);
                            $hasContent = !empty(trim(strip_tags((string) $p->body)));
                        @endphp
                        <tr>
                            <td>
                                <div class="font-semibold">{{ $p->title }}</div>
                                <div class="text-xs text-slate-500">{{ $p->slug }}</div>
                                @if($p->tags->isNotEmpty())
                                    <div class="text-xs text-slate-500 mt-1">{{ $p->tags->pluck('name')->implode('، ') }}</div>
                                @endif
                            </td>
                            <td>{{ $p->category?->name ?? '—' }}</td>
                            <td>
                                @if($status === 'scheduled')
                                    <span class="badge badge-warning">زمان‌بندی‌شده</span>
                                @elseif($status === 'published')
                                    <span class="badge badge-success">منتشرشده</span>
                                @elseif($status === 'pending_review')
                                    <span class="badge badge-warning">بازبینی</span>
                                @elseif($status === 'approved')
                                    <span class="badge badge-success" style="background:#0d9488;">تایید شده</span>
                                @elseif($status === 'rejected')
                                    <span class="badge badge-danger">رد شده</span>
                                @else
                                    <span class="badge badge-secondary">پیش‌نویس</span>
                                @endif
                            </td>
                            <td>{{ $p->published_at ? \App\Helpers\Jalali::formatFa($p->published_at, 'Y/m/d H:i') : '—' }}</td>
                            <td class="text-xs">
                                <div>بازدید: {{ number_format($p->view_count) }}</div>
                                <div>لایک: {{ number_format($p->likes_count) }}</div>
                            </td>
                            <td class="text-xs">
                                <div>{{ $p->estimatedReadingMinutes() }} دقیقه مطالعه</div>
                                <div class="{{ $hasSeo ? 'text-emerald-700' : 'text-amber-700' }}">{{ $hasSeo ? 'SEO کامل' : 'SEO ناقص' }}</div>
                                <div class="{{ $hasContent ? 'text-emerald-700' : 'text-red-700' }}">{{ $hasContent ? 'محتوا آماده' : 'بدون محتوا' }}</div>
                            </td>
                            <td>
                                <a href="{{ route('admin.posts.edit', $p) }}" class="btn btn-primary btn-sm">ویرایش</a>
                                <form method="post" action="{{ route('admin.posts.destroy', $p) }}" style="display:inline;" onsubmit="return confirm('حذف شود؟');">@csrf @method('DELETE')<button type="submit" class="btn btn-danger btn-sm">حذف</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="admin-empty">مطلبی ثبت نشده است.</td></tr>
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

@extends('admin.layout')
@section('title', 'مطالب وبلاگ')
@section('header', 'مطالب وبلاگ')
@section('content')

@include('admin.partials.breadcrumb', ['crumbs' => [
    ['label' => 'داشبورد', 'url' => route('admin.dashboard')],
    ['label' => 'مطالب وبلاگ', 'url' => null],
]])

@include('admin.posts.guide')

<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">مطالب وبلاگ</h1>
        <div class="admin-page-tools">
            <a href="{{ route('admin.posts.create') }}" class="btn btn-primary">مطلب جدید</a>
        </div>
    </div>
    <div class="card mb-4 admin-sticky-filters">
        <div class="card-body">
            <form method="get" class="admin-filter-bar" aria-label="فیلتر مطالب">
                <div class="form-group mb-0">
                    <label class="form-label" for="posts-filter-q">جستجو</label>
                    <input id="posts-filter-q" type="search" name="q" value="{{ request('q') }}" placeholder="عنوان، نامک یا خلاصه" autocomplete="off">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label" for="posts-filter-status">وضعیت انتشار</label>
                    <select id="posts-filter-status" name="status">
                        <option value="">همه وضعیت‌ها</option>
                        <option value="draft" @selected(request('status') === 'draft')>پیش‌نویس</option>
                        <option value="pending_review" @selected(request('status') === 'pending_review')>در انتظار بازبینی</option>
                        <option value="approved" @selected(request('status') === 'approved')>تاییدشده (منتشرنشده)</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>ردشده</option>
                        <option value="scheduled" @selected(request('status') === 'scheduled')>زمان‌بندی‌شده</option>
                        <option value="published" @selected(request('status') === 'published')>منتشرشده</option>
                    </select>
                </div>
                <div class="form-group mb-0 flex items-end">
                    <button class="btn btn-secondary w-full" style="min-height:2.75rem" type="submit">اعمال فیلتر</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card admin-table-wrap">
        <div class="table-responsive">
            <table>
                <thead><tr><th>عنوان</th><th>دسته</th><th>وضعیت</th><th>زمان انتشار / زمان‌بندی (شمسی)</th><th>ثبت (شمسی)</th><th>آخرین ویرایش (شمسی)</th><th>آمار</th><th>کیفیت محتوا</th><th>عملیات</th></tr></thead>
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
                                    <span class="badge badge-scheduled">زمان‌بندی‌شده</span>
                                @elseif($status === 'published')
                                    <span class="badge badge-published">منتشرشده</span>
                                @elseif($status === 'pending_review')
                                    <span class="badge badge-pending">بازبینی</span>
                                @elseif($status === 'approved')
                                    <span class="badge badge-approved">تایید شده</span>
                                @elseif($status === 'rejected')
                                    <span class="badge badge-rejected">رد شده</span>
                                @else
                                    <span class="badge badge-draft">پیش‌نویس</span>
                                @endif
                            </td>
                            <td>{{ $p->published_at ? \App\Helpers\Jalali::formatFa($p->published_at, 'Y/m/d H:i') : '—' }}</td>
                            <td class="text-xs text-slate-600">{{ \App\Helpers\Jalali::formatFa($p->created_at, 'Y/m/d H:i') }}</td>
                            <td class="text-xs text-slate-600">@if($p->updated_at && $p->updated_at->ne($p->created_at)){{ \App\Helpers\Jalali::formatFa($p->updated_at, 'Y/m/d H:i') }}@else—@endif</td>
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
                        <tr>
                            <td colspan="9" class="p-0">
                                <div class="admin-empty-state">
                                    <svg class="admin-empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                    </svg>
                                    <p class="admin-empty-state__title">مطلبی ثبت نشده</p>
                                    <p class="admin-empty-state__text">با دکمهٔ «مطلب جدید» اولین نوشته را اضافه کنید.</p>
                                </div>
                            </td>
                        </tr>
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

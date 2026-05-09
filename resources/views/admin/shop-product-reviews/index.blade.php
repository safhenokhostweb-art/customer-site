@extends('admin.layout')
@section('title', 'نظرات خریداران')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">نظرات محصولات فروشگاه</h1>
            <p class="admin-section-lead" style="margin-top:0.35rem">مدیریت سریع تایید/رد نظرها با تمرکز روی بررسی کیفیت متن.</p>
        </div>
        <div class="admin-segmented" role="group" aria-label="فیلتر وضعیت نظر">
            <a href="{{ route('admin.shop-product-reviews.index') }}" class="btn btn-sm btn-secondary">همه</a>
            <a href="{{ route('admin.shop-product-reviews.index', ['status' => 'pending']) }}" class="btn btn-sm btn-secondary">در انتظار</a>
            <a href="{{ route('admin.shop-product-reviews.index', ['status' => 'approved']) }}" class="btn btn-sm btn-secondary">تأیید شده</a>
        </div>
    </div>
    <div class="card admin-table-wrap">
        <div class="table-responsive">
        <table aria-label="فهرست نظرات محصولات">
            <thead>
                <tr><th>محصول</th><th>امتیاز</th><th>متن</th><th>وضعیت</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($reviews as $r)
                    <tr>
                        <td>{{ $r->product->title ?? '—' }}</td>
                        <td>{{ $r->rating }}</td>
                        <td class="admin-review-snippet">{{ \Illuminate\Support\Str::limit($r->body ?? $r->title ?? '—', 120) }}</td>
                        <td><span class="badge {{ $r->approved ? 'badge-published' : 'badge-pending' }}">{{ $r->approved ? 'تأیید' : 'معلق' }}</span></td>
                        <td>
                            <div class="admin-table-actions">
                            @if(!$r->approved)
                                <form method="post" action="{{ route('admin.shop-product-reviews.update', $r) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="approved" value="1">
                                    <button type="submit" class="btn btn-sm btn-primary">تأیید</button>
                                </form>
                            @else
                                <form method="post" action="{{ route('admin.shop-product-reviews.update', $r) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="approved" value="0">
                                    <button type="submit" class="btn btn-sm btn-secondary">لغو تأیید</button>
                                </form>
                            @endif
                            <form method="post" action="{{ route('admin.shop-product-reviews.destroy', $r) }}" class="inline" onsubmit="return confirm('حذف؟');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">حذف</button>
                            </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-0"><div class="admin-empty-state"><p class="admin-empty-state__title">نظری ثبت نشده</p><p class="admin-empty-state__text">پس از ثبت نظر توسط کاربران، اینجا برای بررسی و تایید نمایش داده می‌شود.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    <div class="admin-pagination">{{ $reviews->links() }}</div>
</div>
@endsection

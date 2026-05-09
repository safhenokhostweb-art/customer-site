@extends('admin.layout')
@section('title', 'نظرات خریداران')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">نظرات محصولات فروشگاه</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.shop-product-reviews.index') }}" class="btn btn-sm btn-secondary">همه</a>
            <a href="{{ route('admin.shop-product-reviews.index', ['status' => 'pending']) }}" class="btn btn-sm btn-secondary">در انتظار</a>
            <a href="{{ route('admin.shop-product-reviews.index', ['status' => 'approved']) }}" class="btn btn-sm btn-secondary">تأیید شده</a>
        </div>
    </div>
    <div class="card table-responsive">
        <table>
            <thead>
                <tr><th>محصول</th><th>امتیاز</th><th>متن</th><th>وضعیت</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($reviews as $r)
                    <tr>
                        <td>{{ $r->product->title ?? '—' }}</td>
                        <td>{{ $r->rating }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($r->body ?? $r->title ?? '—', 80) }}</td>
                        <td>{{ $r->approved ? 'تأیید' : 'معلق' }}</td>
                        <td>
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
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="admin-empty">نظری نیست.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="admin-pagination">{{ $reviews->links() }}</div>
</div>
@endsection

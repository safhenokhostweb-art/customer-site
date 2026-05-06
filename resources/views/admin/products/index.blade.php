@extends('admin.layout')
@section('title', 'محصولات')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">محصولات</h1>
        <div class="admin-page-tools">
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">محصول جدید</a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>عنوان</th><th>قیمت</th><th>موجودی</th><th>وضعیت</th><th>عملیات</th></tr>
                </thead>
                <tbody>
                    @forelse($products as $p)
                        <tr>
                            <td>{{ $p->title }}</td>
                            <td>{!! \App\Helpers\CurrencyHelper::formatPrice($p->price) !!} {{ \App\Helpers\CurrencyHelper::unitLabel() }}</td>
                            <td>{{ $p->stock }}</td>
                            <td>{{ $p->is_active ? 'فعال' : 'غیرفعال' }}</td>
                            <td>
                                <a href="{{ route('admin.products.edit', $p) }}" class="btn btn-primary btn-sm">ویرایش</a>
                                <form method="post" action="{{ route('admin.products.destroy', $p) }}" style="display:inline;" onsubmit="return confirm('حذف شود؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="admin-empty">محصولی ثبت نشده است.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(method_exists($products, 'links'))
        <div class="admin-pagination">{{ $products->links() }}</div>
    @endif
</div>
@endsection

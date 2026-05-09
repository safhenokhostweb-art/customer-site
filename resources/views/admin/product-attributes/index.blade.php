@extends('admin.layout')
@section('title', 'ویژگی‌های فنی محصول')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">ویژگی‌های فنی (مشخصات)</h1>
            <p class="admin-section-lead" style="margin-top:0.35rem">ویژگی‌ها برای فیلتر و نمایش مشخصات محصول در فروشگاه استفاده می‌شوند.</p>
        </div>
        <a href="{{ route('admin.product-attributes.create') }}" class="btn btn-primary">ویژگی جدید</a>
    </div>
    <div class="card admin-table-wrap">
        <div class="table-responsive">
        <table aria-label="فهرست ویژگی‌ها">
            <thead>
                <tr><th>نام</th><th>نامک</th><th>مقادیر</th><th>فیلتر</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($attributes as $a)
                    <tr>
                        <td>{{ $a->name }}</td>
                        <td><code>{{ $a->slug }}</code></td>
                        <td>{{ $a->values_count }}</td>
                        <td><span class="badge {{ $a->filterable ? 'badge-published' : 'badge-draft' }}">{{ $a->filterable ? 'فعال' : 'غیرفعال' }}</span></td>
                        <td>
                            <div class="admin-table-actions">
                            <a href="{{ route('admin.product-attributes.edit', $a) }}" class="btn btn-sm btn-primary">ویرایش</a>
                            <form action="{{ route('admin.product-attributes.destroy', $a) }}" method="post" class="inline" onsubmit="return confirm('حذف؟');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">حذف</button>
                            </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-0"><div class="admin-empty-state"><p class="admin-empty-state__title">ویژگی‌ای ثبت نشده است</p><p class="admin-empty-state__text">با «ویژگی جدید»، ویژگی‌هایی مثل رنگ یا سایز را ثبت کنید.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection

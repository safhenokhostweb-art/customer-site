@extends('admin.layout')
@section('title', 'کوپن‌ها')
@section('header', 'کوپن‌ها')
@section('content')
<div class="mb-4 p-4 rounded-lg bg-slate-50 border border-slate-200 text-slate-700 text-sm">
    <strong>راهنما:</strong> کدهای تخفیف در صفحهٔ سبد خرید و هنگام تکمیل سفارش قابل استفاده‌اند. حداقل مبلغ سفارش (اختیاری) تعیین می‌کند کوپن فقط برای سفارش‌های بالاتر از آن مبلغ اعمال شود.
</div>
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <p class="text-slate-600 text-sm">مدیریت کدهای تخفیف برای مشتریان.</p>
    <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        کوپن جدید
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>کد</th>
                    <th>تخفیف</th>
                    <th>حداقل سفارش</th>
                    <th>انقضا</th>
                    <th>استفاده</th>
                    <th>وضعیت</th>
                    <th style="width:180px">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($coupons as $c)
                    <tr>
                        <td><code class="px-2 py-1 bg-slate-100 rounded text-sm font-mono">{{ $c->code }}</code></td>
                        <td><span class="font-semibold text-indigo-600">{{ $c->discount_percent }}٪</span></td>
                        <td>{{ $c->min_order_amount ? number_format($c->min_order_amount) . ' تومان' : '—' }}</td>
                        <td>{{ $c->expires_at ? \App\Helpers\Jalali::formatFa($c->expires_at, 'Y/m/d') : '—' }}</td>
                        <td>{{ $c->used_count }}{{ $c->usage_limit ? ' / ' . $c->usage_limit : '' }}</td>
                        <td>
                            @if($c->is_active)
                                <span class="badge badge-success">فعال</span>
                            @else
                                <span class="badge badge-danger">غیرفعال</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.coupons.edit', $c) }}" class="btn btn-primary btn-sm">ویرایش</a>
                                <form method="post" action="{{ route('admin.coupons.destroy', $c) }}" class="inline" onsubmit="return confirm('آیا از حذف این کوپن اطمینان دارید؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-12 text-slate-500">هنوز کوپنی ثبت نشده است. <a href="{{ route('admin.coupons.create') }}" class="text-indigo-600 hover:underline">اولین کوپن را ایجاد کنید</a></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($coupons->hasPages())
    <div class="mt-4">{{ $coupons->links() }}</div>
@endif
@endsection

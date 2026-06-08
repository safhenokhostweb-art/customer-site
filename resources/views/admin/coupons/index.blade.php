@extends('admin.layout')
@section('title', 'کوپن‌ها')
@section('content')

<div class="coupon-page" style="font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif;display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-ms-flex-direction:column;flex-direction:column;gap:1.25rem">

{{-- Guide partial --}}
@include('admin.coupons.guide')

{{-- Header --}}
<div style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;-webkit-box-pack:justify;-ms-flex-pack:justify;justify-content:space-between;-ms-flex-wrap:wrap;flex-wrap:wrap;gap:.75rem">
    <div>
        <h1 style="font-size:1.25rem;font-weight:800;color:#1e293b;margin:0">کوپن‌های تخفیف</h1>
        <p style="font-size:.8rem;color:#64748b;margin:.2rem 0 0">مدیریت کدهای تخفیف فروشگاه</p>
    </div>
    <a href="{{ route('admin.coupons.create') }}"
       style="display:-webkit-inline-box;display:-ms-inline-flexbox;display:inline-flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;gap:.375rem;padding:.5rem 1rem;background:#4f46e5;color:#fff;border-radius:.625rem;font-size:.875rem;font-weight:700;text-decoration:none">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        کوپن جدید
    </a>
</div>

@if(session('success'))
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:.75rem;padding:.875rem 1.125rem;color:#15803d;font-size:.875rem;font-weight:600">
    {{ session('success') }}
</div>
@endif

{{-- Table --}}
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:1rem;overflow:hidden;-webkit-box-shadow:0 1px 4px rgba(0,0,0,.05);box-shadow:0 1px 4px rgba(0,0,0,.05);overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;font-size:.8625rem;min-width:600px">
        <thead>
            <tr>
                @foreach(['کد','نوع','مقدار','حداقل خرید','استفاده','انقضا','وضعیت',''] as $h)
                <th style="text-align:right;padding:.75rem 1rem;background:#f8fafc;font-size:.72rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0">{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($coupons as $coupon)
            <tr style="border-bottom:1px solid #f1f5f9">
                <td style="padding:.75rem 1rem;font-weight:700;color:#1e293b;font-family:monospace;direction:ltr;text-align:right">
                    {{ $coupon->code }}
                </td>
                <td style="padding:.75rem 1rem;color:#334155">
                    {{ $coupon->type === 'percent' ? 'درصدی' : 'مبلغی' }}
                </td>
                <td style="padding:.75rem 1rem;color:#334155">
                    @if($coupon->type === 'percent')
                        {{ \App\Helpers\NumberHelper::formatFa($coupon->value) }}٪
                    @else
                        {{ \App\Helpers\NumberHelper::formatFa($coupon->value) }} ت
                    @endif
                </td>
                <td style="padding:.75rem 1rem;color:#64748b;font-size:.8rem">
                    {{ $coupon->min_order_amount ? \App\Helpers\NumberHelper::formatFa($coupon->min_order_amount) . ' ت' : '—' }}
                </td>
                <td style="padding:.75rem 1rem;color:#64748b;font-size:.8rem">
                    {{ \App\Helpers\NumberHelper::formatFa($coupon->used_count ?? 0) }}
                    @if($coupon->max_uses)/ {{ \App\Helpers\NumberHelper::formatFa($coupon->max_uses) }}@endif
                </td>
                <td style="padding:.75rem 1rem;color:#64748b;font-size:.8rem">
                    {{ $coupon->expires_at ? \App\Helpers\Jalali::formatFa($coupon->expires_at, 'Y/m/d') : '—' }}
                </td>
                <td style="padding:.75rem 1rem">
                    @if($coupon->is_active)
                    <span style="display:inline-block;padding:.22rem .7rem;border-radius:9999px;font-size:.73rem;font-weight:700;background:#dcfce7;color:#15803d">فعال</span>
                    @else
                    <span style="display:inline-block;padding:.22rem .7rem;border-radius:9999px;font-size:.73rem;font-weight:700;background:#f1f5f9;color:#64748b">غیرفعال</span>
                    @endif
                </td>
                <td style="padding:.75rem 1rem">
                    <div style="display:-webkit-box;display:-ms-flexbox;display:flex;gap:.375rem;-ms-flex-wrap:wrap;flex-wrap:wrap">
                        <a href="{{ route('admin.coupons.edit', $coupon) }}"
                           style="padding:.25rem .625rem;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:.375rem;font-size:.75rem;font-weight:600;text-decoration:none">ویرایش</a>
                        <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}"
                              onsubmit="return confirm('کوپن «{{ $coupon->code }}» حذف شود؟');" style="display:inline">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    style="padding:.25rem .625rem;background:#fff1f2;color:#be123c;border:1px solid #fecdd3;border-radius:.375rem;font-size:.75rem;font-weight:600;cursor:pointer;font-family:Vazirmatn,Tahoma,sans-serif">حذف</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center;padding:3rem 1rem;color:#94a3b8">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="32" height="32" style="color:#cbd5e1;margin-bottom:.5rem"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    <br>هیچ کوپنی ثبت نشده.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($coupons->hasPages())
<div class="admin-pagination">{{ $coupons->links() }}</div>
@endif

</div>
@endsection

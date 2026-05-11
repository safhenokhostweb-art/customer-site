@extends('admin.layout')
@section('title', 'فروشگاه ماژول')
@section('header', 'فروشگاه ماژول')

@section('content')
@if(session('warning'))
    <div class="mb-4 p-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800">{{ session('warning') }}</div>
@endif

@if($error)
    <div class="mb-4 p-4 rounded-lg bg-slate-100 border border-slate-200 text-slate-700">{{ $error }}</div>
    <p class="text-sm text-slate-500">برای خرید ماژول می‌توانید مستقیماً به فروشگاه پلتفرم بروید:</p>
    <a href="{{ $platformStoreUrl ?? '#' }}" target="_blank" rel="noopener" class="btn btn-primary mt-2">رفتن به فروشگاه پلتفرم</a>
@else

<p class="text-gray-600 mb-6">ماژول‌های پولی قابل خرید را از اینجا ببینید. با کلیک روی «خرید» به صفحه پرداخت پلتفرم منتقل می‌شوید. پس از پرداخت، ماژول به‌صورت خودکار در پنل شما فعال می‌شود.</p>

@if(empty($modules) || count($modules) === 0)
    <div class="card">
        <div class="card-body text-center text-gray-500 py-12">
            در حال حاضر ماژول پولی برای خرید موجود نیست.
            <br>
            <a href="{{ $platformStoreUrl }}" target="_blank" rel="noopener" class="btn btn-secondary mt-4">مشاهده فروشگاه پلتفرم</a>
        </div>
    </div>
@else
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach($modules as $module)
            <div class="card border border-slate-200">
                <div class="card-header bg-slate-50 font-semibold">{{ $module['title'] ?? $module['slug'] }}</div>
                <div class="card-body">
                    @if(!empty($module['short_description']))
                        <p class="text-sm text-gray-600 mb-3">{{ $module['short_description'] }}</p>
                    @endif
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        @if(isset($module['display_price']) && (int) $module['display_price'] > 0)
                            <span class="font-bold text-indigo-600">{{ number_format((int) $module['display_price']) }} ریال</span>
                            @if(!empty($module['price_promo_irr']) && (int) $module['price_promo_irr'] < (int) ($module['price_irr'] ?? 0))
                                <span class="text-sm text-gray-400 line-through">{{ number_format((int) ($module['price_irr'] ?? 0)) }}</span>
                            @endif
                        @else
                            <span class="text-gray-500">رایگان</span>
                        @endif
                        @php
                            $moduleStatus = $module['status'] ?? (!empty($module['installed']) ? 'installed' : (!empty($module['owned']) ? 'owned_disabled' : 'available'));
                        @endphp
                        @if($moduleStatus === 'installed')
                            <span class="badge badge-success">نصب شده</span>
                        @elseif($moduleStatus === 'owned_disabled')
                            <span class="badge bg-amber-100 text-amber-800 border border-amber-200 px-2 py-0.5 rounded">قبلاً خریداری‌شده — غیرفعال</span>
                            <a href="{{ route('admin.modules.index') }}" class="btn btn-secondary btn-sm" title="فعال‌سازی از صفحهٔ ماژول‌های سایت">فعال‌سازی</a>
                        @else
                            <a href="{{ $platformStoreUrl }}?module={{ urlencode($module['slug'] ?? '') }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm">خرید</a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <p class="mt-4 text-sm text-gray-500">
        <a href="{{ $platformStoreUrl }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">فروشگاه کامل پلتفرم</a>
    </p>
@endif

@endif
@endsection

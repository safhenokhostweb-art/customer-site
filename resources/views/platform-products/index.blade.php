@extends('themes.default.layout')

@section('title', 'خرید لایسنس و ماژول')

@section('content')
<div class="container mx-auto px-4 py-12">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-2">خرید لایسنس و ماژول</h1>
        <p class="text-gray-600 mb-8">لایسنس سایت، ماژول‌های اضافی و پشتیبانی را از اینجا خریداری کنید.</p>

        @if(empty($products))
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-8 text-center">
                <p class="text-gray-600">در حال حاضر محصولی برای فروش وجود ندارد.</p>
            </div>
        @else
            @php
                $grouped = collect($products)->groupBy('type');
                $typeOrder = ['license_template' => 'لایسنس', 'module_addon' => 'ماژول', 'support_plan' => 'پشتیبانی'];
            @endphp

            @foreach($typeOrder as $type => $typeTitle)
                @if(isset($grouped[$type]) && $grouped[$type]->isNotEmpty())
                    <section class="mb-10">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b-2 border-indigo-200">{{ $typeTitle }}</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($grouped[$type] as $product)
                                <div class="border border-gray-200 rounded-xl p-6 bg-white shadow-sm hover:shadow-md transition-shadow">
                                    <h3 class="font-bold text-lg mb-1">{{ $product['name_fa'] ?? $product['name_en'] }}</h3>
                                    @if(!empty($product['support_plan']))
                                        <p class="text-sm text-gray-500 mb-2">
                                            {{ $product['support_plan']['title'] ?? 'پشتیبانی' }}
                                            @if(!empty($product['support_plan']['duration_days']))
                                                — {{ $product['support_plan']['duration_days'] }} روز
                                            @endif
                                        </p>
                                    @elseif(!empty($product['module']))
                                        <p class="text-sm text-gray-500 mb-2">{{ $product['module']['title'] ?? '' }}</p>
                                    @endif
                                    @if(!empty($product['description']))
                                        <p class="text-sm text-gray-600 mb-4">{{ Str::limit($product['description'], 120) }}</p>
                                    @endif
                                    <div class="flex items-center justify-between flex-wrap gap-2">
                                        <span class="text-xl font-bold text-indigo-600">
                                            {{ number_format($product['price_irr'] ?? 0) }} <span class="text-sm font-normal text-gray-500">ریال</span>
                                        </span>
                                        <a href="{{ $storeUrl }}?add={{ $product['id'] }}"
                                           class="inline-flex items-center gap-1 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 font-medium"
                                           target="_blank" rel="noopener">
                                            خرید
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach

            <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-xl text-sm text-blue-800">
                با کلیک روی «خرید» به صفحهٔ فروشگاه پنل هدایت می‌شوید. در صورت نیاز ابتدا وارد شوید.
            </div>
        @endif
    </div>
</div>
@endsection

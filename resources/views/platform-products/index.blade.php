@extends('themes.default.layout')

@section('title', 'ط®ط±غŒط¯ ظ„ط§غŒط³ظ†ط³ ظˆ ظ…ط§عکظˆظ„')

@section('content')
<div class="container mx-auto px-4 py-12">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-2">ط®ط±غŒط¯ ظ„ط§غŒط³ظ†ط³ ظˆ ظ…ط§عکظˆظ„</h1>
        <p class="text-gray-600 mb-8">ظ„ط§غŒط³ظ†ط³ ط³ط§غŒطھطŒ ظ…ط§عکظˆظ„â€Œظ‡ط§غŒ ط§ط¶ط§ظپغŒ ظˆ ظ¾ط´طھغŒط¨ط§ظ†غŒ ط±ط§ ط§ط² ط§غŒظ†ط¬ط§ ط®ط±غŒط¯ط§ط±غŒ ع©ظ†غŒط¯.</p>

        @if(empty($products))
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-8 text-center">
                <p class="text-gray-600">ط¯ط± ط­ط§ظ„ ط­ط§ط¶ط± ظ…ط­طµظˆظ„غŒ ط¨ط±ط§غŒ ظپط±ظˆط´ ظˆط¬ظˆط¯ ظ†ط¯ط§ط±ط¯.</p>
            </div>
        @else
            @php
                $grouped = collect($products)->groupBy('type');
                $typeOrder = ['license_template' => 'ظ„ط§غŒط³ظ†ط³', 'module_addon' => 'ظ…ط§عکظˆظ„', 'support_plan' => 'ظ¾ط´طھغŒط¨ط§ظ†غŒ'];
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
                                            {{ $product['support_plan']['title'] ?? 'ظ¾ط´طھغŒط¨ط§ظ†غŒ' }}
                                            @if(!empty($product['support_plan']['duration_days']))
                                                â€” {{ $product['support_plan']['duration_days'] }} ط±ظˆط²
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
                                            {{ number_format($product['price_irr'] ?? 0) }} <span class="text-sm font-normal text-gray-500">ط±غŒط§ظ„</span>
                                        </span>
                                        <a href="{{ $storeUrl }}?add={{ $product['id'] }}"
                                           class="inline-flex items-center gap-1 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 font-medium"
                                           target="_blank" rel="noopener">
                                            ط®ط±غŒط¯
                                            <x-core-ui::icon name="package" class="w- />
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach

            <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-xl text-sm text-blue-800">
                ط¨ط§ ع©ظ„غŒع© ط±ظˆغŒ آ«ط®ط±غŒط¯آ» ط¨ظ‡ طµظپط­ظ‡ظ” ظپط±ظˆط´ع¯ط§ظ‡ ظ¾ظ†ظ„ ظ‡ط¯ط§غŒطھ ظ…غŒâ€Œط´ظˆغŒط¯. ط¯ط± طµظˆط±طھ ظ†غŒط§ط² ط§ط¨طھط¯ط§ ظˆط§ط±ط¯ ط´ظˆغŒط¯.
            </div>
        @endif
    </div>
</div>
@endsection


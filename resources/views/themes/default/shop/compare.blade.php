@extends('themes.default.layout')
@section('title', 'مقایسه محصولات')
@section('breadcrumb')
    <a href="{{ route('home') }}">خانه</a><span class="mx-2">/</span>
    <a href="{{ route('shop.index') }}">فروشگاه</a><span class="mx-2">/</span><span>مقایسه</span>
@endsection
@section('content')
<h1 class="text-xl font-bold mb-4">مقایسه (حداکثر ۴ کالا)</h1>
@if($products->isEmpty())
    <p class="text-gray-600">محصولی برای مقایسه انتخاب نشده.</p>
    <a href="{{ route('shop.index') }}" class="text-indigo-600 font-semibold">بازگشت به فروشگاه</a>
@else
    <div style="overflow-x:auto;">
        <table style="border-collapse:collapse; min-width:600px; width:100%;">
            <thead><tr><th style="border:1px solid #e5e7eb;padding:0.5rem;"></th>
                @foreach($products as $p)
                    <th style="border:1px solid #e5e7eb;padding:0.5rem; text-align:right;"><a href="{{ route('shop.show', $p->slug) }}" class="font-bold text-indigo-600">{{ $p->title }}</a></th>
                @endforeach
            </tr></thead>
            <tbody>
                <tr><td style="border:1px solid #e5e7eb;padding:0.5rem;">قیمت</td>
                    @foreach($products as $p)
                        <td style="border:1px solid #e5e7eb;padding:0.5rem;">{!! \App\Helpers\CurrencyHelper::formatPrice($p->display_price) !!}</td>
                    @endforeach
                </tr>
                @php $attrNames = $products->flatMap(fn($p) => $p->attributeValues->map(fn($v) => $v->attribute->name))->unique()->values(); @endphp
                @foreach($attrNames as $aname)
                    <tr>
                        <td style="border:1px solid #e5e7eb;padding:0.5rem;">{{ $aname }}</td>
                        @foreach($products as $p)
                            @php $vals = $p->attributeValues->filter(fn($v) => $v->attribute->name === $aname)->pluck('value')->implode('، '); @endphp
                            <td style="border:1px solid #e5e7eb;padding:0.5rem;">{{ $vals ?: '—' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection

@extends('themes.default.layout')
@section('title', 'علاقه‌مندی‌ها')
@section('breadcrumb')
    <a href="{{ route('home') }}">خانه</a><span class="mx-2">/</span>
    <a href="{{ route('shop.index') }}">فروشگاه</a><span class="mx-2">/</span><span>علاقه‌مندی‌ها</span>
@endsection
@section('content')
<h1 class="text-xl font-bold mb-4">علاقه‌مندی‌ها</h1>
@if($items->isEmpty())
    <p class="text-gray-600">لیست خالی است.</p>
@else
    <div class="card-grid card-grid-3">
        @foreach($items as $wi)
            @php $p = $wi->product; @endphp
            @if($p)
                <article class="card">
                    <a href="{{ route('shop.show', $p->slug) }}" class="block font-bold mb-2">{{ $p->title }}</a>
                    <p class="text-indigo-600 font-semibold">{!! \App\Helpers\CurrencyHelper::formatPrice($p->display_price) !!}</p>
                    <form method="post" action="{{ route('shop.wishlist.toggle', $p) }}" class="mt-2">@csrf
                        <button type="submit" class="text-sm text-red-600">حذف</button>
                    </form>
                </article>
            @endif
        @endforeach
    </div>
@endif
@endsection

@extends(\App\Helpers\SiteHelper::view('layout'))
@section('title', $card->name . ' — کارت ویزیت')
@section('content')
<main>
    <div class="main-inner">
        <div class="card" style="max-width:400px;margin:2rem auto;text-align:center;padding:2rem;">
            @if($card->avatar)
                <img src="{{ $card->avatar }}" alt="{{ $card->name }}" style="width:100px;height:100px;border-radius:50%;object-fit:cover;margin-bottom:1rem;">
            @endif
            <h1>{{ $card->name }}</h1>
            @if($card->title)<p>{{ $card->title }}</p>@endif
            @if($card->company)<p>{{ $card->company }}</p>@endif
            @if($card->phone)<p><a href="tel:{{ $card->phone }}">{{ $card->phone }}</a></p>@endif
            @if($card->email)<p><a href="mailto:{{ $card->email }}">{{ $card->email }}</a></p>@endif
            @if($card->website)<p><a href="{{ $card->website }}" target="_blank" rel="noopener">{{ $card->website }}</a></p>@endif
            @if($card->address)<p>{{ $card->address }}</p>@endif
        </div>
    </div>
</main>
@endsection

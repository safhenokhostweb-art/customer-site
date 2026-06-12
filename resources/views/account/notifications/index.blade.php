@extends('account.layout')
@section('title', 'اعلان‌ها')
@section('header', 'مرکز اعلان‌ها')
@section('content')
<div class="max-w-3xl">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <p class="text-slate-600 text-sm">همهٔ اعلان‌ها و پیام‌های ارسالی از طرف سایت اینجا نمایش داده می‌شوند.</p>
        @if($notifications->whereNull('read_at')->count() > 0)
            <form method="POST" action="{{ route('account.notifications.read-all') }}" class="inline">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm">همه را خوانده‌شده کن</button>
            </form>
        @endif
    </div>

    @if($notifications->isEmpty())
        <div class="account-card">
            <div class="account-card-body text-center py-16">
                <div class="w-20 h-20 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-300 mb-4">
                    <x-core-ui::icon name="bell" class="w-10 h-10" />
                </div>
                <p class="text-slate-500 text-lg font-medium">اعلانی وجود ندارد</p>
                <p class="text-slate-400 text-sm mt-1">وقتی اعلان جدیدی برای شما ارسال شود، اینجا نمایش داده می‌شود.</p>
            </div>
        </div>
    @else
        <div class="space-y-4">
            @foreach($notifications as $n)
                @php
                    $data = $n->data ?? [];
                    $imageUrl = $data['image_url'] ?? null;
                    $ctaText = $data['cta_text'] ?? 'مشاهده';
                    $style = $data['style'] ?? $n->type;
                    $isPromo = in_array($style, ['promo', 'ad'], true);
                    $isUnread = !$n->read_at;
                @endphp
                <article class="account-card overflow-hidden {{ $isUnread ? 'ring-2 ring-indigo-200' : '' }}"
                         style="{{ $isPromo && $style === 'ad' ? 'border-left: 4px solid #4f46e5;' : '' }}">
                    <div class="account-card-body p-0">
                        @if($imageUrl)
                            <div class="w-full aspect-[21/9] sm:aspect-[3/1] bg-slate-100 overflow-hidden">
                                <img src="{{ $imageUrl }}" alt="" class="w-full h-full object-cover">
                            </div>
                        @endif
                        <div class="p-4 sm:p-5 {{ $imageUrl ? '' : '' }}">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h2 class="text-lg font-bold text-slate-900 {{ $isUnread ? '' : 'font-semibold' }}">{{ $n->title }}</h2>
                                        @if($isUnread)
                                            <span class="badge badge-info text-xs">جدید</span>
                                        @endif
                                        @if(in_array($n->type, ['promo', 'ad']))
                                            <span class="badge {{ $n->type === 'ad' ? 'badge-warning' : 'badge-success' }} text-xs">تبلیغاتی</span>
                                        @endif
                                    </div>
                                    @if($n->body)
                                        <p class="text-slate-600 mt-2 leading-relaxed">{{ $n->body }}</p>
                                    @endif
                                    <p class="text-xs text-slate-400 mt-3">{{ \App\Helpers\Jalali::formatFa($n->created_at, 'Y/m/d H:i') }}</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 shrink-0">
                                    @if(!$n->read_at)
                                        <form method="POST" action="{{ route('account.notifications.read', $n) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary btn-sm">خواندم</button>
                                        </form>
                                    @endif
                                    @if($n->link)
                                        <a href="{{ $n->link }}" class="btn btn-primary btn-sm" target="_blank" rel="noopener">{{ $ctaText }}</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-6">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection

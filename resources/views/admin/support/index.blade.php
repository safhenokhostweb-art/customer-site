@extends('admin.layout')

@section('title', 'پشتیبانی')

@section('content')
<div class="max-w-2xl mx-auto">
    <h2 class="text-xl font-bold mb-4">پشتیبانی</h2>

    {{-- وضعیت پشتیبانی --}}
    @if($license && $license->license_key)
        @php
            $supportExpires = $license->support_expires_at;
            $supportStatus = !$supportExpires ? 'no_date' : ($supportExpires->isPast() ? 'expired' : ($supportExpires->diffInDays(now(), false) <= 30 ? 'expiring_soon' : 'active'));
        @endphp
        <div class="card p-4 mb-6 {{ $supportStatus === 'expired' ? 'bg-red-50 border-red-200' : ($supportStatus === 'expiring_soon' ? 'bg-amber-50 border-amber-200' : 'bg-green-50 border-green-200') }}">
            <h3 class="font-semibold mb-2">وضعیت پشتیبانی</h3>
            <div class="flex flex-wrap items-center gap-3">
                @if($supportExpires)
                    <span class="text-slate-700">انقضا: {{ \App\Helpers\Jalali::formatFa($supportExpires, 'Y/m/d') }}</span>
                    @if($supportStatus === 'expired')
                        <span class="badge badge-danger">منقضی شده</span>
                        <a href="{{ route('platform-products.index') }}" class="text-indigo-600 text-sm hover:underline">تمدید پشتیبانی</a>
                    @elseif($supportStatus === 'expiring_soon')
                        <span class="badge badge-warning">به‌زودی منقضی</span>
                        <a href="{{ route('platform-products.index') }}" class="text-indigo-600 text-sm hover:underline">تمدید پشتیبانی</a>
                    @else
                        <span class="badge badge-success">فعال</span>
                    @endif
                @else
                    <span class="text-slate-600">تاریخ انقضا تعیین نشده</span>
                @endif
                @if($license->support_level ?? null)
                    <span class="text-slate-500 text-sm">سطح: {{ $license->support_level }}</span>
                @endif
            </div>
        </div>
    @endif

    <h3 class="text-lg font-semibold mb-4">ارسال تیکت پشتیبانی</h3>

    @if(!$license || empty($license->license_key))
        <div class="alert alert-error">
            برای ارسال تیکت پشتیبانی، ابتدا لایسنس را در <a href="{{ route('admin.license.index') }}" class="underline">بخش لایسنس</a> فعال کنید.
        </div>
    @else
        @if(session('success'))
            <div class="alert alert-success mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error mb-4">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.support.store') }}" class="card p-4">
            @csrf
            <div class="mb-3">
                <label class="form-label">ایمیل شما</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', auth()->user()->email ?? '') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">نام (اختیاری)</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', auth()->user()->name ?? '') }}">
            </div>
            <div class="mb-3">
                <label class="form-label">موضوع</label>
                <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">پیام</label>
                <textarea name="message" class="form-control" rows="5" required>{{ old('message') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">ارسال تیکت</button>
        </form>
    @endif
</div>
@endsection

@extends('admin.layout')

@section('title', 'پشتیبانی')

@section('content')
<div class="max-w-3xl mx-auto">
    <h2 class="text-xl font-bold mb-4">مرکز پشتیبانی</h2>

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

    {{-- خطا و فلش messages --}}
    @if($error)
        <div class="alert alert-error mb-4">{{ $error }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error mb-4">{{ session('error') }}</div>
    @endif

    {{-- تاریخچه تیکت‌ها --}}
    @if($tickets->isNotEmpty())
        <div class="card p-4 mb-6">
            <h3 class="font-semibold mb-3">تاریخچه تیکت‌های شما</h3>
            <div class="space-y-3">
                @foreach($tickets as $ticket)
                    <div class="border rounded p-3 {{ $ticket['status'] === 'closed' ? 'bg-gray-50' : 'bg-white' }}">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <div class="font-medium text-sm">{{ $ticket['ticket_number'] ?? '—' }} - {{ Str::limit($ticket['subject'], 50) }}</div>
                            <div class="flex gap-2 text-xs">
                                @if($ticket['status'] === 'open')
                                    <span class="badge bg-yellow-100 text-yellow-800">باز</span>
                                @elseif($ticket['status'] === 'in_progress')
                                    <span class="badge bg-blue-100 text-blue-800">در حال بررسی</span>
                                @else
                                    <span class="badge bg-gray-100 text-gray-600">بسته</span>
                                @endif
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm whitespace-pre-wrap">{{ Str::limit($ticket['message'], 150) }}</p>
                        <div class="flex justify-between items-center mt-2 text-xs text-gray-500">
                            <span>{{ \App\Helpers\Jalali::formatFa(\Carbon\Carbon::parse($ticket['created_at']), 'Y/m/d H:i') }}</span>
                            @if(count($ticket['replies']) > 0)
                                <span>{{ count($ticket['replies']) }} پاسخ</span>
                            @endif
                        </div>
                        @if(count($ticket['replies']) > 0)
                            <details class="mt-2">
                                <summary class="text-xs text-indigo-600 cursor-pointer hover:underline">مشاهده پاسخ‌ها</summary>
                                <div class="mt-2 space-y-2 pr-3 border-r-2 border-gray-200">
                                    @foreach($ticket['replies'] as $reply)
                                        <div class="text-xs {{ $reply['author_type'] === 'staff' ? 'bg-blue-50 p-2 rounded' : '' }}">
                                            <div class="flex justify-between text-gray-500">
                                                <span>{{ $reply['author_type'] === 'staff' ? 'پشتیبانی' : ($reply['customer_name'] ?? 'شما') }}</span>
                                                <span>{{ \App\Helpers\Jalali::formatFa(\Carbon\Carbon::parse($reply['created_at']), 'Y/m/d H:i') }}</span>
                                            </div>
                                            <p class="mt-1 whitespace-pre-wrap">{{ $reply['message'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- فرم ارسال تیکت --}}
    <div class="card p-4">
        <h3 class="text-lg font-semibold mb-4">ارسال تیکت جدید</h3>

        @if(!$license || empty($license->license_key))
            <div class="alert alert-error">
                برای ارسال تیکت، ابتدا لایسنس را در <a href="{{ route('admin.license.index') }}" class="underline">بخش لایسنس</a> فعال کنید.
            </div>
        @else
            <form method="POST" action="{{ route('admin.support.store') }}">
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
                    <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" required maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label">پیام</label>
                    <textarea name="message" class="form-control" rows="5" required maxlength="5000">{{ old('message') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">ارسال تیکت</button>
            </form>
        @endif
    </div>
</div>
@endsection


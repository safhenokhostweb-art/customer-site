@extends('account.layout')
@section('title', 'فعالیت‌های اخیر')
@section('header', 'فعالیت‌های اخیر')
@section('content')
<div class="space-y-6 max-w-3xl">
    <div class="account-card">
        <div class="account-card-body">
            @if($activities->isEmpty())
                <p class="text-slate-500 py-8 text-center">فعلاً فعالیتی ثبت نشده است.</p>
            @else
                <div class="space-y-3">
                    @foreach($activities as $a)
                        <div class="flex items-start gap-4 py-3 border-b border-slate-100 last:border-0">
                            <div class="shrink-0 w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-slate-800">{{ $a->description ?? $a->action }}</div>
                                <div class="text-xs text-slate-500 mt-1">{{ \App\Helpers\Jalali::formatFa($a->created_at, 'Y/m/d H:i') }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4">{{ $activities->links() }}</div>
            @endif
        </div>
    </div>
    <a href="{{ route('account.profile.index') }}" class="btn btn-secondary">بازگشت به پروفایل</a>
</div>
@endsection

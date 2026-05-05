@extends('account.layout')
@section('title', 'اشتراک‌های من')
@section('header', 'اشتراک‌های من')

@section('content')
<div class="account-card">
    <div class="account-card-body overflow-x-auto">
        @if($subscriptions->isEmpty())
            <p class="text-slate-500 text-center py-8">اشتراک فعالی ندارید.</p>
        @else
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="text-right py-3 px-4 bg-slate-50 font-semibold text-slate-600">پلن</th>
                        <th class="text-right py-3 px-4 bg-slate-50 font-semibold text-slate-600">شروع</th>
                        <th class="text-right py-3 px-4 bg-slate-50 font-semibold text-slate-600">پایان</th>
                        <th class="text-right py-3 px-4 bg-slate-50 font-semibold text-slate-600">وضعیت</th>
                        <th class="text-right py-3 px-4 bg-slate-50 font-semibold text-slate-600">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subscriptions as $sub)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="py-3 px-4 font-medium">{{ $sub->plan?->name ?? '-' }}</td>
                            <td class="py-3 px-4">{{ \App\Helpers\Jalali::formatFa($sub->starts_at, 'Y/m/d') }}</td>
                            <td class="py-3 px-4">{{ \App\Helpers\Jalali::formatFa($sub->ends_at, 'Y/m/d') }}</td>
                            <td class="py-3 px-4">
                                <span class="badge {{ $sub->isActive() ? 'badge-success' : 'badge-danger' }}">{{ $sub->isActive() ? 'فعال' : 'منقضی' }}</span>
                            </td>
                            <td class="py-3 px-4">
                                @if($sub->isActive())
                                    <form method="POST" action="{{ route('account.subscriptions.cancel', $sub) }}" class="inline" onsubmit="return confirm('آیا از لغو اشتراک اطمینان دارید؟');">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm">لغو اشتراک</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4">{{ $subscriptions->links() }}</div>
        @endif
    </div>
</div>
@endsection

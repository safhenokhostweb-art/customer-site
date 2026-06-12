@extends('account.layout')
@section('title', 'ظپط¹ط§ظ„غŒطھâ€Œظ‡ط§غŒ ط§ط®غŒط±')
@section('header', 'ظپط¹ط§ظ„غŒطھâ€Œظ‡ط§غŒ ط§ط®غŒط±')
@section('content')
<div class="space-y-6 max-w-3xl">
    <div class="account-card">
        <div class="account-card-body">
            @if($activities->isEmpty())
                <p class="text-slate-500 py-8 text-center">ظپط¹ظ„ط§ظ‹ ظپط¹ط§ظ„غŒطھغŒ ط«ط¨طھ ظ†ط´ط¯ظ‡ ط§ط³طھ.</p>
            @else
                <div class="space-y-3">
                    @foreach($activities as $a)
                        <div class="flex items-start gap-4 py-3 border-b border-slate-100 last:border-0">
                            <div class="shrink-0 w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-600">
                                <x-core-ui::icon name="activity" class="w- />
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
    <a href="{{ route('account.profile.index') }}" class="btn btn-secondary">ط¨ط§ط²ع¯ط´طھ ط¨ظ‡ ظ¾ط±ظˆظپط§غŒظ„</a>
</div>
@endsection


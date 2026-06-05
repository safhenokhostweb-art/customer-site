@extends('admin.layout')
@section('title', 'درخواست‌های اشتراک در انتظار')
@section('content')

<div class="pr-page" style="font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif">

<div class="pr-page__head">
    <div>
        <h1 class="pr-page__title">درخواست‌های در انتظار تأیید</h1>
        <p class="pr-page__sub">اشتراک‌هایی که فاکتور آن‌ها پرداخت و تأیید نشده</p>
    </div>
    <div class="pr-page__actions">
        <a href="{{ route('admin.subscriptions.dashboard') }}" class="btn btn-secondary btn-sm">داشبورد</a>
        <a href="{{ route('admin.subscribers.index') }}" class="btn btn-secondary btn-sm">همه مشترکین</a>
        <a href="{{ route('admin.subscribers.assign.form') }}" class="btn btn-primary btn-sm">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            ثبت دستی اشتراک
        </a>
    </div>
</div>


<div class="pr-table-wrap">
    <table class="pr-table">
        <thead>
            <tr>
                <th>کاربر</th>
                <th>پلن درخواستی</th>
                <th>مبلغ</th>
                <th>فاکتور</th>
                <th>تاریخ درخواست</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $req)
            <tr>
                <td>
                    <div class="pr-table__user">
                        <div class="pr-table__avatar">{{ mb_substr($req->user->name ?? $req->user->mobile, 0, 1) }}</div>
                        <div>
                            <div class="pr-table__uname">{{ $req->user->name ?? '—' }}</div>
                            <div class="pr-table__umobile" dir="ltr">{{ $req->user->mobile ?? '' }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="pr-table__plan">
                        @if($req->plan?->color)<span class="pr-plan-dot" style="background:{{ $req->plan->color }}"></span>@endif
                        {{ $req->plan->name ?? '—' }}
                    </span>
                </td>
                <td class="pr-table__amount">
                    @if($req->invoice)
                        {{ \App\Helpers\NumberHelper::formatFa($req->invoice->total) }} تومان
                    @else —
                    @endif
                </td>
                <td>
                    @if($req->invoice)
                    <span class="pr-invoice-badge pr-invoice-badge--{{ $req->invoice->status }}">
                        {{ $req->invoice->status === 'pending_confirmation' ? 'ثبت‌شده، تأیید نشده' : 'در انتظار پرداخت' }}
                    </span>
                    @else <span class="pr-table__muted">—</span>
                    @endif
                </td>
                <td class="pr-table__date">{{ \App\Helpers\Jalali::formatFa($req->created_at, 'Y/m/d H:i') }}</td>
                <td>
                    <div class="pr-table__ops">
                        {{-- تأیید و فعال‌سازی --}}
                        <form method="POST" action="{{ route('admin.subscribers.approve', $req) }}"
                              onsubmit="return confirm('اشتراک تأیید و فعال شود؟');">
                            @csrf
                            <button type="submit" class="pr-btn pr-btn--approve">تأیید و فعال‌سازی</button>
                        </form>
                        {{-- مشاهده جزئیات --}}
                        <a href="{{ route('admin.subscribers.show', $req) }}" class="pr-btn pr-btn--view">جزئیات</a>
                        {{-- حذف --}}
                        <form method="POST" action="{{ route('admin.subscribers.destroy', $req) }}"
                              onsubmit="return confirm('این درخواست حذف شود؟');">
                            @csrf @method('DELETE')
                            <button type="submit" class="pr-btn pr-btn--delete">حذف</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="pr-table__empty">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="36" height="36" style="color:#c7d2fe;margin-bottom:.625rem"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <br>هیچ درخواست در انتظاری وجود ندارد.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($requests->hasPages())
<div class="admin-pagination">{{ $requests->links() }}</div>
@endif

</div>

@push('head')
<style>
.pr-page, .pr-page * { font-family: Vazirmatn, Tahoma, 'Segoe UI', Arial, sans-serif; }
.pr-page { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: 1.125rem; }
.pr-page__head { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: justify; -ms-flex-pack: justify; justify-content: space-between; -ms-flex-wrap: wrap; flex-wrap: wrap; gap: .75rem; }
.pr-page__title { font-size: 1.25rem; font-weight: 800; color: #1e293b; margin: 0; }
.pr-page__sub { font-size: .8rem; color: #64748b; margin: .2rem 0 0; }
.pr-page__actions { display: -webkit-box; display: -ms-flexbox; display: flex; gap: .5rem; -ms-flex-wrap: wrap; flex-wrap: wrap; }

.pr-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; overflow: hidden; -webkit-box-shadow: 0 1px 4px rgba(0,0,0,.05); box-shadow: 0 1px 4px rgba(0,0,0,.05); overflow-x: auto; }
.pr-table { width: 100%; border-collapse: collapse; font-size: .8625rem; min-width: 700px; }
.pr-table th { text-align: right; padding: .75rem 1rem; background: #f8fafc; font-size: .72rem; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
.pr-table td { padding: .75rem 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.pr-table tr:last-child td { border-bottom: none; }
.pr-table tbody tr:hover td { background: #f8fafc; }

.pr-table__user { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .625rem; }
.pr-table__avatar { width: 30px; height: 30px; border-radius: 50%; background: -webkit-linear-gradient(135deg,#f59e0b,#f97316); background: linear-gradient(135deg,#f59e0b,#f97316); color: #fff; font-size: .72rem; font-weight: 700; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; -ms-flex-negative: 0; flex-shrink: 0; }
.pr-table__uname { font-weight: 600; color: #1e293b; font-size: .8625rem; }
.pr-table__umobile { font-size: .73rem; color: #94a3b8; direction: ltr; text-align: right; }
.pr-table__plan { display: -webkit-inline-box; display: -ms-inline-flexbox; display: inline-flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .375rem; font-weight: 600; color: #334155; }
.pr-plan-dot { width: 8px; height: 8px; border-radius: 50%; -ms-flex-negative: 0; flex-shrink: 0; }
.pr-table__amount { font-weight: 700; color: #1e293b; }
.pr-table__date { color: #64748b; font-size: .8rem; }
.pr-table__muted { color: #cbd5e1; }
.pr-table__empty { text-align: center; padding: 3rem 1rem; color: #94a3b8; }

.pr-invoice-badge { display: inline-block; padding: .22rem .65rem; border-radius: 9999px; font-size: .72rem; font-weight: 700; }
.pr-invoice-badge--pending { background: #fef3c7; color: #d97706; }
.pr-invoice-badge--pending_confirmation { background: #dbeafe; color: #1d4ed8; }

.pr-table__ops { display: -webkit-box; display: -ms-flexbox; display: flex; gap: .375rem; -ms-flex-wrap: wrap; flex-wrap: wrap; -webkit-box-align: center; -ms-flex-align: center; align-items: center; }
.pr-btn { display: inline-block; padding: .3rem .7rem; border-radius: .5rem; font-size: .77rem; font-weight: 700; cursor: pointer; border: 1px solid transparent; font-family: Vazirmatn, Tahoma, sans-serif; text-decoration: none; }
.pr-btn--approve { background: #dcfce7; color: #15803d; border-color: #86efac; }
.pr-btn--approve:hover { background: #bbf7d0; }
.pr-btn--view { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
.pr-btn--view:hover { background: #e2e8f0; }
.pr-btn--delete { background: #fff1f2; color: #be123c; border-color: #fecdd3; }
.pr-btn--delete:hover { background: #ffe4e6; }
</style>
@endpush

@endsection

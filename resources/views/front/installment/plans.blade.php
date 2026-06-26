@extends(\App\Helpers\SiteHelper::view('layout'))
@section('title', 'انتخاب طرح اقساطی')

@section('content')
<div style="font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif;max-width:860px;margin:2rem auto;padding:0 1rem">

    <div style="text-align:center;margin-bottom:2rem">
        <h1 style="font-size:1.5rem;font-weight:800;color:#1e293b;margin:0 0 .5rem">انتخاب طرح اقساطی</h1>
        <p style="color:#64748b;font-size:.9rem">
            مبلغ قابل پرداخت:
            <strong style="color:#6366f1">@numFa($amount) تومان</strong>
        </p>
    </div>

    @if(session('error'))
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:.75rem;padding:.875rem 1.25rem;margin-bottom:1.5rem;color:#be123c;font-size:.875rem">
        {{ session('error') }}
    </div>
    @endif

    @if($plans->isEmpty())
    <div style="background:#f8fafc;border:2px dashed #e2e8f0;border-radius:1rem;padding:2.5rem;text-align:center;color:#94a3b8">
        <svg viewBox="0 0 48 48" fill="none" style="width:48px;height:48px;margin:0 auto .75rem">
            <rect width="48" height="48" rx="12" fill="#f1f5f9"/>
            <path d="M12 24h24M24 12v24" stroke="#cbd5e1" stroke-width="3" stroke-linecap="round"/>
        </svg>
        <p style="font-size:1rem;font-weight:700;color:#475569;margin:0 0 .25rem">طرح اقساطی موجود نیست</p>
        <p style="font-size:.8rem">در حال حاضر طرح اقساطی برای این مبلغ وجود ندارد.</p>
    </div>
    @else
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem">
        @foreach($plans as $plan)
        @php $calc = $plan->calculate($amount); @endphp
        <div style="background:#fff;border:2px solid {{ $selected == $plan->id ? '#6366f1' : '#e2e8f0' }};border-radius:1rem;overflow:hidden;box-shadow:{{ $selected == $plan->id ? '0 4px 20px rgba(99,102,241,.2)' : '0 1px 4px rgba(0,0,0,.05)' }};transition:box-shadow .2s">
            <div style="padding:1.25rem 1.375rem;background:{{ $selected == $plan->id ? 'linear-gradient(135deg,#6366f1,#7c3aed)' : '#f8fafc' }};border-bottom:1px solid {{ $selected == $plan->id ? 'transparent' : '#e2e8f0' }}">
                <h3 style="font-size:1rem;font-weight:800;color:{{ $selected == $plan->id ? '#fff' : '#1e293b' }};margin:0">{{ $plan->name }}</h3>
                <p style="font-size:.75rem;color:{{ $selected == $plan->id ? 'rgba(255,255,255,.75)' : '#94a3b8' }};margin:.25rem 0 0">{{ \App\Helpers\NumberHelper::formatFa($plan->period_months) }} ماه</p>
            </div>
            <div style="padding:1.125rem 1.375rem">
                <dl style="display:grid;row-gap:.5rem;font-size:.875rem">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <dt style="color:#64748b">پیش‌پرداخت</dt>
                        <dd style="font-weight:700;color:#1e293b">@numFa($calc['down_payment']) تومان</dd>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <dt style="color:#64748b">قسط ماهانه</dt>
                        <dd style="font-weight:700;color:#6366f1">@numFa($calc['monthly_payment']) تومان</dd>
                    </div>
                    @if($calc['total_interest'] > 0)
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <dt style="color:#64748b">سود کل</dt>
                        <dd style="font-weight:700;color:#c2410c">@numFa($calc['total_interest']) تومان</dd>
                    </div>
                    @else
                    <div style="padding:.375rem .625rem;background:#f0fdf4;border-radius:.5rem;text-align:center">
                        <span style="font-size:.75rem;font-weight:700;color:#15803d">بدون سود</span>
                    </div>
                    @endif
                    <div style="border-top:1px dashed #e2e8f0;padding-top:.5rem;display:flex;justify-content:space-between;align-items:center">
                        <dt style="color:#94a3b8;font-size:.8rem">جمع کل پرداختی</dt>
                        <dd style="font-weight:800;color:#1e293b">@numFa($calc['total_payable']) تومان</dd>
                    </div>
                </dl>

                <form method="POST" action="{{ route('installment.initiate') }}" style="margin-top:1rem">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                    <input type="hidden" name="amount" value="{{ $amount }}">
                    <button type="submit" style="width:100%;padding:.65rem 1rem;background:{{ $selected == $plan->id ? 'linear-gradient(135deg,#6366f1,#7c3aed)' : '#f1f5f9' }};color:{{ $selected == $plan->id ? '#fff' : '#475569' }};border:none;border-radius:.625rem;font-size:.875rem;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:{{ $selected == $plan->id ? '0 3px 10px rgba(99,102,241,.3)' : 'none' }};transition:all .15s">
                        انتخاب این طرح
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection

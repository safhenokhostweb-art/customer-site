@extends('admin.layout')
@section('title', 'درگاه‌های پرداخت')
@section('header', 'درگاه‌های پرداخت')
@push('head')
<style>
.pg-card{background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:1.25rem 1.5rem;display:flex;align-items:center;gap:1rem;box-shadow:0 1px 4px rgba(15,23,42,.06);transition:box-shadow .2s}
.pg-card:hover{box-shadow:0 4px 16px rgba(15,23,42,.10)}
.pg-card--default{border-color:#6366f1;box-shadow:0 0 0 2px #e0e7ff}
.pg-icon{width:52px;height:52px;border-radius:.875rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.5rem}
.pg-icon--zarinpal{background:#eef2ff;color:#6366f1}
.pg-icon--idpay{background:#f0fdf4;color:#16a34a}
.pg-icon--mellat{background:#fef3c7;color:#d97706}
.pg-icon--melli{background:#fce7f3;color:#db2777}
.pg-icon--tejarat{background:#ecfdf5;color:#059669}
.pg-icon--pasargad{background:#eff6ff;color:#2563eb}
.pg-icon--parsian{background:#fdf4ff;color:#9333ea}
.pg-info{flex:1;min-width:0}
.pg-name{font-weight:700;font-size:.9375rem;color:#1e293b;margin-bottom:.15rem}
.pg-driver{font-size:.78rem;color:#64748b}
.pg-badges{display:flex;gap:.4rem;margin-top:.35rem;flex-wrap:wrap}
.badge{display:inline-flex;align-items:center;gap:.25rem;padding:.2rem .6rem;border-radius:9999px;font-size:.7rem;font-weight:600}
.badge-green{background:#dcfce7;color:#15803d}
.badge-red{background:#fee2e2;color:#dc2626}
.badge-purple{background:#ede9fe;color:#7c3aed}
.badge-gray{background:#f1f5f9;color:#64748b}
.pg-actions{display:flex;gap:.5rem;flex-shrink:0}
.btn-sm{padding:.35rem .75rem;border-radius:.5rem;font-size:.78rem;font-weight:600;cursor:pointer;border:none;transition:background .15s,opacity .15s;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem}
.btn-outline{background:#f8fafc;border:1px solid #e2e8f0;color:#475569}
.btn-outline:hover{background:#f1f5f9}
.btn-primary{background:#6366f1;color:#fff}
.btn-primary:hover{background:#4f46e5}
.btn-danger{background:#fee2e2;color:#dc2626;border:1px solid #fecaca}
.btn-danger:hover{background:#fecaca}
.btn-star{background:#fef9c3;color:#a16207;border:1px solid #fde68a}
.btn-star:hover{background:#fef08a}
.empty-state{text-align:center;padding:3rem 1rem;color:#94a3b8}
.empty-icon{font-size:3rem;margin-bottom:.75rem}
</style>
@endpush
@section('content')

@include('admin.partials.breadcrumb', ['crumbs' => [
    ['label' => 'داشبورد',       'url' => route('admin.dashboard')],
    ['label' => 'درگاه‌های پرداخت', 'url' => null],
]])

<div class="admin-page">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem">
        <div>
            <h1 style="font-size:1.25rem;font-weight:800;color:#1e293b;margin:0">درگاه‌های پرداخت</h1>
            <p style="font-size:.82rem;color:#64748b;margin:.2rem 0 0">مدیریت درگاه‌های بانکی — اطلاعات ورود رمزنگاری‌شده ذخیره می‌شوند</p>
        </div>
        <a href="{{ route('admin.payment-gateways.create') }}" class="btn-sm btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            افزودن درگاه
        </a>
    </div>

    @if(session('success'))
        <div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:.75rem;padding:.75rem 1rem;margin-bottom:1.25rem;color:#15803d;font-size:.85rem">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2;border:1px solid #fecaca;border-radius:.75rem;padding:.75rem 1rem;margin-bottom:1.25rem;color:#dc2626;font-size:.85rem">
            ✕ {{ session('error') }}
        </div>
    @endif

    {{-- Info Box --}}
    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:.875rem;padding:.875rem 1.125rem;margin-bottom:1.5rem;font-size:.82rem;color:#1e40af;display:flex;gap:.625rem;align-items:flex-start">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:.1rem"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        <span>درگاهی که <strong>پیش‌فرض</strong> علامت‌گذاری شود برای پرداخت‌های اشتراک و فاکتور استفاده می‌شود. اطلاعات ورود با کلید رمزنگاری Laravel ذخیره می‌شوند.</span>
    </div>

    {{-- Gateway List --}}
    @if($gateways->isEmpty())
        <div class="empty-state">
            <div class="empty-icon">🏦</div>
            <p style="font-weight:600;color:#475569;margin:.5rem 0 .25rem">هیچ درگاهی ثبت نشده است</p>
            <p style="font-size:.82rem;margin:0 0 1.25rem">اولین درگاه بانکی خود را اضافه کنید</p>
            <a href="{{ route('admin.payment-gateways.create') }}" class="btn-sm btn-primary" style="display:inline-flex">
                افزودن درگاه
            </a>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:.875rem">
            @foreach($gateways as $gw)
            @php
                $icons = [
                    'zarinpal' => '💳', 'idpay' => '💰',
                    'mellat'   => '🏦', 'melli' => '🏛️',
                    'tejarat'  => '🏢', 'pasargad' => '🌿',
                    'parsian'  => '🔷',
                ];
            @endphp
            <div class="pg-card {{ $gw->is_default ? 'pg-card--default' : '' }}">
                <div class="pg-icon pg-icon--{{ $gw->driver }}">{{ $icons[$gw->driver] ?? '💳' }}</div>
                <div class="pg-info">
                    <div class="pg-name">{{ $gw->name }}</div>
                    <div class="pg-driver">{{ $gw->driver_label }}</div>
                    <div class="pg-badges">
                        @if($gw->is_default)
                            <span class="badge badge-purple">⭐ پیش‌فرض</span>
                        @endif
                        @if($gw->is_active)
                            <span class="badge badge-green">● فعال</span>
                        @else
                            <span class="badge badge-red">● غیرفعال</span>
                        @endif
                        @if(!empty(($gw->config)['sandbox']))
                            <span class="badge badge-gray">🧪 آزمایشی</span>
                        @endif
                    </div>
                </div>
                <div class="pg-actions">
                    @if(!$gw->is_default)
                    <form method="POST" action="{{ route('admin.payment-gateways.set-default', $gw) }}">
                        @csrf
                        <button type="submit" class="btn-sm btn-star" title="تنظیم به‌عنوان پیش‌فرض">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            پیش‌فرض
                        </button>
                    </form>
                    @endif
                    <a href="{{ route('admin.payment-gateways.edit', $gw) }}" class="btn-sm btn-outline">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        ویرایش
                    </a>
                    @if(!$gw->is_default)
                    <form method="POST" action="{{ route('admin.payment-gateways.destroy', $gw) }}" onsubmit="return confirm('درگاه «{{ $gw->name }}» حذف شود؟')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-sm btn-danger">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                            حذف
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    @endif

    {{-- راهنما --}}
    <div style="margin-top:2rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:.875rem;padding:1.125rem 1.375rem">
        <h3 style="font-size:.875rem;font-weight:700;color:#1e293b;margin:0 0 .75rem">راهنمای درگاه‌های پشتیبانی‌شده</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.625rem">
            @foreach($drivers as $key => $label)
            <div style="display:flex;align-items:center;gap:.5rem;font-size:.8rem;color:#475569">
                <span style="width:8px;height:8px;border-radius:50%;background:#6366f1;flex-shrink:0"></span>
                <span><strong>{{ $label }}</strong></span>
            </div>
            @endforeach
        </div>
    </div>

</div>
@endsection

@extends('admin.layout')

@section('title', 'تاریخچه تیکت‌ها')

@push('head')
<style>
.form-glass-card{background:rgba(255,255,255,0.82);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.3);border-radius:1.25rem;box-shadow:0 8px 32px rgba(15,23,42,0.06)}
.form-glass-card:hover{box-shadow:0 12px 40px rgba(15,23,42,0.1)}
.form-input{width:100%!important;padding:0.75rem 1rem!important;border:1px solid #e2e8f0!important;border-radius:0.75rem!important;font-size:0.875rem!important;transition:all 0.2s ease;background:rgba(255,255,255,0.6);box-sizing:border-box}
.form-input:focus{outline:none!important;border-color:#a5b4fc!important;box-shadow:0 0 0 3px rgba(99,102,241,0.12)!important}
.form-input:hover{border-color:#c7d2fe!important}
.form-label{display:block!important;font-size:0.8125rem!important;font-weight:600!important;color:#374151!important;margin-bottom:0.5rem!important}
.btn-gradient{display:inline-flex;align-items:center;gap:0.5rem;padding:0.7rem 1.75rem;border-radius:0.75rem;font-weight:600;font-size:0.875rem;transition:all 0.25s ease;background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;border:none;cursor:pointer;box-shadow:0 4px 14px rgba(99,102,241,0.25)}
.btn-gradient:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(99,102,241,0.35)}
.btn-ghost{display:inline-flex;align-items:center;gap:0.5rem;padding:0.7rem 1.75rem;border-radius:0.75rem;font-weight:600;font-size:0.875rem;transition:all 0.2s ease;background:transparent;color:#64748b;border:1.5px solid #e2e8f0;cursor:pointer}
.btn-ghost:hover{background:#f8fafc;border-color:#cbd5e1;color:#475569}
.error-glass{background:rgba(254,242,242,0.85);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid rgba(239,68,68,0.2);border-radius:1rem;padding:1rem 1.25rem}
.ticket-card{background:rgba(255,255,255,0.82);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.3);border-radius:1.25rem;box-shadow:0 8px 32px rgba(15,23,42,0.06);padding:1.25rem;transition:all 0.3s ease}
.ticket-card:hover{box-shadow:0 12px 40px rgba(15,23,42,0.1)}
.status-badge{display:inline-flex;align-items:center;gap:0.4rem;padding:0.3rem 0.85rem;border-radius:9999px;font-size:0.75rem;font-weight:600;white-space:nowrap;border:1px solid transparent;transition:all 0.2s ease}
.status-badge:hover{transform:scale(1.05)}
.form-section{max-width:48rem;margin-left:auto;margin-right:auto}
@keyframes newReplyPulse{0%,100%{opacity:1;box-shadow:0 0 0 0 rgba(99,102,241,0.4)}50%{opacity:0.85;box-shadow:0 0 0 6px rgba(99,102,241,0)}}
</style>
@endpush

@section('content')
<div class="form-section">

    {{-- Header --}}
    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:0.5rem">
        <a href="{{ route('admin.support.index') }}" style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 0.875rem;border-radius:0.75rem;font-size:0.75rem;font-weight:500;color:#4b5563;background:rgba(255,255,255,0.7);border:1px solid #e5e7eb;text-decoration:none;transition:all 0.2s">
            <x-core-ui::icon name="chevron-right" class="w-5 h-5 shrink-0" />
            بازگشت به پشتیبانی
        </a>
        <div>
            <h2 style="font-size:1.25rem;font-weight:700;color:#111827;margin:0">تاریخچه تیکت‌ها</h2>
            <p style="font-size:0.875rem;color:#6b7280;margin:0.25rem 0 0">مشاهده و پیگیری تمام تیکت‌های ثبت شده</p>
        </div>
    </div>

    {{-- خطا و فلش messages --}}
    @if($error)
        <div class="error-glass" style="display:flex;align-items:flex-start;gap:0.75rem;margin-bottom:1rem">
            <x-core-ui::icon name="alert-triangle" class="w-5 h-5 shrink-0" style="color:#ef4444;margin-top:0.125rem" />
            <p style="font-size:0.875rem;color:#b91c1c;margin:0">{{ $error }}</p>
        </div>
    @endif
    @if(session('success'))
        <div class="ticket-card" style="border-color:#a7f3d0;background:rgba(236,253,245,0.8);display:flex;align-items:center;gap:0.75rem;margin-bottom:1rem" x-data="{ show: true }" x-show="show" x-transition>
            <x-core-ui::icon name="check-circle" class="w-5 h-5 shrink-0" style="color:#059669" />
            <span style="font-size:0.875rem;color:#065f46;font-weight:500">{{ session('success') }}</span>
            <button @click="show = false" style="margin-right:auto;color:#34d399;background:none;border:none;cursor:pointer">
                <x-core-ui::icon name="close" class="w-5 h-5 shrink-0" />
            </button>
        </div>
    @endif
    @if(session('error'))
        <div class="error-glass" style="display:flex;align-items:flex-start;gap:0.75rem;margin-bottom:1rem" x-data="{ show: true }" x-show="show" x-transition>
            <x-core-ui::icon name="alert-triangle" class="w-5 h-5 shrink-0" style="color:#ef4444;margin-top:0.125rem" />
            <p style="font-size:0.875rem;color:#b91c1c;flex:1;margin:0">{{ session('error') }}</p>
            <button @click="show = false" style="color:#f87171;background:none;border:none;cursor:pointer;flex-shrink:0">
                <x-core-ui::icon name="close" class="w-5 h-5 shrink-0" />
            </button>
        </div>
    @endif

    {{-- لیست تیکت‌ها --}}
    @if($tickets->isEmpty())
        <div class="ticket-card" style="text-align:center;padding:3rem 1.5rem">
            <div style="font-size:3rem;margin-bottom:1rem">📭</div>
            <h3 style="font-weight:700;color:#111827;margin:0 0 0.5rem;font-size:1rem">هیچ تیکتی ثبت نشده</h3>
            <p style="font-size:0.875rem;color:#6b7280;margin:0">هنوز تیکتی ارسال نکرده‌اید. برای ثبت تیکت جدید به <a href="{{ route('admin.support.index') }}" style="color:#4f46e5;font-weight:500">مرکز پشتیبانی</a> مراجعه کنید.</p>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:1rem">
            @foreach($tickets as $ticket)
                <div class="ticket-card" style="overflow:hidden;{{ $ticket['status'] === 'closed' ? 'border-color:#e5e7eb;background:rgba(249,250,251,0.8)' : 'border-color:#e5e7eb' }}">
                    {{-- هدر تیکت --}}
                    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:0.5rem;margin-bottom:0.75rem">
                        <div style="display:flex;align-items:center;gap:0.5rem">
                            <span style="font-family:monospace;font-weight:700;color:#4338ca;font-size:0.75rem;background:#eef2ff;padding:0.25rem 0.5rem;border-radius:0.5rem">{{ $ticket['ticket_number'] ?? '—' }}</span>
                            <span style="font-weight:600;font-size:0.9375rem;color:#111827">{{ $ticket['subject'] }}</span>
                        </div>
                        <div style="display:flex;gap:0.5rem;font-size:0.75rem">
                            @if($ticket['status'] === 'open')
                                <span class="status-badge" style="background:#fefce8;color:#a16207;border-color:#fde68a"><span style="width:0.375rem;height:0.375rem;border-radius:50%;background:#eab308;animation:pulse 2s infinite;display:inline-block"></span> باز</span>
                            @elseif($ticket['status'] === 'in_progress')
                                <span class="status-badge" style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe"><span style="width:0.375rem;height:0.375rem;border-radius:50%;background:#3b82f6;animation:pulse 2s infinite;display:inline-block"></span> در حال بررسی</span>
                            @else
                                <span class="status-badge" style="background:#f9fafb;color:#4b5563;border-color:#e5e7eb"><x-core-ui::icon name="check" class="w-5 h-5 shrink-0" /> بسته</span>
                            @endif
                            @if(isset($ticket['priority']))
                                @php $priorityColors = ['low' => '#059669', 'normal' => '#6366f1', 'high' => '#dc2626']; @endphp
                                <span class="status-badge" style="background:{{ $ticket['priority'] === 'high' ? '#fef2f2' : ($ticket['priority'] === 'low' ? '#ecfdf5' : '#eef2ff') }};color:{{ $priorityColors[$ticket['priority']] ?? '#6366f1' }};border-color:transparent">
                                    {{ $ticket['priority'] === 'high' ? '🔴' : ($ticket['priority'] === 'low' ? '🟢' : '🔵') }}
                                    {{ $ticket['priority'] === 'high' ? 'بالا' : ($ticket['priority'] === 'low' ? 'کم' : 'عادی') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- پیام اصلی --}}
                    <div style="background:#f9fafb;border-radius:0.75rem;padding:0.75rem 1rem;margin-bottom:0.75rem">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.375rem">
                            <span style="font-size:0.75rem;font-weight:600;color:#374151">پیام شما</span>
                            <span style="font-size:0.6875rem;color:#9ca3af">{{ \App\Helpers\Jalali::formatFa(\Carbon\Carbon::parse($ticket['created_at']), 'Y/m/d H:i') }}</span>
                        </div>
                        <p style="font-size:0.875rem;color:#4b5563;white-space:pre-wrap;margin:0">{{ $ticket['message'] }}</p>
                    </div>

                    {{-- پاسخ‌ها --}}
                    @if(count($ticket['replies']) > 0)
                        <div style="margin-bottom:0.75rem">
                            <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem">
                                <span style="width:0.25rem;height:1rem;background:linear-gradient(135deg,#6366f1,#a855f7);border-radius:9999px;display:inline-block"></span>
                                <span style="font-size:0.8125rem;font-weight:600;color:#374151">پاسخ‌ها ({{ count($ticket['replies']) }})</span>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:0.5rem;padding-right:0.75rem;border-right:2px solid #e5e7eb">
                                @foreach($ticket['replies'] as $reply)
                                    <div style="font-size:0.8125rem;{{ $reply['author_type'] === 'staff' ? 'background:#eff6ff;padding:0.625rem;border-radius:0.75rem;border:1px solid #dbeafe' : '' }}">
                                        <div style="display:flex;justify-content:space-between;color:#6b7280;margin-bottom:0.25rem">
                                            <span style="font-weight:600;color:#374151">
                                                @if($reply['author_type'] === 'staff')
                                                    <span style="display:inline-flex;align-items:center;gap:0.25rem">
                                                        <x-core-ui::icon name="user" class="w-5 h-5 shrink-0" style="color:#4f46e5" />
                                                        پشتیبانی
                                                    </span>
                                                @else
                                                    {{ $reply['customer_name'] ?? 'شما' }}
                                                @endif
                                            </span>
                                            <span>{{ \App\Helpers\Jalali::formatFa(\Carbon\Carbon::parse($reply['created_at']), 'Y/m/d H:i') }}</span>
                                        </div>
                                        <p style="white-space:pre-wrap;color:#374151;margin:0">{{ $reply['message'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- فرم ارسال پاسخ (فقط برای تیکت‌های باز) --}}
                    @if($ticket['status'] !== 'closed')
                        <div style="border-top:1px solid #e5e7eb;padding-top:0.75rem;margin-top:0.5rem">
                            <form method="POST" action="{{ route('admin.support.reply', $ticket['id']) }}" x-data="{ message: '' }">
                                @csrf
                                <div style="display:flex;flex-direction:column;gap:0.5rem">
                                    <textarea name="message" x-model="message" class="form-input" style="resize:vertical;min-height:80px" rows="3" required placeholder="پاسخ خود را بنویسید..." maxlength="5000"></textarea>
                                    <div style="display:flex;align-items:center;justify-content:space-between">
                                        <span style="font-size:0.75rem;color:#9ca3af" x-text="message.length + ' / 5000 کاراکتر'"></span>
                                        <button type="submit" class="btn-gradient" style="padding:0.5rem 1.25rem;font-size:0.8125rem">
                                            <x-core-ui::icon name="arrow-left" class="w-5 h-5 shrink-0" />
                                            ارسال پاسخ
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @else
                        <div style="border-top:1px solid #e5e7eb;padding-top:0.75rem;margin-top:0.5rem;text-align:center">
                            <span style="font-size:0.8125rem;color:#9ca3af">این تیکت بسته شده است و امکان ارسال پاسخ وجود ندارد.</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

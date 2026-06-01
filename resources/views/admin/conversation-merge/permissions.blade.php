@extends('admin.layout')
@section('title', 'مدیریت دسترسی ادغام مکالمات')

@push('head')
<style>
.perm-table th { background: #f9fafb; font-weight: 600; font-size: .8rem; color: #374151; }
.perm-table td { font-size: .875rem; vertical-align: middle; }
.badge-granted { display:inline-flex; align-items:center; gap:.3rem; padding:.25rem .7rem; border-radius:9999px; background:#dcfce7; color:#166534; font-size:.7rem; font-weight:700; border:1px solid #bbf7d0; }
.badge-none    { display:inline-flex; align-items:center; gap:.3rem; padding:.25rem .7rem; border-radius:9999px; background:#f3f4f6; color:#6b7280; font-size:.7rem; font-weight:600; border:1px solid #e5e7eb; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

    {{-- سربرگ --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="bi bi-git text-primary me-2"></i>دسترسی ادغام مکالمات
            </h4>
            <small class="text-muted">
                مشخص کنید کدام کاربران می‌توانند مکالمات موبایل را ادغام کنند.
                ادمین‌ها همیشه این دسترسی را دارند.
            </small>
        </div>
        <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-right me-1"></i>بازگشت
        </a>
    </div>

    {{-- هشدار عدم تنظیم پلتفرم --}}
    @if($notConfigured)
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div>
            اتصال به پلتفرم اصلی تنظیم نشده است.
            برای فعال‌سازی این قابلیت، متغیرهای <code>PLATFORM_API_URL</code> و <code>PLATFORM_ADMIN_TOKEN</code>
            را در فایل <code>.env</code> تنظیم کنید.
        </div>
    </div>
    @endif

    {{-- پیام‌های flash --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- جدول کاربران --}}
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <table class="table perm-table mb-0">
                <thead>
                    <tr>
                        <th class="ps-4 py-3">کاربر</th>
                        <th class="py-3">موبایل</th>
                        <th class="py-3">وضعیت دسترسی</th>
                        <th class="py-3 pe-4 text-end">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    @php $hasGrant = isset($granted[(string)$user->id]); @endphp
                    <tr>
                        <td class="ps-4 py-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold"
                                     style="width:2rem;height:2rem;font-size:.75rem;">
                                    {{ mb_substr($user->name ?? '?', 0, 1) }}
                                </div>
                                <span>{{ $user->name ?? '—' }}</span>
                            </div>
                        </td>
                        <td class="py-3 font-monospace text-muted" style="direction:ltr">{{ $user->mobile ?? '—' }}</td>
                        <td class="py-3">
                            @if($hasGrant)
                                <span class="badge-granted"><i class="bi bi-check-circle-fill"></i> دارد</span>
                            @else
                                <span class="badge-none"><i class="bi bi-dash-circle"></i> ندارد</span>
                            @endif
                        </td>
                        <td class="py-3 pe-4 text-end">
                            @if($hasGrant)
                                <form method="POST"
                                      action="{{ route('admin.conversation-merge.revoke', $user->id) }}"
                                      onsubmit="return confirm('لغو دسترسی ادغام برای این کاربر؟')"
                                      class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" {{ $notConfigured ? 'disabled' : '' }}>
                                        <i class="bi bi-x-circle me-1"></i>لغو دسترسی
                                    </button>
                                </form>
                            @else
                                <form method="POST"
                                      action="{{ route('admin.conversation-merge.grant') }}"
                                      class="d-inline">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-success" {{ $notConfigured ? 'disabled' : '' }}>
                                        <i class="bi bi-plus-circle me-1"></i>اعطای دسترسی
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-5">هیچ کاربری یافت نشد.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-muted mt-3" style="font-size:.8rem;">
        <i class="bi bi-info-circle me-1"></i>
        ادمین سایت و ادمین پلتفرم همیشه دسترسی ادغام را دارند و نیازی به تنظیم ندارند.
    </p>
</div>
@endsection

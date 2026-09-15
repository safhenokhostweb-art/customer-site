@php
    $uid = $uid ?? uniqid('pp');
    $selected = $selected ?? null;
@endphp
<div class="admin-page-picker js-page-picker" data-search-url="{{ route('admin.menus.pages.search') }}">
    <input type="hidden" name="{{ $name }}" class="page-picker-value" value="{{ $selected?->id }}">
    <div class="page-picker-selected" style="{{ $selected ? '' : 'display:none;' }}display:flex;align-items:center;gap:0.5rem;padding:0.4rem 0.6rem;border:1px solid #e2e8f0;border-radius:0.375rem;background:#f8fafc;">
        <span class="page-picker-selected-title" style="font-weight:600;">{{ $selected?->title }}</span>
        <span class="page-picker-selected-slug text-xs text-slate-500">/{{ $selected?->slug }}</span>
        <button type="button" class="btn btn-outline-secondary btn-sm page-picker-clear" style="margin-inline-start:auto;">تغییر</button>
    </div>
    <div class="page-picker-search" style="{{ $selected ? 'display:none;' : '' }}position:relative;">
        <input type="text" class="page-picker-input" placeholder="جستجوی صفحه بر اساس عنوان یا نامک…" autocomplete="off">
        <div class="page-picker-results" style="display:none;position:absolute;z-index:20;top:100%;right:0;left:0;background:#fff;border:1px solid #e2e8f0;border-radius:0.375rem;box-shadow:0 4px 10px rgba(0,0,0,.08);max-height:14rem;overflow-y:auto;"></div>
        <p class="page-picker-empty text-xs text-slate-400" style="display:none;margin:0.35rem 0 0;">صفحه‌ای یافت نشد.</p>
    </div>
</div>

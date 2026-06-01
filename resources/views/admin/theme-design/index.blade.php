@extends('admin.layout')
@section('title', 'ظاهر و تم')
@section('header', 'ظاهر و تم')
@section('content')
<div class="admin-page">

    @if(session('success'))
    <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    {{-- ═══ Enterprise Theme Presets ═══ --}}
    <div class="card mb-6" style="margin-bottom:1.5rem;">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
            <span>پریست‌های Enterprise Theme Engine</span>
            @if($activePreset)
            <form action="{{ route('admin.theme-design.reset-preset') }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-sm" style="font-size:0.75rem;padding:0.3rem 0.75rem;background:transparent;border:1px solid #d1d5db;color:#6b7280;border-radius:0.5rem;">
                    بازنشانی به سفارشی
                </button>
            </form>
            @endif
        </div>
        <div class="card-body">
            <p class="admin-muted-note" style="margin-bottom:1rem;font-size:0.85rem;">
                یکی از ۸ پریست حرفه‌ای را انتخاب کنید. تم بلافاصله بر کل پنل اعمال می‌شود.
            </p>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;">
                @foreach($presets as $preset)
                @php $isActive = $activePreset === $preset['slug']; @endphp
                <div style="border-radius:0.875rem;border:2px solid {{ $isActive ? $preset['accent'] : '#e5e7eb' }};overflow:hidden;box-shadow:{{ $isActive ? '0 0 0 3px '.($preset['accent']).'33' : '0 1px 3px rgba(0,0,0,0.07)' }};transition:box-shadow .15s,border-color .15s;">
                    {{-- Color strip --}}
                    <div style="height:56px;background:linear-gradient(135deg,{{ $preset['sidebar'] }} 0%,{{ $preset['sidebar_end'] }} 50%,{{ $preset['primary'] }} 100%);position:relative;">
                        <div style="position:absolute;bottom:8px;left:8px;display:flex;gap:5px;">
                            <span style="width:18px;height:18px;border-radius:50%;background:{{ $preset['accent'] }};border:2px solid rgba(255,255,255,0.4);"></span>
                            <span style="width:18px;height:18px;border-radius:50%;background:{{ $preset['primary'] }};border:2px solid rgba(255,255,255,0.4);"></span>
                        </div>
                        @if($isActive)
                        <div style="position:absolute;top:6px;right:6px;background:{{ $preset['accent'] }};color:#fff;font-size:0.65rem;font-weight:700;padding:2px 8px;border-radius:99px;">
                            فعال
                        </div>
                        @endif
                    </div>
                    {{-- Info --}}
                    <div style="padding:0.75rem;background:#fff;">
                        <div style="font-weight:600;font-size:0.85rem;color:#111827;margin-bottom:0.2rem;">{{ $preset['name'] }}</div>
                        <div style="font-size:0.72rem;color:#6b7280;line-height:1.4;">{{ $preset['desc'] }}</div>
                    </div>
                    {{-- Action --}}
                    <div style="padding:0 0.75rem 0.75rem;">
                        @if(!$isActive)
                        <form action="{{ route('admin.theme-design.preset') }}" method="POST">
                            @csrf
                            <input type="hidden" name="preset_slug" value="{{ $preset['slug'] }}">
                            <button type="submit"
                                style="width:100%;padding:0.4rem 0;border-radius:0.5rem;border:none;background:{{ $preset['accent'] }};color:#fff;font-size:0.78rem;font-weight:600;cursor:pointer;">
                                فعال‌سازی
                            </button>
                        </form>
                        @else
                        <div style="width:100%;padding:0.4rem 0;border-radius:0.5rem;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;font-size:0.78rem;font-weight:600;text-align:center;">
                            ✓ در حال استفاده
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ═══ Custom Settings ═══ --}}
    <div class="card">
        <div class="card-header">تنظیمات سفارشی رنگ و فونت</div>
        <div class="card-body">
            <p class="admin-muted-note" style="margin-bottom:1rem;font-size:0.85rem;">
                اگر پریستی انتخاب نشده باشد، این مقادیر به‌عنوان تم سفارشی اعمال می‌شوند.
            </p>
            <form action="{{ route('admin.theme-design.update') }}" method="post" class="admin-form-stack" style="max-width:560px;">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label class="form-label">رنگ اصلی (Primary)</label>
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <input type="color" value="{{ old('primary_color', $settings->primary_color ?? '#4f46e5') }}" id="pc_primary"
                               oninput="document.getElementById('pt_primary').value=this.value"
                               style="width:40px;height:36px;border-radius:6px;border:1px solid #d1d5db;padding:2px;cursor:pointer;">
                        <input type="text" name="primary_color" id="pt_primary"
                               value="{{ old('primary_color', $settings->primary_color ?? '#4f46e5') }}" placeholder="#4f46e5"
                               oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value))document.getElementById('pc_primary').value=this.value"
                               style="flex:1;">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">رنگ ثانویه (Secondary)</label>
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <input type="color" value="{{ old('secondary_color', $settings->secondary_color ?? '#64748b') }}" id="pc_secondary"
                               oninput="document.getElementById('pt_secondary').value=this.value"
                               style="width:40px;height:36px;border-radius:6px;border:1px solid #d1d5db;padding:2px;cursor:pointer;">
                        <input type="text" name="secondary_color" id="pt_secondary"
                               value="{{ old('secondary_color', $settings->secondary_color ?? '#64748b') }}" placeholder="#64748b"
                               oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value))document.getElementById('pc_secondary').value=this.value"
                               style="flex:1;">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">فونت عناوین</label>
                    <input type="text" name="font_family_heading" value="{{ old('font_family_heading', $settings->font_family_heading ?? 'Vazirmatn, Tahoma, sans-serif') }}" placeholder="Vazirmatn, Tahoma, sans-serif">
                </div>
                <div class="form-group">
                    <label class="form-label">فونت متن</label>
                    <input type="text" name="font_family_body" value="{{ old('font_family_body', $settings->font_family_body ?? 'Vazirmatn, Tahoma, sans-serif') }}" placeholder="Vazirmatn, Tahoma, sans-serif">
                </div>
                <div class="form-group">
                    <label class="form-label">اندازه پایه فونت</label>
                    <input type="text" name="base_font_size" value="{{ old('base_font_size', $settings->base_font_size ?? '16px') }}" placeholder="16px یا 1rem">
                </div>
                <div class="form-group">
                    <label class="form-label">عرض کانتینر</label>
                    <input type="text" name="container_width" value="{{ old('container_width', $settings->container_width ?? '1200px') }}" placeholder="1200px">
                </div>
                <div class="form-group">
                    <label class="form-label">گردی گوشه‌ها (Border radius)</label>
                    <input type="text" name="border_radius" value="{{ old('border_radius', $settings->border_radius ?? '8px') }}" placeholder="8px">
                </div>
                <div class="form-group">
                    <label class="form-label">استایل دکمه</label>
                    <select name="button_style">
                        @foreach($buttonStyles as $value => $label)
                        <option value="{{ $value }}" {{ old('button_style', $settings->button_style ?? 'solid') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">فاصله سکشن‌ها</label>
                    <select name="section_spacing">
                        @foreach(['xs'=>'خیلی کم','sm'=>'کم','md'=>'متوسط','lg'=>'زیاد','xl'=>'خیلی زیاد'] as $v => $l)
                        <option value="{{ $v }}" {{ old('section_spacing', $settings->section_spacing ?? 'md') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary">ذخیره تنظیمات سفارشی</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

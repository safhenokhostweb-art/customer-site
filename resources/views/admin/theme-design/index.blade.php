@extends('admin.layout')
@section('title', 'ظاهر و تم')
@section('header', 'ظاهر و تم')
@section('content')
<div class="admin-page">

    {{-- ═══ Canvas Theme Status Banner ═══ --}}
    @if($canvasActive)
    <div style="background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);border-radius:1rem;padding:1.25rem 1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;color:#fff;">
        <div style="display:flex;align-items:center;gap:0.875rem;">
            <div style="width:2.75rem;height:2.75rem;background:rgba(255,255,255,0.15);border-radius:0.875rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <x-core-ui::icon name="layout" class="w-6 h-6" style="color:#fff;" />
            </div>
            <div>
                <p style="font-weight:700;font-size:1rem;margin:0 0 0.15rem;">قالب Canvas فعال است</p>
                <p style="font-size:0.8rem;opacity:0.85;margin:0;">{{ $canvasName ?: 'قالب سفارشی' }} — صفحات عمومی سایت با این قالب نمایش داده می‌شوند</p>
            </div>
        </div>
        <a href="{{ route('admin.theme-design.canvas') }}"
           style="display:inline-flex;align-items:center;gap:0.5rem;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);color:#fff;font-size:0.85rem;font-weight:600;padding:0.5rem 1.25rem;border-radius:0.75rem;text-decoration:none;">
            <x-core-ui::icon name="eye" class="w-4 h-4" />
            مدیریت قالب Canvas
        </a>
    </div>
    @else
    <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:1rem;padding:1rem 1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <div style="width:2.5rem;height:2.5rem;background:#e0f2fe;border-radius:0.75rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <x-core-ui::icon name="layout" class="w-5 h-5 text-sky-500" />
            </div>
            <div>
                <p style="font-weight:600;font-size:0.9rem;color:#0c4a6e;margin:0 0 0.15rem;">قالب Canvas نصب نشده</p>
                <p style="font-size:0.78rem;color:#0369a1;margin:0;">قالب‌های حرفه‌ای را از مارکت‌پلیس خریداری و نصب کنید</p>
            </div>
        </div>
        <a href="{{ route('admin.theme-marketplace.index') }}"
           style="display:inline-flex;align-items:center;gap:0.5rem;background:#0284c7;color:#fff;font-size:0.82rem;font-weight:600;padding:0.45rem 1rem;border-radius:0.65rem;text-decoration:none;">
            <x-core-ui::icon name="store" class="w-4 h-4" />
            مارکت‌پلیس
        </a>
    </div>
    @endif

    {{-- ═══ My Themes (قالب‌های من — مالکیت چندقالبی) ═══ --}}
    <div class="card" style="margin-bottom:1.75rem;" data-testid="my-themes-section">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;">
            <div style="display:flex;align-items:center;gap:0.75rem;">
                <div style="width:2rem;height:2rem;background:linear-gradient(135deg,#4f46e5,#6366f1);border-radius:0.5rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <x-core-ui::icon name="layout" class="w-4 h-4" style="color:#fff;" />
                </div>
                <div>
                    <span style="font-weight:700;font-size:1rem;display:block;">قالب‌های من</span>
                    <span style="font-size:0.78rem;color:#6b7280;">مدیریت تمام قالب‌های خریداری‌شده، تخصیص‌داده‌شده یا ساخته‌شده با بوم خالی</span>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                <form action="{{ route('admin.theme-design.canvas.create-blank') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-secondary" style="font-size:0.78rem;padding:0.35rem 0.75rem;">
                        ساخت قالب با بوم خالی
                    </button>
                </form>
                <a href="{{ route('admin.theme-marketplace.index') }}" class="btn btn-sm btn-primary" style="font-size:0.78rem;padding:0.35rem 0.75rem;text-decoration:none;">
                    خرید قالب جدید
                </a>
            </div>
        </div>
        <div class="card-body">
            @if(isset($myThemes) && $myThemes->isNotEmpty())
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem;">
                    @foreach($myThemes as $item)
                        <div style="border:2px solid {{ $item->is_active ? '#4f46e5' : '#e5e7eb' }};border-radius:0.875rem;padding:1rem;background:{{ $item->is_active ? '#eef2ff' : '#fff' }};display:flex;flex-direction:column;justify-content:space-between;gap:0.75rem;">
                            <div>
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;margin-bottom:0.4rem;">
                                    <strong style="font-size:0.95rem;color:#111827;">{{ $item->theme_name }}</strong>
                                    @if($item->is_active)
                                        <span style="background:#4f46e5;color:#fff;font-size:0.7rem;font-weight:700;padding:0.15rem 0.55rem;border-radius:99px;">فعال</span>
                                    @else
                                        <span style="background:#f3f4f6;color:#6b7280;font-size:0.7rem;font-weight:600;padding:0.15rem 0.55rem;border-radius:99px;">غیرفعال</span>
                                    @endif
                                </div>
                                <div style="font-size:0.75rem;color:#6b7280;display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                                    <code>{{ $item->theme_slug }}</code>
                                    @if($item->source_theme_version)
                                        <span>• نسخه {{ $item->source_theme_version }}</span>
                                    @endif
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;padding-top:0.5rem;border-top:1px solid {{ $item->is_active ? '#c7d2fe' : '#f3f4f6' }};">
                                @if($item->is_active)
                                    <a href="{{ route('admin.theme-design.canvas-edit') }}"
                                       class="btn btn-sm btn-primary"
                                       style="flex:1;text-align:center;text-decoration:none;font-size:0.78rem;padding:0.4rem 0.75rem;">
                                        ویرایش با Canvas
                                    </a>
                                @else
                                    <form action="{{ route('admin.theme-design.my-themes.activate', $item->theme_slug) }}" method="POST" style="flex:1;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-secondary" style="width:100%;font-size:0.78rem;padding:0.4rem 0.75rem;">
                                            فعالسازی
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.theme-design.my-themes.activate', $item->theme_slug) }}" method="POST" style="flex:1;">
                                        @csrf
                                        <input type="hidden" name="open_canvas" value="1">
                                        <button type="submit" class="btn btn-sm btn-primary" style="width:100%;font-size:0.78rem;padding:0.4rem 0.75rem;">
                                            ویرایش با Canvas
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p style="font-size:0.85rem;color:#6b7280;margin:0;">
                    هنوز قالبی در مجموعه «قالب‌های من» ثبت نشده است و سایت از تم پیش‌فرض پایه استفاده می‌کند. می‌توانید از مارکت‌پلیس قالب بخرید یا یک قالب با بوم خالی بسازید.
                </p>
            @endif
        </div>
    </div>

    {{-- Phase 3H-B: legacy 5-slot rotating "Canvas Revision History" UI
         de-exposed here — superseded by the canonical, append-only permanent
         revision history/rollback panel on the Canvas dashboard itself
         (admin.theme-design.canvas). Backend (canvasRevisions()/
         canvasRestoreRevision(), canvas_revision_{1..5} Settings) left
         intact/untouched — not an ordinary UI choice any more. --}}

    {{-- ══════════════════════════════════════════════════════════════════════
         SECTION 1 — ظاهر پنل مدیریت (Dashboard)
         ══════════════════════════════════════════════════════════════════════ --}}
    <div class="card" style="margin-bottom:1.75rem;">
        <div class="card-header" style="display:flex;align-items:center;gap:0.75rem;">
            <div style="width:2rem;height:2rem;background:linear-gradient(135deg,#1e293b,#0f172a);border-radius:0.5rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <x-core-ui::icon name="server" class="w-4 h-4" style="color:#60a5fa;" />
            </div>
            <div style="flex:1;">
                <span style="font-weight:700;font-size:1rem;display:block;">ظاهر پنل مدیریت</span>
                <span style="font-size:0.78rem;color:#6b7280;">رنگ نوار کناری و داشبورد ادمین — مستقل از تم سایت</span>
            </div>
            @if($activeDashPreset && $activeDashPreset !== 'enterprise-blue')
            <form action="{{ route('admin.theme-design.dashboard-preset') }}" method="POST" style="display:inline;">
                @csrf
                <input type="hidden" name="preset_slug" value="enterprise-blue">
                <button type="submit" class="btn btn-sm" style="font-size:0.73rem;padding:0.3rem 0.75rem;background:transparent;border:1px solid #d1d5db;color:#6b7280;border-radius:0.5rem;">
                    بازگشت به پیش‌فرض
                </button>
            </form>
            @endif
        </div>
        <div class="card-body">
            <p style="font-size:0.83rem;color:#6b7280;margin:0 0 1.25rem;">یکی از تم‌های آماده را انتخاب کنید یا رنگ‌ها را به صورت دستی تنظیم نمایید.</p>

            {{-- Preset grid --}}
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:0.875rem;margin-bottom:1.5rem;">
                @foreach($presets as $preset)
                @php $isDashActive = $activeDashPreset === $preset['slug']; @endphp
                <div style="border-radius:0.875rem;border:2px solid {{ $isDashActive ? $preset['accent'] : '#e5e7eb' }};overflow:hidden;box-shadow:{{ $isDashActive ? '0 0 0 3px '.($preset['accent']).'33' : '0 1px 3px rgba(0,0,0,0.07)' }};transition:all .15s;">
                    {{-- Sidebar gradient preview --}}
                    <div style="height:52px;background:linear-gradient(160deg,{{ $preset['sidebar'] }} 0%,{{ $preset['sidebar_end'] }} 100%);position:relative;display:flex;align-items:flex-end;padding:0 8px 7px;">
                        <div style="display:flex;gap:4px;align-items:center;">
                            <span style="width:6px;height:6px;border-radius:50%;background:{{ $preset['accent'] }};"></span>
                            <span style="width:28px;height:3px;border-radius:2px;background:rgba(255,255,255,0.5);"></span>
                        </div>
                        <div style="position:absolute;top:0;right:0;bottom:0;left:0;display:flex;flex-direction:column;justify-content:center;gap:4px;padding:0 8px;">
                            <span style="width:55%;height:4px;border-radius:2px;background:rgba(255,255,255,0.25);"></span>
                            <span style="width:40%;height:4px;border-radius:2px;background:rgba(255,255,255,0.18);"></span>
                            <span style="width:50%;height:4px;border-radius:2px;background:rgba(255,255,255,0.14);"></span>
                        </div>
                        @if($isDashActive)
                        <div style="position:absolute;top:5px;left:5px;background:{{ $preset['accent'] }};color:#fff;font-size:0.6rem;font-weight:700;padding:1px 7px;border-radius:99px;">فعال</div>
                        @endif
                        <div style="position:absolute;bottom:5px;left:8px;display:flex;gap:3px;">
                            <span style="width:12px;height:12px;border-radius:50%;background:{{ $preset['accent'] }};border:1.5px solid rgba(255,255,255,0.4);"></span>
                            <span style="width:12px;height:12px;border-radius:50%;background:{{ $preset['primary'] }};border:1.5px solid rgba(255,255,255,0.4);"></span>
                        </div>
                    </div>
                    <div style="padding:0.6rem 0.7rem 0.55rem;background:#fff;">
                        <div style="font-weight:600;font-size:0.8rem;color:#111827;margin-bottom:0.15rem;">{{ $preset['name'] }}</div>
                        <div style="font-size:0.68rem;color:#6b7280;line-height:1.4;">{{ $preset['desc'] }}</div>
                    </div>
                    <div style="padding:0 0.7rem 0.7rem;">
                        @if(!$isDashActive)
                        <form action="{{ route('admin.theme-design.dashboard-preset') }}" method="POST">
                            @csrf
                            <input type="hidden" name="preset_slug" value="{{ $preset['slug'] }}">
                            <button type="submit" style="width:100%;padding:0.35rem 0;border-radius:0.5rem;border:none;background:{{ $preset['accent'] }};color:#fff;font-size:0.74rem;font-weight:600;cursor:pointer;">
                                انتخاب
                            </button>
                        </form>
                        @else
                        <div style="width:100%;padding:0.35rem 0;border-radius:0.5rem;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;font-size:0.74rem;font-weight:600;text-align:center;">✓ انتخاب شده</div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Custom colors for dashboard --}}
            <div x-data="{ open: {{ $activeDashPreset === 'custom' ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open"
                    style="display:flex;align-items:center;gap:0.5rem;font-size:0.83rem;font-weight:600;color:#4f46e5;background:none;border:none;cursor:pointer;padding:0;margin-bottom:0.75rem;">
                    <x-core-ui::icon name="settings" class="w-4 h-4" />
                    <span x-text="open ? 'بستن رنگ‌های سفارشی داشبورد' : 'تنظیم رنگ‌های سفارشی داشبورد'"></span>
                    <x-core-ui::icon name="chevron-down" class="w-4 h-4" style="transition:transform .2s" x-bind:style="open ? 'transform:rotate(180deg)' : ''"/>
                </button>
                <div x-show="open" x-transition.duration.200ms>
                    <form action="{{ route('admin.theme-design.dashboard-custom') }}" method="POST" style="max-width:640px;">
                        @csrf
                        @method('PUT')
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:0.875rem;margin-bottom:1rem;">
                            @php
                            $dashLabels = [
                                'admin_sidebar_bg1'    => 'پس‌زمینه اصلی نوار کناری',
                                'admin_sidebar_bg2'    => 'پس‌زمینه ثانویه (گرادیان)',
                                'admin_sidebar_text'   => 'رنگ متن',
                                'admin_sidebar_muted'  => 'رنگ متن محو',
                                'admin_sidebar_accent' => 'رنگ تاکیدی (Accent)',
                                'admin_sidebar_border' => 'رنگ خطوط جداکننده',
                            ];
                            @endphp
                            @foreach($dashLabels as $key => $label)
                            @php $val = $dashCustomColors[$key] ?? '#1e293b'; @endphp
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:0.8rem;">{{ $label }}</label>
                                <div style="display:flex;align-items:center;gap:0.5rem;">
                                    <input type="color" value="{{ old($key, $val) }}" id="dc_color_{{ $key }}"
                                           oninput="document.getElementById('dc_text_{{ $key }}').value=this.value"
                                           style="width:36px;height:34px;border-radius:6px;border:1px solid #d1d5db;padding:2px;cursor:pointer;flex-shrink:0;">
                                    <input type="text" name="{{ $key }}" id="dc_text_{{ $key }}"
                                           value="{{ old($key, $val) }}" placeholder="{{ $val }}"
                                           pattern="#[0-9A-Fa-f]{6}" maxlength="7"
                                           oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value))document.getElementById('dc_color_{{ $key }}').value=this.value"
                                           style="flex:1;font-family:monospace;font-size:0.82rem;">
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn-primary" style="font-size:0.83rem;padding:0.45rem 1.25rem;">
                            ذخیره رنگ‌های سفارشی داشبورد
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         SECTION 2 — تم سایت
         TASK 3/3 Phase 6.5C (BLD-24) — Appearance convergence. While a
         Canvas theme is active, this legacy preset/custom-color form is a
         SECOND, disconnected place to change the site's live colors/fonts
         (the public render is driven by Canvas global_styles, not this —
         see ThemeDesignController::syncThemeSettingFromCanvasGlobalStyles()
         for why the underlying ThemeSetting store still exists and stays
         in sync automatically). Customer workflow must converge to ONE
         editable surface: when Canvas is active, this becomes a compact,
         read-only pointer to Canvas theme management instead of a second
         editable form; when no Canvas theme is installed yet, this remains
         the real, necessary, only appearance authority (the classic
         non-Canvas render path genuinely depends on it).
         ══════════════════════════════════════════════════════════════════════ --}}
    @if($canvasActive)
    <div class="card" style="margin-bottom:1.75rem;">
        <div class="card-header" style="display:flex;align-items:center;gap:0.75rem;">
            <div style="width:2rem;height:2rem;background:linear-gradient(135deg,#6366f1,#a855f7);border-radius:0.5rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <x-core-ui::icon name="sun" class="w-4 h-4" style="color:#fff;" />
            </div>
            <div style="flex:1;">
                <span style="font-weight:700;font-size:1rem;display:block;">تم سایت مشتری</span>
                <span style="font-size:0.78rem;color:#6b7280;">رنگ‌ها و ظاهر صفحات عمومی سایت — مستقل از داشبورد</span>
            </div>
        </div>
        <div class="card-body">
            <p style="font-size:0.85rem;color:#374151;margin:0 0 1rem;line-height:1.7;">
                رنگ‌ها و فونت سایت شما هم‌اکنون از <strong>قالب Canvas فعال</strong> ({{ $canvasName ?: 'قالب سفارشی' }}) خوانده می‌شود.
                برای تغییر رنگ اصلی/ثانویه، پس‌زمینه یا فونت سایت، از مدیریت قالب Canvas استفاده کنید — این فرم قدیمی حین فعال بودن Canvas اثری روی سایت عمومی ندارد.
            </p>
            <a href="{{ route('admin.theme-design.canvas') }}"
               style="display:inline-flex;align-items:center;gap:0.5rem;background:#4f46e5;color:#fff;font-size:0.85rem;font-weight:600;padding:0.55rem 1.25rem;border-radius:0.65rem;text-decoration:none;">
                <x-core-ui::icon name="layout" class="w-4 h-4" />
                تنظیم رنگ و فونت در Canvas
            </a>
        </div>
    </div>
    @else
    <div class="card" style="margin-bottom:1.75rem;">
        <div class="card-header" style="display:flex;align-items:center;gap:0.75rem;">
            <div style="width:2rem;height:2rem;background:linear-gradient(135deg,#6366f1,#a855f7);border-radius:0.5rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <x-core-ui::icon name="sun" class="w-4 h-4" style="color:#fff;" />
            </div>
            <div style="flex:1;">
                <span style="font-weight:700;font-size:1rem;display:block;">تم سایت مشتری</span>
                <span style="font-size:0.78rem;color:#6b7280;">رنگ‌ها و ظاهر صفحات عمومی سایت — مستقل از داشبورد</span>
            </div>
            @if($activeSitePreset)
            <form action="{{ route('admin.theme-design.reset-site-preset') }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-sm" style="font-size:0.73rem;padding:0.3rem 0.75rem;background:transparent;border:1px solid #d1d5db;color:#6b7280;border-radius:0.5rem;">
                    بازنشانی به سفارشی
                </button>
            </form>
            @endif
        </div>
        <div class="card-body">
            <p style="font-size:0.83rem;color:#6b7280;margin:0 0 1.25rem;">یکی از پریست‌های آماده را انتخاب کنید یا رنگ‌ها و فونت را به صورت دستی تنظیم نمایید.</p>

            {{-- Preset grid --}}
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:0.875rem;margin-bottom:1.5rem;">
                @foreach($presets as $preset)
                @php $isSiteActive = $activeSitePreset === $preset['slug']; @endphp
                <div style="border-radius:0.875rem;border:2px solid {{ $isSiteActive ? $preset['accent'] : '#e5e7eb' }};overflow:hidden;box-shadow:{{ $isSiteActive ? '0 0 0 3px '.($preset['accent']).'33' : '0 1px 3px rgba(0,0,0,0.07)' }};transition:all .15s;">
                    <div style="height:52px;background:linear-gradient(135deg,{{ $preset['primary'] }} 0%,{{ $preset['accent'] }} 100%);position:relative;display:flex;flex-direction:column;justify-content:center;padding:0 10px;gap:5px;">
                        <span style="width:65%;height:5px;border-radius:3px;background:rgba(255,255,255,0.75);"></span>
                        <span style="width:45%;height:3px;border-radius:2px;background:rgba(255,255,255,0.45);"></span>
                        @if($isSiteActive)
                        <div style="position:absolute;top:5px;left:5px;background:rgba(0,0,0,0.4);color:#fff;font-size:0.6rem;font-weight:700;padding:1px 7px;border-radius:99px;">فعال</div>
                        @endif
                        <div style="position:absolute;bottom:5px;left:8px;display:flex;gap:3px;">
                            <span style="width:12px;height:12px;border-radius:3px;background:rgba(255,255,255,0.9);"></span>
                            <span style="width:22px;height:12px;border-radius:3px;background:rgba(255,255,255,0.4);"></span>
                        </div>
                    </div>
                    <div style="padding:0.6rem 0.7rem 0.55rem;background:#fff;">
                        <div style="font-weight:600;font-size:0.8rem;color:#111827;margin-bottom:0.15rem;">{{ $preset['name'] }}</div>
                        <div style="font-size:0.68rem;color:#6b7280;line-height:1.4;">{{ $preset['desc'] }}</div>
                    </div>
                    <div style="padding:0 0.7rem 0.7rem;display:flex;flex-direction:column;gap:0.35rem;">
                        @if(!$isSiteActive)
                        <form action="{{ route('admin.theme-design.site-preset') }}" method="POST">
                            @csrf
                            <input type="hidden" name="preset_slug" value="{{ $preset['slug'] }}">
                            <button type="submit" style="width:100%;padding:0.35rem 0;border-radius:0.5rem;border:none;background:{{ $preset['accent'] }};color:#fff;font-size:0.74rem;font-weight:600;cursor:pointer;">
                                انتخاب
                            </button>
                        </form>
                        @else
                        <div style="width:100%;padding:0.35rem 0;border-radius:0.5rem;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;font-size:0.74rem;font-weight:600;text-align:center;">✓ انتخاب شده</div>
                        <form action="{{ route('admin.theme-design.reset-site-preset-to', $preset['slug']) }}" method="POST">
                            @csrf
                            <button type="submit" style="width:100%;padding:0.25rem 0;border-radius:0.5rem;border:1px solid #d1d5db;background:#fff;color:#6b7280;font-size:0.68rem;font-weight:600;cursor:pointer;">
                                بازگردانی به رنگ پیش‌فرض
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @endforeach

                {{-- Custom preset card --}}
                @php
                    $isSiteCustom = (!$activeSitePreset || $activeSitePreset === 'custom');
                    $customPrimary   = $settings->primary_color    ?? '#4f46e5';
                    $customAccent    = $settings->accent_color     ?? '#7c3aed';
                    $customBg        = $settings->background_color ?? '#f9fafb';
                    $customHeaderBg  = $settings->header_bg        ?? '#1e293b';
                @endphp
                <div style="border-radius:0.875rem;border:2px solid {{ $isSiteCustom ? '#7c3aed' : '#e5e7eb' }};overflow:hidden;box-shadow:{{ $isSiteCustom ? '0 0 0 3px #7c3aed33' : '0 1px 3px rgba(0,0,0,0.07)' }};transition:all .15s;">
                    <div style="height:52px;background:linear-gradient(135deg,{{ $customPrimary }} 0%,{{ $customAccent }} 100%);position:relative;display:flex;flex-direction:column;justify-content:center;padding:0 10px;gap:5px;">
                        <span style="width:65%;height:5px;border-radius:3px;background:rgba(255,255,255,0.75);"></span>
                        <span style="width:45%;height:3px;border-radius:2px;background:rgba(255,255,255,0.45);"></span>
                        @if($isSiteCustom)
                        <div style="position:absolute;top:5px;left:5px;background:rgba(0,0,0,0.4);color:#fff;font-size:0.6rem;font-weight:700;padding:1px 7px;border-radius:99px;">فعال</div>
                        @endif
                        <div style="position:absolute;bottom:5px;left:8px;display:flex;gap:3px;">
                            <span style="width:12px;height:12px;border-radius:3px;background:rgba(255,255,255,0.9);"></span>
                            <span style="width:22px;height:12px;border-radius:3px;background:rgba(255,255,255,0.4);"></span>
                        </div>
                    </div>
                    <div style="padding:0.6rem 0.7rem 0.55rem;background:#fff;">
                        <div style="font-weight:600;font-size:0.8rem;color:#111827;margin-bottom:0.15rem;">سفارشی</div>
                        <div style="font-size:0.68rem;color:#6b7280;line-height:1.4;">رنگ‌های دلخواه شما</div>
                    </div>
                    <div style="padding:0 0.7rem 0.7rem;">
                        @if(!$isSiteCustom)
                        <form action="{{ route('admin.theme-design.reset-site-preset') }}" method="POST">
                            @csrf
                            <button type="submit" style="width:100%;padding:0.35rem 0;border-radius:0.5rem;border:none;background:#7c3aed;color:#fff;font-size:0.74rem;font-weight:600;cursor:pointer;">
                                انتخاب
                            </button>
                        </form>
                        @else
                        <div style="width:100%;padding:0.35rem 0;border-radius:0.5rem;background:#f5f3ff;border:1px solid #ddd6fe;color:#5b21b6;font-size:0.74rem;font-weight:600;text-align:center;">✓ انتخاب شده</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Custom site settings (always visible when custom is active) --}}
            <div x-data="{ open: {{ $isSiteCustom ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open"
                    style="display:flex;align-items:center;gap:0.5rem;font-size:0.83rem;font-weight:600;color:#7c3aed;background:none;border:none;cursor:pointer;padding:0;margin-bottom:0.75rem;">
                    <x-core-ui::icon name="settings" class="w-4 h-4" />
                    <span x-text="open ? 'بستن تنظیمات سفارشی سایت' : 'تنظیمات سفارشی رنگ و فونت سایت'"></span>
                    <x-core-ui::icon name="chevron-down" class="w-4 h-4" style="transition:transform .2s" x-bind:style="open ? 'transform:rotate(180deg)' : ''"/>
                </button>
                <div x-show="open" x-transition.duration.200ms>
                    <form action="{{ route('admin.theme-design.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- ─── رنگ‌های اصلی برند ─────────────────────── --}}
                        <p style="font-size:0.78rem;font-weight:700;color:#374151;margin:0 0 0.625rem;text-transform:uppercase;letter-spacing:0.05em;">رنگ‌های اصلی برند</p>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:0.75rem;margin-bottom:1rem;">
                            @php
                            $siteColorFields = [
                                'primary_color'    => ['label' => 'رنگ اصلی (Primary)',      'default' => $settings->primary_color    ?? '#4f46e5'],
                                'secondary_color'  => ['label' => 'رنگ ثانویه (Secondary)',  'default' => $settings->secondary_color  ?? '#64748b'],
                                'accent_color'     => ['label' => 'رنگ تاکیدی (Accent)',     'default' => $settings->accent_color     ?? '#7c3aed'],
                                'link_color'       => ['label' => 'رنگ لینک‌ها',             'default' => $settings->link_color       ?? '#4f46e5'],
                            ];
                            @endphp
                            @foreach($siteColorFields as $fname => $fconf)
                            @php $fval = old($fname, $fconf['default']); @endphp
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:0.78rem;">{{ $fconf['label'] }}</label>
                                <div style="display:flex;align-items:center;gap:0.5rem;">
                                    <input type="color" value="{{ $fval }}" id="pc_{{ $fname }}"
                                           oninput="document.getElementById('pt_{{ $fname }}').value=this.value"
                                           style="width:36px;height:34px;border-radius:6px;border:1px solid #d1d5db;padding:2px;cursor:pointer;flex-shrink:0;">
                                    <input type="text" name="{{ $fname }}" id="pt_{{ $fname }}"
                                           value="{{ $fval }}" placeholder="{{ $fconf['default'] }}"
                                           oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value))document.getElementById('pc_{{ $fname }}').value=this.value"
                                           style="flex:1;font-family:monospace;font-size:0.82rem;">
                                </div>
                            </div>
                            @endforeach
                        </div>

                        {{-- ─── رنگ‌های پس‌زمینه و متن ──────────────────── --}}
                        <p style="font-size:0.78rem;font-weight:700;color:#374151;margin:0 0 0.625rem;text-transform:uppercase;letter-spacing:0.05em;">پس‌زمینه و متن</p>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:0.75rem;margin-bottom:1rem;">
                            @php
                            $bgTextFields = [
                                'background_color' => ['label' => 'پس‌زمینه صفحه',          'default' => $settings->background_color ?? '#f9fafb'],
                                'surface_color'    => ['label' => 'پس‌زمینه کارت/پنل',      'default' => $settings->surface_color    ?? '#ffffff'],
                                'text_color'       => ['label' => 'رنگ متن اصلی',           'default' => $settings->text_color       ?? '#111827'],
                                'muted_color'      => ['label' => 'رنگ متن فرعی',           'default' => $settings->muted_color      ?? '#6b7280'],
                                'border_color'     => ['label' => 'رنگ خطوط/حاشیه‌ها',      'default' => $settings->border_color     ?? '#e5e7eb'],
                            ];
                            @endphp
                            @foreach($bgTextFields as $fname => $fconf)
                            @php $fval = old($fname, $fconf['default']); @endphp
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:0.78rem;">{{ $fconf['label'] }}</label>
                                <div style="display:flex;align-items:center;gap:0.5rem;">
                                    <input type="color" value="{{ $fval }}" id="pc_{{ $fname }}"
                                           oninput="document.getElementById('pt_{{ $fname }}').value=this.value"
                                           style="width:36px;height:34px;border-radius:6px;border:1px solid #d1d5db;padding:2px;cursor:pointer;flex-shrink:0;">
                                    <input type="text" name="{{ $fname }}" id="pt_{{ $fname }}"
                                           value="{{ $fval }}" placeholder="{{ $fconf['default'] }}"
                                           oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value))document.getElementById('pc_{{ $fname }}').value=this.value"
                                           style="flex:1;font-family:monospace;font-size:0.82rem;">
                                </div>
                            </div>
                            @endforeach
                        </div>

                        {{-- ─── هدر و فوتر ────────────────────────────────── --}}
                        <p style="font-size:0.78rem;font-weight:700;color:#374151;margin:0 0 0.625rem;text-transform:uppercase;letter-spacing:0.05em;">هدر و فوتر سایت</p>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:0.75rem;margin-bottom:1rem;">
                            @php
                            $layoutColorFields = [
                                'header_bg' => ['label' => 'پس‌زمینه هدر سایت',  'default' => $settings->header_bg ?? '#1e293b'],
                                'footer_bg' => ['label' => 'پس‌زمینه فوتر سایت', 'default' => $settings->footer_bg ?? '#1e293b'],
                            ];
                            @endphp
                            @foreach($layoutColorFields as $fname => $fconf)
                            @php $fval = old($fname, $fconf['default']); @endphp
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:0.78rem;">{{ $fconf['label'] }}</label>
                                <div style="display:flex;align-items:center;gap:0.5rem;">
                                    <input type="color" value="{{ $fval }}" id="pc_{{ $fname }}"
                                           oninput="document.getElementById('pt_{{ $fname }}').value=this.value"
                                           style="width:36px;height:34px;border-radius:6px;border:1px solid #d1d5db;padding:2px;cursor:pointer;flex-shrink:0;">
                                    <input type="text" name="{{ $fname }}" id="pt_{{ $fname }}"
                                           value="{{ $fval }}" placeholder="{{ $fconf['default'] }}"
                                           oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value))document.getElementById('pc_{{ $fname }}').value=this.value"
                                           style="flex:1;font-family:monospace;font-size:0.82rem;">
                                </div>
                            </div>
                            @endforeach
                        </div>

                        {{-- ─── تایپوگرافی ────────────────────────────────── --}}
                        <p style="font-size:0.78rem;font-weight:700;color:#374151;margin:0 0 0.625rem;text-transform:uppercase;letter-spacing:0.05em;">تایپوگرافی</p>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.875rem;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:0.78rem;">فونت عناوین</label>
                                <input type="text" name="font_family_heading" value="{{ old('font_family_heading', $settings->font_family_heading ?? 'Vazirmatn, Tahoma, sans-serif') }}" placeholder="Vazirmatn, Tahoma, sans-serif" style="font-size:0.82rem;">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:0.78rem;">فونت متن</label>
                                <input type="text" name="font_family_body" value="{{ old('font_family_body', $settings->font_family_body ?? 'Vazirmatn, Tahoma, sans-serif') }}" placeholder="Vazirmatn, Tahoma, sans-serif" style="font-size:0.82rem;">
                            </div>
                        </div>

                        {{-- ─── ساختار ─────────────────────────────────────── --}}
                        <p style="font-size:0.78rem;font-weight:700;color:#374151;margin:0 0 0.625rem;text-transform:uppercase;letter-spacing:0.05em;">ساختار</p>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:0.75rem;margin-bottom:0.875rem;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:0.78rem;">اندازه پایه فونت</label>
                                <input type="text" name="base_font_size" value="{{ old('base_font_size', $settings->base_font_size ?? '16px') }}" placeholder="16px" style="font-size:0.82rem;">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:0.78rem;">عرض کانتینر</label>
                                <input type="text" name="container_width" value="{{ old('container_width', $settings->container_width ?? '1200px') }}" placeholder="1200px" style="font-size:0.82rem;">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:0.78rem;">گردی گوشه‌ها</label>
                                <input type="text" name="border_radius" value="{{ old('border_radius', $settings->border_radius ?? '8px') }}" placeholder="8px" style="font-size:0.82rem;">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:0.78rem;">استایل دکمه</label>
                                <select name="button_style" style="font-size:0.82rem;">
                                    @foreach($buttonStyles as $value => $label)
                                    <option value="{{ $value }}" {{ old('button_style', $settings->button_style ?? 'solid') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:0.78rem;">فاصله سکشن‌ها</label>
                                <select name="section_spacing" style="font-size:0.82rem;">
                                    @foreach(['xs'=>'خیلی کم','sm'=>'کم','md'=>'متوسط','lg'=>'زیاد','xl'=>'خیلی زیاد'] as $v => $l)
                                    <option value="{{ $v }}" {{ old('section_spacing', $settings->section_spacing ?? 'md') === $v ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                            <button type="submit" class="btn btn-primary" style="font-size:0.83rem;padding:0.45rem 1.25rem;">
                                ذخیره و اعمال رنگ‌های سفارشی
                            </button>
                            @if(!$isSiteCustom)
                            <p style="font-size:0.78rem;color:#7c3aed;margin:0;background:#f5f3ff;padding:0.3rem 0.7rem;border-radius:0.4rem;border:1px solid #ddd6fe;">
                                رنگ‌های پریست «{{ $activeSitePreset }}» نمایش داده می‌شوند. پس از ذخیره، سایت به حالت سفارشی می‌رود.
                            </p>
                            @endif
                        </div>
                    </form>
                    <div style="margin-top:0.75rem;padding-top:0.75rem;border-top:1px solid #f3f4f6;">
                        <form action="{{ route('admin.theme-design.reset-custom-colors') }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" onclick="return confirm('آیا می‌خواهید همه رنگ‌های سفارشی سایت به مقادیر پیش‌فرض سیستم بازگردانی شوند؟')"
                                style="font-size:0.75rem;padding:0.35rem 0.875rem;border-radius:0.5rem;border:1px solid #fca5a5;background:#fef2f2;color:#991b1b;cursor:pointer;font-weight:600;">
                                بازگردانی رنگ‌ها به پیش‌فرض سیستم
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

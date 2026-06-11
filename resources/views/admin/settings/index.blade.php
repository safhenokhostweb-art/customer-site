@extends('admin.layout')
@section('title', 'تنظیمات')
@section('header', 'تنظیمات سایت')
@section('content')
<div class="admin-page">
    <div class="card">
        <div class="card-header">تنظیمات عمومی و نمایش</div>
        <div class="card-body">
            <form action="{{ route('admin.settings.update') }}" method="post" class="admin-form-stack">
                @csrf

                <div class="form-group">
                    <label class="form-label">نام سایت</label>
                    <input type="text" name="site_name" value="{{ old('site_name', $siteName) }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">قالب</label>
                    <select name="theme">
                        <option value="default" {{ ($theme ?? 'default') === 'default' ? 'selected' : '' }}>پیش‌فرض</option>
                        <option value="minimal" {{ ($theme ?? '') === 'minimal' ? 'selected' : '' }}>مینیمال</option>
                        <option value="dark" {{ ($theme ?? '') === 'dark' ? 'selected' : '' }}>تیره</option>
                    </select>
                </div>

                <hr>
                <h3 class="admin-page-title">رنگ نوار کناری پنل ادمین و حساب کاربری</h3>
                <p style="font-size:0.9rem;color:#64748b;margin:0 0 1rem;">پس‌زمینهٔ آبی ملایم و متن خوانا؛ مقدار هر فیلد به صورت هگز شش‌رقمی (مثلاً <code>#5a7fa8</code>).</p>
                @php $sbSwatches = ['#5a7fa8','#456f94','#7dd3fc','#93c5fd','#38bdf8','#2563eb','#64748b','#f1f6fb','#e8f4fc','#8aa9c4']; @endphp
                <div class="admin-form-grid-2" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem;">
                    @foreach($adminSidebarThemeLabels ?? [] as $key => $sbLabel)
                        @php
                            $sbDef = \App\Helpers\AdminSidebarTheme::KEYS[$key] ?? '#000000';
                            $sbVal = old($key, $adminSidebarTheme[$key] ?? $sbDef);
                            $sbHex = is_string($sbVal) && preg_match('/^#[0-9A-Fa-f]{6}$/', $sbVal);
                        @endphp
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">{{ $sbLabel }}</label>
                            <div style="display:flex;align-items:center;gap:0.5rem;">
                                @if($sbHex)
                                    <input type="color" id="sb_color_{{ $key }}" value="{{ $sbVal }}" style="width:3rem;height:2.5rem;padding:0;border:1px solid #cbd5e1;border-radius:6px;cursor:pointer;" title="{{ $key }}">
                                @endif
                                <input type="text" name="{{ $key }}" value="{{ $sbVal }}" placeholder="{{ $sbDef }}" pattern="#[0-9A-Fa-f]{6}" maxlength="7" style="flex:1;" class="font-mono">
                            </div>
                            <div style="display:flex;flex-wrap:wrap;gap:0.25rem;margin-top:0.35rem;">
                                @foreach($sbSwatches as $c)
                                    <button type="button" style="width:1.25rem;height:1.25rem;border:1px solid #e2e8f0;border-radius:4px;padding:0;cursor:pointer;background:{{ $c }}" title="{{ $c }}" aria-label="{{ $c }}" onclick="var w=this.closest('div').previousElementSibling;var col=w.querySelector('input[type=color]');var t=w.querySelector('input[type=text]');if(col)col.value='{{ $c }}';if(t)t.value='{{ $c }}';"></button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <hr>
                <h3 class="admin-page-title">هدر و فوتر سایت (سازنده بصری)</h3>

                <div class="form-group">
                    <label class="form-label">قالب هدر</label>
                    <select name="builder_header_template">
                        @foreach($headerOptions ?? [] as $value => $label)
                            <option value="{{ $value }}" {{ old('builder_header_template', $builderHeaderTemplate ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">قالب فوتر</label>
                    <select name="builder_footer_template">
                        @foreach($footerOptions ?? [] as $value => $label)
                            <option value="{{ $value }}" {{ old('builder_footer_template', $builderFooterTemplate ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <label class="admin-form-inline-check">
                    <input type="checkbox" name="builder_sticky_header" value="1" {{ ($builderStickyHeader ?? true) ? 'checked' : '' }}>
                    هدر چسبان (Sticky)
                </label>

                <hr>
                <h3 class="admin-page-title">بلوک درباره ما (صفحه خانه)</h3>

                <label class="admin-form-inline-check">
                    <input type="checkbox" name="about_us_enabled" value="1" {{ ($aboutUsEnabled ?? false) ? 'checked' : '' }}>
                    نمایش بلوک درباره ما
                </label>

                <div class="form-group">
                    <label class="form-label">عنوان</label>
                    <input type="text" name="about_us_title" value="{{ old('about_us_title', $aboutUsTitle ?? '') }}" placeholder="درباره ما">
                </div>

                <div class="form-group">
                    <label class="form-label">متن</label>
                    <textarea id="settings-about-us-editor" name="about_us_text" class="w-full min-h-[200px]">{{ old('about_us_text', $aboutUsText ?? '') }}</textarea>
                </div>

                <hr>
                <h3 class="admin-page-title">اعلان‌های حساب کاربری</h3>

                <div class="form-group">
                    <label class="form-label">موقعیت زنگوله اعلان در پروفایل کاربر</label>
                    <select name="notification_bell_position">
                        <option value="bottom-right" {{ ($notificationBellPosition ?? 'bottom-right') === 'bottom-right' ? 'selected' : '' }}>پایین راست</option>
                        <option value="bottom-left" {{ ($notificationBellPosition ?? '') === 'bottom-left' ? 'selected' : '' }}>پایین چپ</option>
                        <option value="top-right" {{ ($notificationBellPosition ?? '') === 'top-right' ? 'selected' : '' }}>بالا راست</option>
                        <option value="top-left" {{ ($notificationBellPosition ?? '') === 'top-left' ? 'selected' : '' }}>بالا چپ</option>
                    </select>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary">ذخیره تنظیمات</button>
                </div>
            </form>
        </div>
    </div>

    @php
        $tm = $themeMeta ?? [];
    @endphp
    {{-- ── Favicon Upload ──────────────────────────────────────────────────── --}}
    @php $currentFavicon = \App\Models\Setting::get('site_favicon', ''); @endphp
    <div class="card">
        <div class="card-header">آیکن مرورگر (Favicon)</div>
        <div class="card-body">
            <p style="font-size:.875rem;color:#64748b;margin:0 0 1.25rem">
                آیکنی که در تب مرورگر، نشانک‌ها و نتایج جستجو نمایش داده می‌شود.<br>
                فرمت‌های قابل‌قبول: <code>ico، png، jpg، gif، webp، svg</code> — حداکثر ۵۱۲ کیلوبایت
            </p>

            @if($currentFavicon && \Illuminate\Support\Facades\Storage::disk('public')->exists($currentFavicon))
            <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.25rem;padding:.75rem 1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:.625rem">
                <img src="{{ Storage::url($currentFavicon) }}" alt="favicon فعلی"
                     style="width:32px;height:32px;object-fit:contain;border:1px solid #e2e8f0;border-radius:4px;background:#fff;padding:2px">
                <div>
                    <div style="font-size:.875rem;font-weight:600;color:#374151">آیکن فعلی</div>
                    <div style="font-size:.78rem;color:#94a3b8;font-family:monospace">{{ $currentFavicon }}</div>
                </div>
            </div>
            @else
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;padding:.75rem 1rem;background:#fafafa;border:1px dashed #d1d5db;border-radius:.625rem;color:#94a3b8;font-size:.875rem">
                <svg style="width:1.5rem;height:1.5rem;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                هنوز آیکنی آپلود نشده است.
            </div>
            @endif

            <form action="{{ route('admin.settings.favicon') }}" method="POST" enctype="multipart/form-data"
                  x-data="{ file: null, preview: null }" style="display:flex;flex-direction:column;gap:1rem">
                @csrf

                <div>
                    <label class="form-label">فایل آیکن جدید</label>
                    <input type="file"
                           name="favicon"
                           accept=".ico,.png,.jpg,.jpeg,.gif,.webp,.svg"
                           style="display:block;width:100%;padding:.5rem;border:1px solid #e2e8f0;border-radius:.625rem;font-size:.875rem;cursor:pointer"
                           x-on:change="
                               file = $event.target.files[0];
                               if (file) {
                                   const r = new FileReader();
                                   r.onload = e => preview = e.target.result;
                                   r.readAsDataURL(file);
                               }
                           ">
                    @error('favicon')
                        <p style="margin-top:.375rem;font-size:.8rem;color:#dc2626">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Live preview --}}
                <div x-show="preview" style="display:none;align-items:center;gap:.75rem;padding:.75rem 1rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:.625rem" x-cloak>
                    <img :src="preview" alt="پیش‌نمایش"
                         style="width:32px;height:32px;object-fit:contain;border:1px solid #d1fae5;border-radius:4px;background:#fff;padding:2px">
                    <span style="font-size:.875rem;color:#166534;font-weight:500" x-text="'پیش‌نمایش: ' + (file ? file.name : '')"></span>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        آپلود آیکن
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">اطلاعات تم فعال (Theme Builder)</div>
        <div class="card-body">
            @if(!empty($tm))
                <ul style="list-style:none; padding:0; margin:0; font-size:.9rem; color:#374151; line-height:1.9;">
                    <li><strong>نام تم:</strong> {{ $tm['name'] ?? '—' }}</li>
                    <li><strong>شناسه تم:</strong> <code>{{ $tm['id'] ?? '—' }}</code></li>
                    <li><strong>نسخه نصب‌شده:</strong> {{ $tm['version'] ?? '1.0.0' }}</li>
                    @if(!empty($tm['release_date']))
                        <li><strong>تاریخ انتشار:</strong> {{ \App\Helpers\Jalali::formatFa(\Carbon\Carbon::parse($tm['release_date']), 'Y/m/d') }}</li>
                    @endif
                    @if(!empty($tm['required_platform_version']))
                        <li><strong>حداقل نسخه پلتفرم:</strong> {{ $tm['required_platform_version'] }}</li>
                    @endif
                    @if(!empty($tm['supported_modules']) && is_array($tm['supported_modules']))
                        <li><strong>ماژول‌های پشتیبانی‌شده:</strong> {{ implode('، ', $tm['supported_modules']) }}</li>
                    @endif
                </ul>
                @if(!empty($tm['changelog']) && is_array($tm['changelog']))
                    <details style="margin-top:.75rem; font-size:.9rem;">
                        <summary style="cursor:pointer;">تاریخچه بروزرسانی تم</summary>
                        <ul style="margin-top:.5rem; padding-right:1.25rem;">
                            @foreach($tm['changelog'] as $version => $changes)
                                <li>
                                    <strong>نسخه {{ is_string($version) ? $version : ($tm['version'] ?? '1.0.0') }}:</strong>
                                    @if(is_array($changes))
                                        <ul style="margin-top:.25rem; padding-right:1rem;">
                                            @foreach($changes as $c)
                                                <li>{{ $c }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span>{{ $changes }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
                <p class="admin-muted-note" style="margin-top:.75rem;">
                    برای بررسی آخرین نسخه و بروزرسانی امن، از بخش «لایسنس → بررسی بروزرسانی» استفاده کنید.
                </p>
            @else
                <p class="admin-muted-note">اطلاعات تم فعال از پلتفرم در دسترس نیست (لایسنس یا ارتباط با پلتفرم را بررسی کنید).</p>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">ماژول‌ها (فعال‌سازی از طریق تنظیمات)</div>
        <div class="card-body">
            <p class="admin-muted-note">فعال یا غیرفعال کردن هر ماژول از طریق فایل <code>.env</code> انجام می‌شود. پس از تغییر، کش را پاک کنید یا سرور را یک‌بار ریستارت کنید.</p>
            <ul style="list-style:none; padding:0; margin-top:.75rem;">
                @foreach($modules as $m)
                    <li style="padding:.5rem 0; border-bottom:1px solid #eef2f7;">
                        <strong>{{ $m['name'] }}</strong> ({{ $m['key'] }})
                        — {{ $m['enabled'] ? 'فعال' : 'غیرفعال' }}
                        <span class="admin-muted-note"> — env: {{ config('modules.modules.'.$m['key'].'.env', 'MODULE_'.strtoupper($m['key']).'_ENABLED') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@include('admin.partials.tinymce-field-init', ['selector' => '#settings-about-us-editor', 'uploadContext' => 'featured', 'height' => 360])
@endsection

@extends('admin.layout')
@section('title', 'طھظ†ط¸غŒظ…ط§طھ')
@section('header', 'طھظ†ط¸غŒظ…ط§طھ ط³ط§غŒطھ')
@section('content')
<div class="admin-page">
    <div class="card">
        <div class="card-header">طھظ†ط¸غŒظ…ط§طھ ط¹ظ…ظˆظ…غŒ ظˆ ظ†ظ…ط§غŒط´</div>
        <div class="card-body">
            <form action="{{ route('admin.settings.update') }}" method="post" class="admin-form-stack">
                @csrf

                <div class="form-group">
                    <label class="form-label">ظ†ط§ظ… ط³ط§غŒطھ</label>
                    <input type="text" name="site_name" value="{{ old('site_name', $siteName) }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">ظ‚ط§ظ„ط¨</label>
                    <select name="theme">
                        <option value="default" {{ ($theme ?? 'default') === 'default' ? 'selected' : '' }}>ظ¾غŒط´â€Œظپط±ط¶</option>
                        <option value="minimal" {{ ($theme ?? '') === 'minimal' ? 'selected' : '' }}>ظ…غŒظ†غŒظ…ط§ظ„</option>
                        <option value="dark" {{ ($theme ?? '') === 'dark' ? 'selected' : '' }}>طھغŒط±ظ‡</option>
                    </select>
                </div>

                <hr>
                <h3 class="admin-page-title">ط±ظ†ع¯ ظ†ظˆط§ط± ع©ظ†ط§ط±غŒ ظ¾ظ†ظ„ ط§ط¯ظ…غŒظ† ظˆ ط­ط³ط§ط¨ ع©ط§ط±ط¨ط±غŒ</h3>
                <p style="font-size:0.9rem;color:#64748b;margin:0 0 1rem;">ظ¾ط³â€Œط²ظ…غŒظ†ظ‡ظ” ط¢ط¨غŒ ظ…ظ„ط§غŒظ… ظˆ ظ…طھظ† ط®ظˆط§ظ†ط§ط› ظ…ظ‚ط¯ط§ط± ظ‡ط± ظپغŒظ„ط¯ ط¨ظ‡ طµظˆط±طھ ظ‡ع¯ط² ط´ط´â€Œط±ظ‚ظ…غŒ (ظ…ط«ظ„ط§ظ‹ <code>#5a7fa8</code>).</p>
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
                <h3 class="admin-page-title">ظ‡ط¯ط± ظˆ ظپظˆطھط± ط³ط§غŒطھ (ط³ط§ط²ظ†ط¯ظ‡ ط¨طµط±غŒ)</h3>

                <div class="form-group">
                    <label class="form-label">ظ‚ط§ظ„ط¨ ظ‡ط¯ط±</label>
                    <select name="builder_header_template">
                        @foreach($headerOptions ?? [] as $value => $label)
                            <option value="{{ $value }}" {{ old('builder_header_template', $builderHeaderTemplate ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">ظ‚ط§ظ„ط¨ ظپظˆطھط±</label>
                    <select name="builder_footer_template">
                        @foreach($footerOptions ?? [] as $value => $label)
                            <option value="{{ $value }}" {{ old('builder_footer_template', $builderFooterTemplate ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <label class="admin-form-inline-check">
                    <input type="checkbox" name="builder_sticky_header" value="1" {{ ($builderStickyHeader ?? true) ? 'checked' : '' }}>
                    ظ‡ط¯ط± ع†ط³ط¨ط§ظ† (Sticky)
                </label>

                <hr>
                <h3 class="admin-page-title">ط¨ظ„ظˆع© ط¯ط±ط¨ط§ط±ظ‡ ظ…ط§ (طµظپط­ظ‡ ط®ط§ظ†ظ‡)</h3>

                <label class="admin-form-inline-check">
                    <input type="checkbox" name="about_us_enabled" value="1" {{ ($aboutUsEnabled ?? false) ? 'checked' : '' }}>
                    ظ†ظ…ط§غŒط´ ط¨ظ„ظˆع© ط¯ط±ط¨ط§ط±ظ‡ ظ…ط§
                </label>

                <div class="form-group">
                    <label class="form-label">ط¹ظ†ظˆط§ظ†</label>
                    <input type="text" name="about_us_title" value="{{ old('about_us_title', $aboutUsTitle ?? '') }}" placeholder="ط¯ط±ط¨ط§ط±ظ‡ ظ…ط§">
                </div>

                <div class="form-group">
                    <label class="form-label">ظ…طھظ†</label>
                    <textarea id="settings-about-us-editor" name="about_us_text" class="w-full min-h-[200px]">{{ old('about_us_text', $aboutUsText ?? '') }}</textarea>
                </div>

                <hr>
                <h3 class="admin-page-title">ط§ط¹ظ„ط§ظ†â€Œظ‡ط§غŒ ط­ط³ط§ط¨ ع©ط§ط±ط¨ط±غŒ</h3>

                <div class="form-group">
                    <label class="form-label">ظ…ظˆظ‚ط¹غŒطھ ط²ظ†ع¯ظˆظ„ظ‡ ط§ط¹ظ„ط§ظ† ط¯ط± ظ¾ط±ظˆظپط§غŒظ„ ع©ط§ط±ط¨ط±</label>
                    <select name="notification_bell_position">
                        <option value="bottom-right" {{ ($notificationBellPosition ?? 'bottom-right') === 'bottom-right' ? 'selected' : '' }}>ظ¾ط§غŒغŒظ† ط±ط§ط³طھ</option>
                        <option value="bottom-left" {{ ($notificationBellPosition ?? '') === 'bottom-left' ? 'selected' : '' }}>ظ¾ط§غŒغŒظ† ع†ظ¾</option>
                        <option value="top-right" {{ ($notificationBellPosition ?? '') === 'top-right' ? 'selected' : '' }}>ط¨ط§ظ„ط§ ط±ط§ط³طھ</option>
                        <option value="top-left" {{ ($notificationBellPosition ?? '') === 'top-left' ? 'selected' : '' }}>ط¨ط§ظ„ط§ ع†ظ¾</option>
                    </select>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary">ط°ط®غŒط±ظ‡ طھظ†ط¸غŒظ…ط§طھ</button>
                </div>
            </form>
        </div>
    </div>

    @php
        $tm = $themeMeta ?? [];
    @endphp
    {{-- â”€â”€ Favicon Upload â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
    @php $currentFavicon = \App\Models\Setting::get('site_favicon', ''); @endphp
    <div class="card">
        <div class="card-header">ط¢غŒع©ظ† ظ…ط±ظˆط±ع¯ط± (Favicon)</div>
        <div class="card-body">
            <p style="font-size:.875rem;color:#64748b;margin:0 0 1.25rem">
                ط¢غŒع©ظ†غŒ ع©ظ‡ ط¯ط± طھط¨ ظ…ط±ظˆط±ع¯ط±طŒ ظ†ط´ط§ظ†ع©â€Œظ‡ط§ ظˆ ظ†طھط§غŒط¬ ط¬ط³طھط¬ظˆ ظ†ظ…ط§غŒط´ ط¯ط§ط¯ظ‡ ظ…غŒâ€Œط´ظˆط¯.<br>
                ظپط±ظ…طھâ€Œظ‡ط§غŒ ظ‚ط§ط¨ظ„â€Œظ‚ط¨ظˆظ„: <code>icoطŒ pngطŒ jpgطŒ gifطŒ webpطŒ svg</code> â€” ط­ط¯ط§ع©ط«ط± غµغ±غ² ع©غŒظ„ظˆط¨ط§غŒطھ
            </p>

            @if($currentFavicon && \Illuminate\Support\Facades\Storage::disk('public')->exists($currentFavicon))
            <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.25rem;padding:.75rem 1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:.625rem">
                <img src="{{ Storage::url($currentFavicon) }}" alt="favicon ظپط¹ظ„غŒ"
                     style="width:32px;height:32px;object-fit:contain;border:1px solid #e2e8f0;border-radius:4px;background:#fff;padding:2px">
                <div>
                    <div style="font-size:.875rem;font-weight:600;color:#374151">ط¢غŒع©ظ† ظپط¹ظ„غŒ</div>
                    <div style="font-size:.78rem;color:#94a3b8;font-family:monospace">{{ $currentFavicon }}</div>
                </div>
            </div>
            @else
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;padding:.75rem 1rem;background:#fafafa;border:1px dashed #d1d5db;border-radius:.625rem;color:#94a3b8;font-size:.875rem">
                <x-core-ui::icon name="settings" style="width:1.5rem;height:1.5rem;flex-shrink:0" />
                ظ‡ظ†ظˆط² ط¢غŒع©ظ†غŒ ط¢ظ¾ظ„ظˆط¯ ظ†ط´ط¯ظ‡ ط§ط³طھ.
            </div>
            @endif

            <form action="{{ route('admin.settings.favicon') }}" method="POST" enctype="multipart/form-data"
                  x-data="{ file: null, preview: null }" style="display:flex;flex-direction:column;gap:1rem">
                @csrf

                <div>
                    <label class="form-label">ظپط§غŒظ„ ط¢غŒع©ظ† ط¬ط¯غŒط¯</label>
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
                    <img :src="preview" alt="ظ¾غŒط´â€Œظ†ظ…ط§غŒط´"
                         style="width:32px;height:32px;object-fit:contain;border:1px solid #d1fae5;border-radius:4px;background:#fff;padding:2px">
                    <span style="font-size:.875rem;color:#166534;font-weight:500" x-text="'ظ¾غŒط´â€Œظ†ظ…ط§غŒط´: ' + (file ? file.name : '')"></span>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary">
                        <x-core-ui::icon name="download" class="w-4 h-4" />
                        ط¢ظ¾ظ„ظˆط¯ ط¢غŒع©ظ†
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">ط§ط·ظ„ط§ط¹ط§طھ طھظ… ظپط¹ط§ظ„ (Theme Builder)</div>
        <div class="card-body">
            @if(!empty($tm))
                <ul style="list-style:none; padding:0; margin:0; font-size:.9rem; color:#374151; line-height:1.9;">
                    <li><strong>ظ†ط§ظ… طھظ…:</strong> {{ $tm['name'] ?? 'â€”' }}</li>
                    <li><strong>ط´ظ†ط§ط³ظ‡ طھظ…:</strong> <code>{{ $tm['id'] ?? 'â€”' }}</code></li>
                    <li><strong>ظ†ط³ط®ظ‡ ظ†طµط¨â€Œط´ط¯ظ‡:</strong> {{ $tm['version'] ?? '1.0.0' }}</li>
                    @if(!empty($tm['release_date']))
                        <li><strong>طھط§ط±غŒط® ط§ظ†طھط´ط§ط±:</strong> {{ \App\Helpers\Jalali::formatFa(\Carbon\Carbon::parse($tm['release_date']), 'Y/m/d') }}</li>
                    @endif
                    @if(!empty($tm['required_platform_version']))
                        <li><strong>ط­ط¯ط§ظ‚ظ„ ظ†ط³ط®ظ‡ ظ¾ظ„طھظپط±ظ…:</strong> {{ $tm['required_platform_version'] }}</li>
                    @endif
                    @if(!empty($tm['supported_modules']) && is_array($tm['supported_modules']))
                        <li><strong>ظ…ط§عکظˆظ„â€Œظ‡ط§غŒ ظ¾ط´طھغŒط¨ط§ظ†غŒâ€Œط´ط¯ظ‡:</strong> {{ implode('طŒ ', $tm['supported_modules']) }}</li>
                    @endif
                </ul>
                @if(!empty($tm['changelog']) && is_array($tm['changelog']))
                    <details style="margin-top:.75rem; font-size:.9rem;">
                        <summary style="cursor:pointer;">طھط§ط±غŒط®ع†ظ‡ ط¨ط±ظˆط²ط±ط³ط§ظ†غŒ طھظ…</summary>
                        <ul style="margin-top:.5rem; padding-right:1.25rem;">
                            @foreach($tm['changelog'] as $version => $changes)
                                <li>
                                    <strong>ظ†ط³ط®ظ‡ {{ is_string($version) ? $version : ($tm['version'] ?? '1.0.0') }}:</strong>
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
                    ط¨ط±ط§غŒ ط¨ط±ط±ط³غŒ ط¢ط®ط±غŒظ† ظ†ط³ط®ظ‡ ظˆ ط¨ط±ظˆط²ط±ط³ط§ظ†غŒ ط§ظ…ظ†طŒ ط§ط² ط¨ط®ط´ آ«ظ„ط§غŒط³ظ†ط³ â†’ ط¨ط±ط±ط³غŒ ط¨ط±ظˆط²ط±ط³ط§ظ†غŒآ» ط§ط³طھظپط§ط¯ظ‡ ع©ظ†غŒط¯.
                </p>
            @else
                <p class="admin-muted-note">ط§ط·ظ„ط§ط¹ط§طھ طھظ… ظپط¹ط§ظ„ ط§ط² ظ¾ظ„طھظپط±ظ… ط¯ط± ط¯ط³طھط±ط³ ظ†غŒط³طھ (ظ„ط§غŒط³ظ†ط³ غŒط§ ط§ط±طھط¨ط§ط· ط¨ط§ ظ¾ظ„طھظپط±ظ… ط±ط§ ط¨ط±ط±ط³غŒ ع©ظ†غŒط¯).</p>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">ظ…ط§عکظˆظ„â€Œظ‡ط§ (ظپط¹ط§ظ„â€Œط³ط§ط²غŒ ط§ط² ط·ط±غŒظ‚ طھظ†ط¸غŒظ…ط§طھ)</div>
        <div class="card-body">
            <p class="admin-muted-note">ظپط¹ط§ظ„ غŒط§ ط؛غŒط±ظپط¹ط§ظ„ ع©ط±ط¯ظ† ظ‡ط± ظ…ط§عکظˆظ„ ط§ط² ط·ط±غŒظ‚ ظپط§غŒظ„ <code>.env</code> ط§ظ†ط¬ط§ظ… ظ…غŒâ€Œط´ظˆط¯. ظ¾ط³ ط§ط² طھط؛غŒغŒط±طŒ ع©ط´ ط±ط§ ظ¾ط§ع© ع©ظ†غŒط¯ غŒط§ ط³ط±ظˆط± ط±ط§ غŒع©â€Œط¨ط§ط± ط±غŒط³طھط§ط±طھ ع©ظ†غŒط¯.</p>
            <ul style="list-style:none; padding:0; margin-top:.75rem;">
                @foreach($modules as $m)
                    <li style="padding:.5rem 0; border-bottom:1px solid #eef2f7;">
                        <strong>{{ $m['name'] }}</strong> ({{ $m['key'] }})
                        â€” {{ $m['enabled'] ? 'ظپط¹ط§ظ„' : 'ط؛غŒط±ظپط¹ط§ظ„' }}
                        <span class="admin-muted-note"> â€” env: {{ config('modules.modules.'.$m['key'].'.env', 'MODULE_'.strtoupper($m['key']).'_ENABLED') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@include('admin.partials.tinymce-field-init', ['selector' => '#settings-about-us-editor', 'uploadContext' => 'featured', 'height' => 360])
@endsection




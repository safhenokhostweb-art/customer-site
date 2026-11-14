{{--
    module:solar_calculator — the four-step solar calculator widget.
    Rendered by App\Modules\SolarCalculator\Canvas\SolarCalculatorWidget.
    Styling uses the theme tokens (--tb-*) only; the accent defaults to the
    theme primary colour. Text is always escaped; the only HTML the script
    inserts is the server-formatted money strings and the catalog icons.
--}}
@php
    $types = [
        'on_grid' => ['label' => $ui['ongrid_label'], 'desc' => $ui['ongrid_desc'], 'image' => $ui['ongrid_image'], 'icon' => 'plug'],
        'off_grid' => ['label' => $ui['offgrid_label'], 'desc' => $ui['offgrid_desc'], 'image' => $ui['offgrid_image'], 'icon' => 'sun'],
        'hybrid' => ['label' => $ui['hybrid_label'], 'desc' => $ui['hybrid_desc'], 'image' => $ui['hybrid_image'], 'icon' => 'battery-charging'],
    ];
    $icon = static fn (string $name) => \SaaSPlatform\ThemeRuntime\Support\IconLibrary::svg($name);
    $config = [
        'calculateUrl' => route('solar-calculator.calculate'),
        'quoteUrl' => route('solar-calculator.quote.store'),
        'csrf' => csrf_token(),
        'mode' => $mode,
        'loggedIn' => $loggedIn,
        'loginUrl' => $loginUrl,
        'preview' => $editorMode,
        'showPrice' => $showPrice,
        'texts' => [
            'loginRequired' => $ui['login_required_text'],
            'btnLogin' => $ui['btn_login'],
            'priceHidden' => $ui['price_hidden_text'],
            'priceUnavailable' => $ui['price_unavailable_text'],
            'saving' => $ui['saving_label'],
            'payback' => $ui['payback_label'],
            'paybackUnit' => 'سال',
            'roofWarning' => $ui['roof_warning_text'],
            'genericError' => 'خطایی رخ داد؛ لطفاً دوباره تلاش کنید.',
            'networkError' => 'ارتباط با سرور برقرار نشد؛ اتصال اینترنت را بررسی کنید.',
            'previewBlocked' => 'در پیش‌نمایش ویرایشگر ثبت درخواست غیرفعال است؛ در صفحهٔ منتشرشده کار می‌کند.',
            'required' => 'این فیلد الزامی است.',
            'busy' => 'در حال محاسبه…',
            'sending' => 'در حال ثبت…',
        ],
    ];
@endphp
<section id="{{ $anchor }}" class="sc-calc sc-calc--{{ $layout }}" data-sc-calc data-block-id="{{ $id }}"
    data-config="{{ json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
    @if($accent) style="--sc-accent: {{ $accent }};" @endif
    aria-labelledby="{{ $anchor }}-title">
    @if($ui['banner_image'] !== '')
        <div class="sc-banner"><img src="{{ $ui['banner_image'] }}" alt="" loading="lazy" decoding="async"></div>
    @endif

    <div class="sc-shell">
        <div class="sc-main">
            <header class="sc-head">
                @if($ui['eyebrow'] !== '')<span class="sc-eyebrow">{!! $icon('sparkles') !!}{{ $ui['eyebrow'] }}</span>@endif
                <h2 class="sc-title" id="{{ $anchor }}-title">{{ $ui['title'] }}</h2>
                @if($ui['subtitle'] !== '')<p class="sc-subtitle">{{ $ui['subtitle'] }}</p>@endif
                @if($editorMode)<p class="sc-preview-badge">پیش‌نمایش ویرایشگر — ثبت درخواست در این حالت غیرفعال است.</p>@endif
            </header>

            @if($showSteps)
                <ol class="sc-steps" aria-label="مراحل محاسبه">
                    @foreach([1, 2, 3, 4] as $n)
                        <li class="sc-step{{ $n === 1 ? ' is-current' : '' }}" data-step-dot="{{ $n }}" @if($n === 1) aria-current="step" @endif>
                            <span class="sc-step-num">{{ \SaaSPlatform\ThemeRuntime\Support\PersianNumber::digits($n) }}</span>
                            <span class="sc-step-title">{{ $ui['step' . $n . '_title'] }}</span>
                        </li>
                    @endforeach
                </ol>
            @endif

            <form class="sc-form" novalidate data-sc-form>
                {{-- Step 1: system type --}}
                <fieldset class="sc-panel" data-step="1">
                    <legend class="sc-panel-title" tabindex="-1">{{ $ui['step1_title'] }}</legend>
                    @if($ui['step1_hint'] !== '')<p class="sc-hint">{{ $ui['step1_hint'] }}</p>@endif
                    <div class="sc-types" role="radiogroup">
                        @foreach($types as $value => $t)
                            <label class="sc-type">
                                <input type="radio" name="system_type" value="{{ $value }}" @checked($systemType === $value)>
                                <span class="sc-type-card">
                                    @if($t['image'] !== '')
                                        <img class="sc-type-img" src="{{ $t['image'] }}" alt="" loading="lazy" decoding="async">
                                    @else
                                        <span class="sc-type-icon">{!! $icon($t['icon']) !!}</span>
                                    @endif
                                    <span class="sc-type-label">{{ $t['label'] }}</span>
                                    <span class="sc-type-desc">{{ $t['desc'] }}</span>
                                    <span class="sc-type-check" aria-hidden="true">{!! $icon('check') !!}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <div class="sc-nav">
                        <button type="button" class="sc-btn sc-btn--primary" data-sc-next>{{ $ui['btn_next'] }}{!! $icon('arrow-left') !!}</button>
                    </div>
                </fieldset>

                {{-- Step 2: consumption and location --}}
                <fieldset class="sc-panel" data-step="2" hidden>
                    <legend class="sc-panel-title" tabindex="-1">{{ $ui['step2_title'] }}</legend>
                    @if($ui['step2_hint'] !== '')<p class="sc-hint">{{ $ui['step2_hint'] }}</p>@endif

                    <div class="sc-segmented" role="radiogroup">
                        <label><input type="radio" name="consumption_mode" value="kwh" checked><span>{{ $ui['mode_kwh_label'] }}</span></label>
                        <label><input type="radio" name="consumption_mode" value="bill"><span>{{ $ui['mode_bill_label'] }}</span></label>
                    </div>

                    <div class="sc-grid">
                        <div class="sc-field" data-when-mode="kwh">
                            <label for="{{ $anchor }}-kwh">{{ $ui['monthly_kwh_label'] }}</label>
                            <div class="sc-input-wrap"><input id="{{ $anchor }}-kwh" name="monthly_kwh" inputmode="decimal" autocomplete="off" placeholder="۴۵۰"><span class="sc-suffix">kWh</span></div>
                            @if($ui['monthly_kwh_hint'] !== '')<small>{{ $ui['monthly_kwh_hint'] }}</small>@endif
                            <p class="sc-field-error" data-error-for="monthly_kwh"></p>
                        </div>
                        <div class="sc-field" data-when-mode="bill" hidden>
                            <label for="{{ $anchor }}-bill">{{ $ui['monthly_bill_label'] }}</label>
                            <div class="sc-input-wrap"><input id="{{ $anchor }}-bill" name="monthly_bill" inputmode="numeric" autocomplete="off" placeholder="۱٬۵۰۰٬۰۰۰" data-sc-money><span class="sc-suffix">تومان</span></div>
                            @if($ui['monthly_bill_hint'] !== '')<small>{{ $ui['monthly_bill_hint'] }}</small>@endif
                            <p class="sc-field-error" data-error-for="monthly_bill"></p>
                        </div>
                        <div class="sc-field">
                            <label for="{{ $anchor }}-region">{{ $ui['region_label'] }}</label>
                            <select id="{{ $anchor }}-region" name="region">
                                <option value="">انتخاب کنید</option>
                                @foreach($regions as $r)
                                    <option value="{{ $r['key'] }}">{{ $r['label'] }}</option>
                                @endforeach
                            </select>
                            <p class="sc-field-error" data-error-for="region"></p>
                        </div>
                        <div class="sc-field">
                            <label for="{{ $anchor }}-roof">{{ $ui['roof_area_label'] }}</label>
                            <div class="sc-input-wrap"><input id="{{ $anchor }}-roof" name="roof_area" inputmode="decimal" autocomplete="off"><span class="sc-suffix">m²</span></div>
                            @if($ui['roof_area_hint'] !== '')<small>{{ $ui['roof_area_hint'] }}</small>@endif
                            <p class="sc-field-error" data-error-for="roof_area"></p>
                        </div>
                        <div class="sc-field" data-when-type="hybrid" hidden>
                            <label for="{{ $anchor }}-backup">{{ $ui['backup_hours_label'] }}</label>
                            <div class="sc-input-wrap"><input id="{{ $anchor }}-backup" name="backup_hours" inputmode="decimal" autocomplete="off" value="{{ \SaaSPlatform\ThemeRuntime\Support\PersianNumber::digits((string) $backupHours) }}"><span class="sc-suffix">ساعت</span></div>
                            <p class="sc-field-error" data-error-for="backup_hours"></p>
                        </div>
                    </div>
                    <div class="sc-nav">
                        <button type="button" class="sc-btn sc-btn--ghost" data-sc-back>{!! $icon('arrow-right') !!}{{ $ui['btn_back'] }}</button>
                        <button type="button" class="sc-btn sc-btn--primary" data-sc-calculate>{!! $icon('calculator') !!}{{ $ui['btn_calculate'] }}</button>
                    </div>
                </fieldset>

                {{-- Step 3: results --}}
                <fieldset class="sc-panel" data-step="3" hidden>
                    <legend class="sc-panel-title" tabindex="-1">{{ $ui['results_title'] }}</legend>
                    @if($ui['step3_hint'] !== '')<p class="sc-hint">{{ $ui['step3_hint'] }}</p>@endif
                    <div class="sc-results" data-sc-results aria-live="polite"></div>
                    <p class="sc-warning" data-sc-roof hidden>{!! $icon('triangle-alert') !!}<span></span></p>
                    <div class="sc-price" data-sc-price hidden>
                        <span class="sc-price-label">{{ $ui['price_title'] }}</span>
                        <strong class="sc-price-value" data-sc-price-value></strong>
                        <div class="sc-price-extra" data-sc-price-extra></div>
                    </div>
                    @if($ui['results_note'] !== '')<p class="sc-note">{!! $icon('info') !!}<span>{{ $ui['results_note'] }}</span></p>@endif
                    <div class="sc-nav">
                        <button type="button" class="sc-btn sc-btn--ghost" data-sc-back>{!! $icon('arrow-right') !!}{{ $ui['btn_back'] }}</button>
                        <button type="button" class="sc-btn sc-btn--primary" data-sc-next>{!! $icon('file-text') !!}{{ $ui['btn_quote'] }}</button>
                    </div>
                </fieldset>

                {{-- Step 4: contact + quote --}}
                <fieldset class="sc-panel" data-step="4" hidden>
                    <legend class="sc-panel-title" tabindex="-1">{{ $ui['contact_title'] }}</legend>
                    <div data-sc-contact>
                        @if($ui['contact_text'] !== '')<p class="sc-hint">{{ $ui['contact_text'] }}</p>@endif
                        <div class="sc-grid">
                            <div class="sc-field">
                                <label for="{{ $anchor }}-name">{{ $ui['name_label'] }}</label>
                                <input id="{{ $anchor }}-name" name="name" autocomplete="name" value="{{ $user?->name }}">
                                <p class="sc-field-error" data-error-for="name"></p>
                            </div>
                            <div class="sc-field">
                                <label for="{{ $anchor }}-mobile">{{ $ui['mobile_label'] }}</label>
                                <input id="{{ $anchor }}-mobile" name="mobile" inputmode="tel" autocomplete="tel" dir="ltr" placeholder="۰۹۱۲۱۲۳۴۵۶۷" value="{{ $user?->mobile }}">
                                <p class="sc-field-error" data-error-for="mobile"></p>
                            </div>
                            <div class="sc-field">
                                <label for="{{ $anchor }}-email">{{ $ui['email_label'] }}@if($requireEmail) <span aria-hidden="true">*</span>@endif</label>
                                <input id="{{ $anchor }}-email" name="email" type="email" autocomplete="email" dir="ltr" value="{{ $user?->email }}">
                                <p class="sc-field-error" data-error-for="email"></p>
                            </div>
                            <div class="sc-field sc-field--wide">
                                <label for="{{ $anchor }}-note">{{ $ui['note_label'] }}</label>
                                <textarea id="{{ $anchor }}-note" name="note" rows="2" maxlength="1000"></textarea>
                                <p class="sc-field-error" data-error-for="note"></p>
                            </div>
                        </div>
                        {{-- Honeypot: real visitors never see or fill it. --}}
                        <div class="sc-hp" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
                        @if($ui['privacy_text'] !== '')<p class="sc-privacy">{!! $icon('shield-check') !!}<span>{{ $ui['privacy_text'] }}</span></p>@endif
                        <div class="sc-nav">
                            <button type="button" class="sc-btn sc-btn--ghost" data-sc-back>{!! $icon('arrow-right') !!}{{ $ui['btn_back'] }}</button>
                            <button type="button" class="sc-btn sc-btn--primary" data-sc-submit>{!! $icon('check') !!}{{ $ui['btn_submit'] }}</button>
                        </div>
                    </div>
                    <div class="sc-success" data-sc-success hidden tabindex="-1">
                        <span class="sc-success-icon">{!! $icon('circle-check') !!}</span>
                        <h3>{{ $ui['success_title'] }}</h3>
                        <p>{{ $ui['success_text'] }}</p>
                        <p class="sc-quote-no" data-sc-quote-no></p>
                        <div class="sc-nav sc-nav--center">
                            <a class="sc-btn sc-btn--primary" data-sc-view href="#" target="_blank" rel="noopener">{!! $icon('file-text') !!}{{ $ui['btn_view_quote'] }}</a>
                            <a class="sc-btn sc-btn--outline" data-sc-print href="#" target="_blank" rel="noopener">{!! $icon('download') !!}{{ $ui['btn_print'] }}</a>
                            <button type="button" class="sc-btn sc-btn--ghost" data-sc-restart>{!! $icon('rotate-ccw') !!}{{ $ui['btn_restart'] }}</button>
                        </div>
                    </div>
                </fieldset>

                <div class="sc-login" data-sc-login hidden>
                    {!! $icon('lock') !!}<p>{{ $ui['login_required_text'] }}</p>
                    @if($loginUrl)<a class="sc-btn sc-btn--primary" href="{{ $loginUrl }}">{!! $icon('log-in') !!}{{ $ui['btn_login'] }}</a>@endif
                </div>
                <p class="sc-error" data-sc-error role="alert" hidden></p>
            </form>
        </div>

        @if($layout === 'split' && $ui['side_image'] !== '')
            <aside class="sc-side" aria-hidden="true"><img src="{{ $ui['side_image'] }}" alt="" loading="lazy" decoding="async"></aside>
        @endif
    </div>
</section>

@if($withAssets)
<style>
.sc-calc{--sc-accent:var(--tb-primary,#f59e0b);--sc-on-accent:var(--tb-on-primary,#fff);--sc-surface:var(--tb-surface,#fff);--sc-text:var(--tb-text,#0f172a);--sc-muted:var(--tb-text-muted,#64748b);--sc-border:var(--tb-border,#e2e8f0);--sc-radius:var(--tb-radius-card,1.125rem);--sc-r-sm:var(--tb-radius-md,.75rem);--sc-soft:color-mix(in srgb,var(--sc-accent) 10%,var(--sc-surface));--sc-ring:color-mix(in srgb,var(--sc-accent) 45%,transparent);color:var(--sc-text);font-family:var(--tb-font-body,Vazirmatn,Tahoma,sans-serif);direction:rtl;max-width:var(--tb-container,72rem);margin-inline:auto;padding-inline:1rem;padding-block:clamp(2rem,1.5rem + 2vw,3.5rem);scroll-margin-top:6rem}
.sc-calc *,.sc-calc *::before,.sc-calc *::after{box-sizing:border-box}
.sc-calc svg{width:1.15em;height:1.15em;flex:none}
.sc-banner{border-radius:var(--sc-radius);overflow:hidden;margin-block-end:1.25rem;aspect-ratio:21/6;background:var(--sc-soft)}
.sc-banner img{width:100%;height:100%;object-fit:cover;display:block}
.sc-shell{display:grid;gap:1.5rem;align-items:start}
.sc-calc--split .sc-shell{grid-template-columns:minmax(0,1fr)}
.sc-main{background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:var(--sc-radius);box-shadow:var(--tb-shadow-md,0 12px 32px -12px rgb(15 23 42/.18));padding:clamp(1.25rem,1rem + 1.5vw,2.25rem)}
.sc-head{text-align:center;margin-block-end:1.5rem}
.sc-eyebrow{display:inline-flex;align-items:center;gap:.4rem;font-size:var(--tb-caption-size,.8125rem);font-weight:700;color:var(--sc-accent);background:var(--sc-soft);padding:.35rem .85rem;border-radius:999px}
.sc-title{font-size:clamp(1.4rem,1.1rem + 1.2vw,2rem);font-weight:800;line-height:1.35;margin:.75rem 0 .5rem;color:var(--sc-text)}
.sc-subtitle{color:var(--sc-muted);margin:0 auto;max-width:42rem;line-height:1.9}
.sc-preview-badge{display:inline-block;margin-top:.75rem;font-size:.8rem;color:var(--tb-error,#b91c1c);background:color-mix(in srgb,var(--tb-error,#b91c1c) 8%,transparent);padding:.3rem .75rem;border-radius:.5rem}
.sc-steps{list-style:none;display:flex;gap:.5rem;margin:0 0 1.5rem;padding:0;counter-reset:none}
.sc-step{flex:0 0 auto;display:flex;align-items:center;gap:.5rem;padding:.55rem .6rem;border-radius:var(--sc-r-sm);background:color-mix(in srgb,var(--sc-text) 4%,transparent);color:var(--sc-muted);font-size:.82rem;font-weight:600;min-width:0;transition:background var(--tb-dur,280ms) var(--tb-ease-out,ease),color var(--tb-dur,280ms)}
.sc-step-num{flex:none;display:grid;place-items:center;width:1.75rem;height:1.75rem;border-radius:50%;background:var(--sc-surface);border:1px solid var(--sc-border);font-weight:800}
.sc-step-title{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:none}
.sc-step.is-current{background:var(--sc-soft);color:var(--sc-text);flex:1 1 auto}
.sc-step.is-current .sc-step-title{display:inline}
.sc-step.is-current .sc-step-num,.sc-step.is-done .sc-step-num{background:var(--sc-accent);border-color:var(--sc-accent);color:var(--sc-on-accent)}
.sc-form{margin:0}
.sc-panel{border:0;margin:0;padding:0;min-width:0;animation:sc-in var(--tb-dur,280ms) var(--tb-ease-out,ease)}
.sc-panel[hidden]{display:none}
.sc-panel-title{font-size:1.15rem;font-weight:800;padding:0;margin-block-end:.35rem;outline:none}
.sc-hint{color:var(--sc-muted);margin:0 0 1.25rem;line-height:1.8;font-size:.95rem}
.sc-types{display:grid;gap:.85rem;grid-template-columns:1fr}
.sc-type{position:relative;cursor:pointer;display:block}
.sc-type input{position:absolute;opacity:0;inset:0;pointer-events:none}
.sc-type-card{display:grid;grid-template-columns:auto 1fr;grid-template-areas:"media label" "media desc";column-gap:.9rem;row-gap:.2rem;align-items:center;height:100%;padding:1rem;border:1.5px solid var(--sc-border);border-radius:var(--sc-r-sm);background:var(--sc-surface);transition:border-color var(--tb-dur-fast,160ms),box-shadow var(--tb-dur-fast,160ms),transform var(--tb-dur-fast,160ms)}
.sc-type:hover .sc-type-card{border-color:color-mix(in srgb,var(--sc-accent) 55%,var(--sc-border));transform:translateY(-2px)}
.sc-type input:focus-visible+.sc-type-card{box-shadow:0 0 0 3px var(--sc-ring)}
.sc-type input:checked+.sc-type-card{border-color:var(--sc-accent);background:var(--sc-soft);box-shadow:0 10px 24px -14px var(--sc-ring)}
.sc-type-img,.sc-type-icon{grid-area:media;width:3.25rem;height:3.25rem;border-radius:.75rem;object-fit:cover}
.sc-type-icon{display:grid;place-items:center;background:var(--sc-soft);color:var(--sc-accent)}
.sc-type-icon svg{width:1.6rem;height:1.6rem}
.sc-type-label{grid-area:label;font-weight:800}
.sc-type-desc{grid-area:desc;color:var(--sc-muted);font-size:.85rem;line-height:1.7}
.sc-type-check{position:absolute;inset-block-start:.6rem;inset-inline-end:.6rem;display:grid;place-items:center;width:1.5rem;height:1.5rem;border-radius:50%;background:var(--sc-accent);color:var(--sc-on-accent);opacity:0;transform:scale(.6);transition:opacity var(--tb-dur-fast,160ms),transform var(--tb-dur-fast,160ms)}
.sc-type-check svg{width:.9rem;height:.9rem}
.sc-type input:checked+.sc-type-card .sc-type-check{opacity:1;transform:none}
.sc-segmented{display:inline-flex;gap:.25rem;padding:.25rem;border-radius:999px;background:color-mix(in srgb,var(--sc-text) 5%,transparent);margin-block-end:1.25rem;max-width:100%}
.sc-segmented label{position:relative;cursor:pointer}
.sc-segmented input{position:absolute;opacity:0}
.sc-segmented span{display:block;padding:.5rem 1rem;border-radius:999px;font-size:.88rem;font-weight:700;color:var(--sc-muted);transition:background var(--tb-dur-fast,160ms),color var(--tb-dur-fast,160ms)}
.sc-segmented input:checked+span{background:var(--sc-surface);color:var(--sc-text);box-shadow:var(--tb-shadow-sm,0 1px 2px rgb(15 23 42/.08))}
.sc-segmented input:focus-visible+span{box-shadow:0 0 0 3px var(--sc-ring)}
.sc-grid{display:grid;gap:1rem;grid-template-columns:1fr}
.sc-field{display:flex;flex-direction:column;gap:.4rem;min-width:0}
.sc-field[hidden]{display:none}
.sc-field label{font-weight:700;font-size:.9rem}
.sc-field small{color:var(--sc-muted);font-size:.78rem;line-height:1.6}
.sc-field input,.sc-field select,.sc-field textarea{width:100%;font:inherit;font-size:1rem;color:var(--sc-text);background:var(--sc-surface);border:1.5px solid var(--sc-border);border-radius:var(--sc-r-sm);padding:.7rem .9rem;min-height:2.9rem;transition:border-color var(--tb-dur-fast,160ms),box-shadow var(--tb-dur-fast,160ms)}
.sc-field textarea{resize:vertical;min-height:4.5rem}
.sc-field input:focus,.sc-field select:focus,.sc-field textarea:focus{outline:none;border-color:var(--sc-accent);box-shadow:0 0 0 3px var(--sc-ring)}
.sc-field.has-error input,.sc-field.has-error select,.sc-field.has-error textarea{border-color:var(--tb-error,#dc2626)}
.sc-input-wrap{position:relative}
.sc-input-wrap input{padding-inline-end:4.25rem}
.sc-suffix{position:absolute;inset-block:0;inset-inline-end:.9rem;display:flex;align-items:center;color:var(--sc-muted);font-size:.8rem;pointer-events:none}
.sc-field-error{margin:0;color:var(--tb-error,#dc2626);font-size:.8rem;min-height:0}
.sc-field-error:empty{display:none}
.sc-nav{display:flex;flex-wrap:wrap;gap:.65rem;justify-content:space-between;margin-top:1.5rem}
.sc-nav--center{justify-content:center}
.sc-nav>.sc-btn:only-child{margin-inline-start:auto}
.sc-btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;min-height:2.9rem;padding:.65rem 1.25rem;border-radius:var(--sc-r-sm);font:inherit;font-weight:800;font-size:.95rem;cursor:pointer;border:1.5px solid transparent;text-decoration:none;transition:transform var(--tb-dur-fast,160ms),box-shadow var(--tb-dur-fast,160ms),background var(--tb-dur-fast,160ms),opacity var(--tb-dur-fast,160ms)}
.sc-btn:focus-visible{outline:none;box-shadow:0 0 0 3px var(--sc-ring)}
.sc-btn--primary{background:var(--sc-accent);color:var(--sc-on-accent)}
.sc-btn--primary:hover{transform:translateY(-1px);box-shadow:0 10px 22px -12px var(--sc-accent)}
.sc-btn--outline{border-color:var(--sc-accent);color:var(--sc-accent);background:transparent}
.sc-btn--ghost{background:transparent;color:var(--sc-muted)}
.sc-btn--ghost:hover{color:var(--sc-text);background:color-mix(in srgb,var(--sc-text) 5%,transparent)}
.sc-btn[disabled],.sc-btn.is-busy{opacity:.65;cursor:progress;transform:none}
.sc-results{display:grid;gap:.75rem;grid-template-columns:repeat(2,minmax(0,1fr))}
.sc-card{display:flex;flex-direction:column;gap:.35rem;padding:1rem;border-radius:var(--sc-r-sm);background:color-mix(in srgb,var(--sc-text) 3%,var(--sc-surface));border:1px solid var(--sc-border);min-width:0}
.sc-card-icon{display:grid;place-items:center;width:2.25rem;height:2.25rem;border-radius:.65rem;background:var(--sc-soft);color:var(--sc-accent)}
.sc-card-label{color:var(--sc-muted);font-size:.8rem;font-weight:600}
.sc-card-value{font-size:clamp(1.2rem,1rem + .8vw,1.6rem);font-weight:900;line-height:1.2;color:var(--sc-text)}
.sc-card-unit{font-size:.78rem;font-weight:600;color:var(--sc-muted);margin-inline-start:.25rem}
.sc-price{margin-top:1rem;padding:1.15rem 1.25rem;border-radius:var(--sc-r-sm);background:linear-gradient(135deg,var(--sc-soft),var(--sc-surface));border:1.5px solid color-mix(in srgb,var(--sc-accent) 35%,var(--sc-border));display:flex;flex-direction:column;gap:.35rem}
.sc-price-label{font-weight:700;color:var(--sc-muted);font-size:.88rem}
.sc-price-value{font-size:clamp(1.35rem,1.1rem + 1vw,1.9rem);font-weight:900;color:var(--sc-text)}
.sc-price-value.is-muted{font-size:1rem;font-weight:700;color:var(--sc-muted)}
.sc-price-extra{display:flex;flex-wrap:wrap;gap:.5rem 1.25rem;font-size:.85rem;color:var(--sc-muted)}
.sc-price-extra b{color:var(--sc-text)}
.sc-warning,.sc-note,.sc-privacy{display:flex;gap:.5rem;align-items:flex-start;font-size:.85rem;line-height:1.8;margin:1rem 0 0;color:var(--sc-muted)}
.sc-warning{color:var(--tb-warning,#b45309);background:color-mix(in srgb,var(--tb-warning,#f59e0b) 10%,transparent);padding:.7rem .9rem;border-radius:var(--sc-r-sm)}
.sc-warning[hidden]{display:none}
.sc-warning svg,.sc-note svg,.sc-privacy svg{margin-top:.3rem}
.sc-hp{position:absolute!important;inset-inline-start:-10000px;width:1px;height:1px;overflow:hidden}
.sc-success{text-align:center;padding:1rem 0;outline:none}
.sc-success[hidden]{display:none}
.sc-success-icon{display:inline-grid;place-items:center;width:4rem;height:4rem;border-radius:50%;background:color-mix(in srgb,var(--tb-success,#16a34a) 14%,transparent);color:var(--tb-success,#16a34a)}
.sc-success-icon svg{width:2rem;height:2rem}
.sc-success h3{font-size:1.25rem;font-weight:900;margin:.85rem 0 .35rem}
.sc-success p{color:var(--sc-muted);margin:0 auto;max-width:34rem;line-height:1.9}
.sc-quote-no{font-weight:800;color:var(--sc-text)!important;margin-top:.5rem!important;direction:ltr}
.sc-login{display:flex;flex-direction:column;align-items:center;gap:.65rem;text-align:center;padding:1.25rem;margin-top:1rem;border-radius:var(--sc-r-sm);background:var(--sc-soft)}
.sc-login[hidden]{display:none}
.sc-login>svg{width:2rem;height:2rem;color:var(--sc-accent)}
.sc-login p{margin:0;font-weight:700}
.sc-error{margin:1rem 0 0;padding:.75rem 1rem;border-radius:var(--sc-r-sm);background:color-mix(in srgb,var(--tb-error,#dc2626) 9%,transparent);color:var(--tb-error,#b91c1c);font-size:.9rem;font-weight:600}
.sc-error[hidden]{display:none}
.sc-side img{width:100%;border-radius:var(--sc-radius);display:block;object-fit:cover;aspect-ratio:4/5}
@media (min-width:640px){.sc-step{flex:1 1 0}.sc-step-title{display:inline}.sc-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.sc-field--wide{grid-column:1/-1}.sc-results{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media (min-width:768px){.sc-types{grid-template-columns:repeat(3,minmax(0,1fr))}.sc-type-card{grid-template-columns:1fr;grid-template-areas:"media" "label" "desc";text-align:center;justify-items:center;padding:1.35rem 1rem}.sc-type-img{width:100%;height:7.5rem}}
@media (min-width:1024px){.sc-calc--split .sc-shell{grid-template-columns:minmax(0,1fr) minmax(0,22rem)}.sc-side{position:sticky;top:6rem}}
@media (max-width:639.98px){.sc-nav .sc-btn{flex:1 1 auto}.sc-side{display:none}}
@keyframes sc-in{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
@media (prefers-reduced-motion:reduce){.sc-calc *{animation:none!important;transition:none!important}}
</style>
<script>
(function () {
    'use strict';
    var FA = '۰۱۲۳۴۵۶۷۸۹', AR = '٠١٢٣٤٥٦٧٨٩';
    function latin(s) {
        return String(s == null ? '' : s).replace(/[۰-۹٠-٩]/g, function (d) {
            var i = FA.indexOf(d); return String(i >= 0 ? i : AR.indexOf(d));
        }).replace(/[٬,\s]/g, '').replace('٫', '.');
    }
    function fa(s) { return String(s).replace(/\d/g, function (d) { return FA[d]; }); }
    function groupMoney(input) {
        var raw = latin(input.value).replace(/\D/g, '');
        input.value = raw ? fa(raw.replace(/\B(?=(\d{3})+(?!\d))/g, '٬')) : '';
    }

    function Calc(root) {
        this.root = root;
        this.cfg = JSON.parse(root.getAttribute('data-config') || '{}');
        this.form = root.querySelector('[data-sc-form]');
        this.step = 1;
        this.result = null;
        this.bind();
        this.syncConditional();
    }
    Calc.prototype.$ = function (sel) { return this.root.querySelector(sel); };
    Calc.prototype.$$ = function (sel) { return Array.prototype.slice.call(this.root.querySelectorAll(sel)); };
    Calc.prototype.value = function (name) {
        var el = this.form.elements[name];
        if (!el) return '';
        if (el instanceof RadioNodeList || (el.length && el[0] && el[0].type === 'radio')) {
            var c = this.form.querySelector('input[name="' + name + '"]:checked');
            return c ? c.value : '';
        }
        return el.value;
    };
    Calc.prototype.csrf = function () {
        var m = document.querySelector('meta[name="csrf-token"]');
        return (m && m.getAttribute('content')) || this.cfg.csrf;
    };
    Calc.prototype.bind = function () {
        var self = this;
        this.$$('[data-sc-next]').forEach(function (b) { b.addEventListener('click', function () { self.next(); }); });
        this.$$('[data-sc-back]').forEach(function (b) { b.addEventListener('click', function () { self.go(self.step - 1); }); });
        this.$('[data-sc-calculate]').addEventListener('click', function () { self.calculate(); });
        this.$('[data-sc-submit]').addEventListener('click', function () { self.submit(); });
        this.$('[data-sc-restart]').addEventListener('click', function () { self.restart(); });
        this.form.addEventListener('change', function () { self.syncConditional(); });
        this.form.addEventListener('submit', function (e) { e.preventDefault(); });
        this.form.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && e.target.tagName === 'INPUT' && e.target.type !== 'radio') {
                e.preventDefault();
                if (self.step === 2) self.calculate(); else if (self.step === 4) self.submit(); else self.next();
            }
        });
        this.$$('[data-sc-money]').forEach(function (i) { i.addEventListener('input', function () { groupMoney(i); }); });
        this.form.addEventListener('input', function (e) {
            var f = e.target.closest('.sc-field');
            if (f) { f.classList.remove('has-error'); var p = f.querySelector('.sc-field-error'); if (p) p.textContent = ''; }
        });
    };
    Calc.prototype.syncConditional = function () {
        var mode = this.value('consumption_mode'), type = this.value('system_type');
        this.$$('[data-when-mode]').forEach(function (el) { el.hidden = el.getAttribute('data-when-mode') !== mode; });
        this.$$('[data-when-type]').forEach(function (el) { el.hidden = el.getAttribute('data-when-type') !== type; });
    };
    Calc.prototype.go = function (n) {
        if (n < 1 || n > 4) return;
        this.step = n;
        this.$$('[data-step]').forEach(function (p) { p.hidden = Number(p.getAttribute('data-step')) !== n; });
        this.$$('[data-step-dot]').forEach(function (d) {
            var i = Number(d.getAttribute('data-step-dot'));
            d.classList.toggle('is-current', i === n);
            d.classList.toggle('is-done', i < n);
            if (i === n) d.setAttribute('aria-current', 'step'); else d.removeAttribute('aria-current');
        });
        this.error('');
        var t = this.$('[data-step="' + n + '"] .sc-panel-title');
        if (t) t.focus({ preventScroll: true });
        var top = this.root.getBoundingClientRect().top;
        if (top < 0) this.root.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
    };
    Calc.prototype.next = function () {
        if (this.step === 1 && !this.value('system_type')) { this.error(this.cfg.texts.required); return; }
        if (this.step === 3 && !this.result) return;
        this.go(this.step + 1);
    };
    Calc.prototype.error = function (msg) {
        var el = this.$('[data-sc-error]');
        el.textContent = msg || '';
        el.hidden = !msg;
    };
    Calc.prototype.fieldErrors = function (errors) {
        var self = this, first = null;
        Object.keys(errors || {}).forEach(function (name) {
            var p = self.$('[data-error-for="' + name + '"]');
            if (!p) return;
            p.textContent = [].concat(errors[name])[0];
            var f = p.closest('.sc-field');
            if (f) { f.classList.add('has-error'); if (!first) first = f.querySelector('input,select,textarea'); }
        });
        if (first) first.focus();
        return first !== null;
    };
    Calc.prototype.payload = function () {
        return {
            system_type: this.value('system_type'),
            consumption_mode: this.value('consumption_mode'),
            monthly_kwh: latin(this.value('monthly_kwh')),
            monthly_bill: latin(this.value('monthly_bill')),
            region: this.value('region'),
            roof_area: latin(this.value('roof_area')),
            backup_hours: latin(this.value('backup_hours'))
        };
    };
    Calc.prototype.request = function (url, body, button, busyText) {
        var self = this, label = button.innerHTML;
        button.disabled = true; button.classList.add('is-busy'); button.textContent = busyText;
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf(), 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(body)
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) { return { status: res.status, data: data }; });
        }).catch(function () {
            return { status: 0, data: { message: self.cfg.texts.networkError } };
        }).then(function (r) {
            button.disabled = false; button.classList.remove('is-busy'); button.innerHTML = label;
            return r;
        });
    };
    Calc.prototype.handleFailure = function (r) {
        if (r.status === 401 && r.data.login_required) {
            this.$('[data-sc-login]').hidden = false;
            return;
        }
        if (r.status === 422 && this.fieldErrors(r.data.errors)) return;
        this.error((r.data && r.data.message) || this.cfg.texts.genericError);
    };
    Calc.prototype.calculate = function () {
        var self = this;
        this.$('[data-sc-login]').hidden = true;
        this.request(this.cfg.calculateUrl, this.payload(), this.$('[data-sc-calculate]'), this.cfg.texts.busy).then(function (r) {
            if (r.status !== 200 || !r.data.ok) { self.handleFailure(r); return; }
            self.result = r.data.result;
            self.renderResult(r.data.result);
            self.go(3);
        });
    };
    Calc.prototype.renderResult = function (res) {
        var box = this.$('[data-sc-results]'), t = this.cfg.texts;
        box.textContent = '';
        (res.cards || []).forEach(function (c) {
            var card = document.createElement('div'); card.className = 'sc-card';
            if (c.icon_svg) { var ic = document.createElement('span'); ic.className = 'sc-card-icon'; ic.innerHTML = c.icon_svg; card.appendChild(ic); }
            var l = document.createElement('span'); l.className = 'sc-card-label'; l.textContent = c.label; card.appendChild(l);
            var v = document.createElement('span'); v.className = 'sc-card-value'; v.textContent = c.display;
            var u = document.createElement('span'); u.className = 'sc-card-unit'; u.textContent = c.unit; v.appendChild(u);
            card.appendChild(v);
            box.appendChild(card);
        });

        var roof = this.$('[data-sc-roof]');
        roof.hidden = res.roof_fits !== false;
        roof.querySelector('span').textContent = t.roofWarning;

        var price = this.$('[data-sc-price]'), pv = this.$('[data-sc-price-value]'), extra = this.$('[data-sc-price-extra]');
        var p = res.price || {};
        extra.textContent = '';
        price.hidden = false;
        pv.classList.remove('is-muted');
        if (!this.cfg.showPrice) { pv.textContent = t.priceHidden; pv.classList.add('is-muted'); }
        else if (!p.available) { pv.textContent = t.priceUnavailable; pv.classList.add('is-muted'); }
        else {
            pv.innerHTML = p.total_html; // server-formatted digits (+ the faded reform zeros span)
            pv.appendChild(document.createTextNode(' ' + p.unit));
            if (p.saving_html) { var s = document.createElement('span'); s.textContent = t.saving + ': '; var b = document.createElement('b'); b.innerHTML = p.saving_html; s.appendChild(b); s.appendChild(document.createTextNode(' ' + p.unit)); extra.appendChild(s); }
            if (p.payback_display) { var y = document.createElement('span'); y.textContent = t.payback + ': '; var b2 = document.createElement('b'); b2.textContent = p.payback_display + ' ' + t.paybackUnit; y.appendChild(b2); extra.appendChild(y); }
        }
    };
    Calc.prototype.submit = function () {
        var self = this;
        if (this.cfg.preview) { this.error(this.cfg.texts.previewBlocked); return; }
        var body = this.payload();
        ['name', 'mobile', 'email', 'note', 'website'].forEach(function (k) { body[k] = k === 'mobile' ? latin(self.value(k)) : self.value(k); });
        this.request(this.cfg.quoteUrl, body, this.$('[data-sc-submit]'), this.cfg.texts.sending).then(function (r) {
            if (r.status !== 201 || !r.data.ok) {
                if (r.status === 422 && r.data.errors && (r.data.errors.system_type || r.data.errors.region || r.data.errors.monthly_kwh || r.data.errors.monthly_bill)) { self.go(2); }
                self.handleFailure(r);
                return;
            }
            self.$('[data-sc-contact]').hidden = true;
            var ok = self.$('[data-sc-success]');
            ok.hidden = false;
            self.$('[data-sc-quote-no]').textContent = r.data.quote_number || '';
            self.$('[data-sc-view]').href = r.data.quote_url;
            self.$('[data-sc-print]').href = r.data.print_url;
            ok.focus();
        });
    };
    Calc.prototype.restart = function () {
        this.result = null;
        this.$('[data-sc-contact]').hidden = false;
        this.$('[data-sc-success]').hidden = true;
        this.go(1);
    };

    function boot() {
        document.querySelectorAll('[data-sc-calc]').forEach(function (root) {
            if (root.__scCalc) return;
            root.__scCalc = new Calc(root);
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
    document.addEventListener('tb:rendered', boot);
})();
</script>
@endif

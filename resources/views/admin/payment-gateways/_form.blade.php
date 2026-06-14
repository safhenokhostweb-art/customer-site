{{--
 فرم مشترک ایجاد/ویرایش درگاه پرداخت
 متغیرها: $gateway (اختیاری)، $drivers، $driverFields
--}}
@push('head')
<style>
.form-card{background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:1.5rem;box-shadow:0 1px 4px rgba(15,23,42,.06);margin-bottom:1.25rem}
.form-card__title{font-size:.9rem;font-weight:700;color:#1e293b;margin:0 0 1.125rem;padding-bottom:.75rem;border-bottom:1px solid #f1f5f9}
.form-row{display:grid;gap:1rem;margin-bottom:1rem}
.form-row--2{grid-template-columns:1fr 1fr}
.field{display:flex;flex-direction:column;gap:.3rem}
.field label{font-size:.8rem;font-weight:600;color:#374151}
.field label .req{color:#dc2626;margin-right:.15rem}
.field input,.field select,.field textarea{padding:.55rem .875rem;border:1px solid #d1d5db;border-radius:.5rem;font-size:.875rem;color:#1e293b;font-family:inherit;background:#fff;transition:border-color .15s}
.field input:focus,.field select:focus,.field textarea:focus{outline:none;border-color:#6366f1;box-shadow:0 0 0 3px #e0e7ff}
.field textarea{min-height:120px;resize:vertical}
.field--error input,.field--error select,.field--error textarea{border-color:#dc2626}
.field-error{font-size:.74rem;color:#dc2626;margin-top:.15rem}
.field-hint{font-size:.74rem;color:#94a3b8;margin-top:.15rem}
.toggle-wrap{display:flex;align-items:center;gap:.625rem;cursor:pointer;user-select:none}
.toggle-wrap input[type=checkbox]{width:1.125rem;height:1.125rem;accent-color:#6366f1;cursor:pointer}
.toggle-label{font-size:.85rem;font-weight:500;color:#374151}

/* فیلدهای پویا درگاه */
.driver-fields{display:none}
.driver-fields.active{display:block}

.gateway-preview{background:linear-gradient(135deg,#6366f1,#818cf8);border-radius:1rem;padding:1.25rem;color:#fff;margin-bottom:1.25rem;display:flex;align-items:center;gap:.875rem}
.gateway-preview__icon{font-size:2rem;background:rgba(255,255,255,.15);border-radius:.625rem;width:52px;height:52px;display:flex;align-items:center;justify-content:center}
.gateway-preview__info h4{margin:0 0 .2rem;font-size:1rem;font-weight:700}
.gateway-preview__info p{margin:0;font-size:.8rem;opacity:.85}
</style>
@endpush

{{-- انتخاب درگاه --}}
<div class="form-card">
    <div class="form-card__title">اطلاعات اصلی</div>

    <div class="form-row form-row--2">
        <div class="field {{ $errors->has('name') ? 'field--error' : '' }}">
            <label>نام نمایشی <span class="req">*</span></label>
            <input type="text" name="name" value="{{ old('name', $gateway->name ?? '') }}"
                   placeholder="مثال: درگاه اصلی بانک ملت" required>
            @error('name') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="field {{ $errors->has('driver') ? 'field--error' : '' }}">
            <label>نوع درگاه <span class="req">*</span></label>
            <select name="driver" id="driverSelect" required onchange="switchDriver(this.value)">
                <option value="">انتخاب کنید...</option>
                @foreach($drivers as $key => $label)
                    <option value="{{ $key }}" {{ old('driver', $gateway->driver ?? '') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('driver') <span class="field-error">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="form-row form-row--2">
        <div class="field">
            <label>ترتیب نمایش</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $gateway->sort_order ?? 0) }}"
                   min="0" max="255" style="max-width:120px">
            <span class="field-hint">عدد کوچک‌تر = اولویت بالاتر</span>
        </div>
        <div class="field" style="justify-content:flex-end;padding-bottom:.25rem">
            <div style="display:flex;flex-direction:column;gap:.5rem">
                <label class="toggle-wrap">
                    <input type="checkbox" name="is_active" value="1"
                           {{ old('is_active', isset($gateway) ? $gateway->is_active : true) ? 'checked' : '' }}>
                    <span class="toggle-label">درگاه فعال باشد</span>
                </label>
                <label class="toggle-wrap">
                    <input type="checkbox" name="is_default" value="1"
                           {{ old('is_default', isset($gateway) ? $gateway->is_default : false) ? 'checked' : '' }}>
                    <span class="toggle-label">به‌عنوان درگاه پیش‌فرض تنظیم شود</span>
                </label>
            </div>
        </div>
    </div>
</div>

{{-- فیلدهای اختصاصی هر درگاه --}}
@foreach($driverFields as $driver => $fields)
<div class="form-card driver-fields {{ old('driver', $gateway->driver ?? '') === $driver ? 'active' : '' }}" id="fields-{{ $driver }}">
    <div class="form-card__title">
        اعتبارنامه درگاه — {{ $drivers[$driver] ?? $driver }}
    </div>

    @foreach($fields as $field)
        @php
            $existingValue = old("config.{$field['key']}", isset($gateway) ? ($gateway->config[$field['key']] ?? '') : '');
        @endphp

        @if($field['type'] === 'checkbox')
            <div style="margin-bottom:.875rem">
                <label class="toggle-wrap">
                    <input type="checkbox" name="config[{{ $field['key'] }}]" value="1"
                           {{ $existingValue ? 'checked' : '' }}>
                    <span class="toggle-label">{{ $field['label'] }}</span>
                </label>
            </div>
        @elseif($field['type'] === 'textarea')
            <div class="field {{ $errors->has("config.{$field['key']}") ? 'field--error' : '' }}" style="margin-bottom:.875rem">
                <label>
                    {{ $field['label'] }}
                    @if($field['required']) <span class="req">*</span> @endif
                </label>
                <textarea name="config[{{ $field['key'] }}]"
                          placeholder="{{ $field['label'] }}"
                          style="font-family:monospace;font-size:.8rem;direction:ltr">{{ $existingValue }}</textarea>
                @if($field['key'] === 'private_key')
                    <span class="field-hint">کلید خصوصی PEM را وارد کنید (شامل -----BEGIN PRIVATE KEY-----)</span>
                @endif
            </div>
        @else
            <div class="field {{ $errors->has("config.{$field['key']}") ? 'field--error' : '' }}" style="margin-bottom:.875rem">
                <label>
                    {{ $field['label'] }}
                    @if($field['required']) <span class="req">*</span> @endif
                </label>
                <input type="{{ $field['type'] === 'password' ? 'password' : 'text' }}"
                       name="config[{{ $field['key'] }}]"
                       value="{{ $field['type'] !== 'password' ? $existingValue : '' }}"
                       placeholder="{{ $field['type'] === 'password' ? '••••••••' : $field['label'] }}"
                       style="{{ $field['type'] === 'password' || in_array($field['key'], ['merchant_id','api_key','terminal_id','pin']) ? 'direction:ltr;letter-spacing:.04em' : '' }}"
                       autocomplete="off">
                @if($field['type'] === 'password')
                    <span class="field-hint">در صورت عدم تغییر، فیلد را خالی بگذارید تا مقدار قبلی حفظ شود</span>
                @endif
                @error("config.{$field['key']}") <span class="field-error">{{ $message }}</span> @enderror
            </div>
        @endif
    @endforeach
</div>
@endforeach

@push('scripts')
<script>
function switchDriver(driver) {
    document.querySelectorAll('.driver-fields').forEach(el => el.classList.remove('active'));
    if (driver) {
        const target = document.getElementById('fields-' + driver);
        if (target) target.classList.add('active');
    }
}
// اجرا در بارگذاری صفحه
const sel = document.getElementById('driverSelect');
if (sel && sel.value) switchDriver(sel.value);
</script>
@endpush

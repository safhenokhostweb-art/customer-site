@extends('admin.layout')
@section('title', 'ثبت دستی اشتراک برای کاربر')
@section('content')

<div class="assign-pg" style="font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif;max-width:780px">

<div class="assign-pg__head">
    <h1 class="assign-pg__title">ثبت دستی اشتراک</h1>
    <p class="assign-pg__sub">اشتراک مستقیم برای کاربر ایجاد کنید یا فاکتور برای پرداخت بسازید.</p>
</div>

@if($errors->any())
<div class="alert alert-danger mb-4">
    <ul class="list-disc list-inside text-sm space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form action="{{ route('admin.subscribers.assign') }}" method="POST" id="assign-form">
@csrf

{{-- ── انتخاب کاربر ─────────────────────────────────── --}}
<div class="assign-card">
    <div class="assign-card__head">
        <span class="assign-card__step">۱</span>
        انتخاب کاربر
    </div>
    <div class="assign-card__body">
        <div class="form-group">
            <label class="form-label">جستجو و انتخاب کاربر *</label>
            <div class="assign-search-wrap">
                <x-core-ui::icon name="search" class="assign-search-icon w-4 h-4" />
                <input type="text" id="user-search"
                       placeholder="نام، نام خانوادگی یا شماره موبایل..."
                       class="assign-search-input" autocomplete="off" dir="rtl">
                <input type="hidden" name="user_id" id="user_id_hidden" required>
                <div id="user-results" class="assign-results" style="display:none"></div>
            </div>
            <div id="selected-user" class="assign-selected" style="display:none">
                <div class="assign-selected__info">
                    <div class="assign-selected__avatar" id="sel-avatar"></div>
                    <div>
                        <p class="assign-selected__name" id="sel-name"></p>
                        <p class="assign-selected__mobile" id="sel-mobile"></p>
                    </div>
                </div>
                <button type="button" id="clear-user" class="assign-selected__clear">×</button>
            </div>
        </div>
    </div>
</div>

{{-- ── انتخاب پلن ─────────────────────────────────────── --}}
<div class="assign-card">
    <div class="assign-card__head">
        <span class="assign-card__step">۲</span>
        انتخاب پلن اشتراک
    </div>
    <div class="assign-card__body">
        <div class="assign-plans-grid" id="plan-grid">
            @foreach($plans as $plan)
            <label class="assign-plan-card" for="plan-{{ $plan->id }}">
                <input type="radio" name="plan_id" id="plan-{{ $plan->id }}" value="{{ $plan->id }}"
                       class="assign-plan-radio" required>
                <div class="assign-plan-card__inner" style="border-top:3px solid {{ $plan->color ?? '#4f46e5' }}">
                    @if($plan->image)
                    <img src="{{ $plan->image_url }}" alt="{{ $plan->name }}"
                         class="assign-plan-card__img">
                    @endif
                    <p class="assign-plan-card__name">{{ $plan->name }}</p>
                    <p class="assign-plan-card__price" style="color:{{ $plan->color ?? '#4f46e5' }}">
                        @if($plan->isFree())رایگان
                        @else {{ \App\Helpers\NumberHelper::formatFa($plan->getEffectivePrice()) }} <small>تومان</small>
                        @endif
                    </p>
                    <p class="assign-plan-card__dur">{{ $plan->getDurationLabel() }}</p>
                </div>
            </label>
            @endforeach
        </div>
    </div>
</div>

{{-- ── تنظیمات ──────────────────────────────────────────── --}}
<div class="assign-card">
    <div class="assign-card__head">
        <span class="assign-card__step">۳</span>
        تنظیمات فعال‌سازی
    </div>
    <div class="assign-card__body">
        <div class="assign-toggle-wrap">
            <label class="assign-toggle" for="activate_now">
                <input type="checkbox" id="activate_now" name="activate_now" value="1" class="assign-toggle__input">
                <span class="assign-toggle__track"></span>
                <span class="assign-toggle__label">
                    <strong>فعال‌سازی فوری</strong>
                    <small>بدون نیاز به پرداخت، اشتراک همین لحظه فعال می‌شود</small>
                </span>
            </label>
        </div>
        <div id="payment-notice" class="assign-notice assign-notice--info" style="margin-top:.875rem">
            <x-core-ui::icon name="info-circle" class="w-4 h-4" style="-ms-flex-negative:0;flex-shrink:0" />
            <span>یک فاکتور پرداخت‌نشده برای کاربر ایجاد می‌شود و در هدر پنل کاربری نمایش داده می‌شود.</span>
        </div>
        <div id="activate-notice" class="assign-notice assign-notice--success" style="margin-top:.875rem;display:none">
            <x-core-ui::icon name="check-circle" class="w-4 h-4" style="-ms-flex-negative:0;flex-shrink:0" />
            <span>اشتراک بلافاصله فعال می‌شود، تیکت اطلاع‌رسانی ارسال خواهد شد و پیامک به موبایل کاربر ارسال می‌شود.</span>
        </div>
        <div class="form-group" style="margin-top:1rem">
            <label class="form-label">یادداشت داخلی (اختیاری)</label>
            <textarea name="notes" rows="2" placeholder="توضیح یا دلیل ایجاد این اشتراک..." class="w-full text-sm"></textarea>
        </div>
    </div>
</div>

{{-- ── دکمه‌ها ──────────────────────────────────────────── --}}
<div class="assign-footer">
    <button type="submit" class="btn btn-primary" id="submit-btn">ثبت اشتراک</button>
    <a href="{{ route('admin.subscribers.index') }}" class="btn btn-secondary">انصراف</a>
</div>

</form>
</div>

@push('head')
<style>
.assign-pg, .assign-pg * { font-family: Vazirmatn, Tahoma, 'Segoe UI', Arial, sans-serif; }
.assign-pg { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: 1.25rem; }
.assign-pg__head { margin-bottom: .25rem; }
.assign-pg__title { font-size: 1.25rem; font-weight: 800; color: #1e293b; margin: 0 0 .25rem; }
.assign-pg__sub { font-size: .85rem; color: #64748b; margin: 0; }

.assign-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; -webkit-box-shadow: 0 1px 4px rgba(0,0,0,.05); box-shadow: 0 1px 4px rgba(0,0,0,.05); }
.assign-card__head { padding: .875rem 1.25rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; border-radius: 1rem 1rem 0 0; font-size: .9rem; font-weight: 700; color: #374151; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .625rem; }
.assign-card__step { width: 24px; height: 24px; border-radius: 50%; background: -webkit-linear-gradient(135deg,#4f46e5,#7c3aed); background: linear-gradient(135deg,#4f46e5,#7c3aed); color: #fff; font-size: .72rem; font-weight: 800; display: -webkit-inline-box; display: -ms-inline-flexbox; display: inline-flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; -ms-flex-negative: 0; flex-shrink: 0; }
.assign-card__body { padding: 1.25rem; }

/* user search */
.assign-search-wrap { position: relative; }
.assign-search-icon { position: absolute; right: .75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; flex-shrink: 0; }
.assign-search-input { width: 100%; -webkit-box-sizing: border-box; box-sizing: border-box; padding: .7rem .875rem .7rem 2.5rem; border: 1.5px solid #e2e8f0; border-radius: .75rem; font-size: .9rem; font-family: Vazirmatn, Tahoma, sans-serif; outline: none; padding-right: 2.5rem; }
.assign-search-input:focus { border-color: #4f46e5; -webkit-box-shadow: 0 0 0 3px rgba(79,70,229,.1); box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
.assign-results { position: absolute; top: calc(100% + 4px); right: 0; left: 0; background: #fff; border: 1.5px solid #c7d2fe; border-radius: .75rem; -webkit-box-shadow: 0 12px 32px rgba(0,0,0,.15); box-shadow: 0 12px 32px rgba(0,0,0,.15); z-index: 9999; max-height: 260px; overflow-y: auto; }
.assign-result-item { padding: .625rem .875rem; cursor: pointer; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .5rem; border-bottom: 1px solid #f8fafc; }
.assign-result-item:hover { background: #f0f4ff; }
.assign-result-item:last-child { border-bottom: none; }
.assign-result-avatar { width: 28px; height: 28px; border-radius: 50%; background: -webkit-linear-gradient(135deg,#4f46e5,#7c3aed); background: linear-gradient(135deg,#4f46e5,#7c3aed); color: #fff; font-size: .72rem; font-weight: 700; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; -ms-flex-negative: 0; flex-shrink: 0; }
.assign-result-name { font-weight: 600; font-size: .85rem; color: #1e293b; }
.assign-result-mobile { font-size: .75rem; color: #94a3b8; direction: ltr; text-align: right; margin-top: 1px; }

.assign-selected { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: justify; -ms-flex-pack: justify; justify-content: space-between; gap: .75rem; background: #f0fdf4; border: 1.5px solid #86efac; border-radius: .625rem; padding: .625rem .875rem; margin-top: .375rem; }
.assign-selected__info { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .625rem; }
.assign-selected__avatar { width: 32px; height: 32px; border-radius: 50%; background: -webkit-linear-gradient(135deg,#059669,#10b981); background: linear-gradient(135deg,#059669,#10b981); color: #fff; font-size: .78rem; font-weight: 800; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; -ms-flex-negative: 0; flex-shrink: 0; }
.assign-selected__name { font-weight: 700; color: #166534; font-size: .875rem; margin: 0; }
.assign-selected__mobile { font-size: .75rem; color: #16a34a; margin: 0; direction: ltr; text-align: right; }
.assign-selected__clear { background: none; border: none; color: #94a3b8; font-size: 1.125rem; cursor: pointer; padding: 0 .25rem; }

/* plan grid */
.assign-plans-grid { display: -ms-grid; display: grid; -ms-grid-columns: 1fr 1fr; grid-template-columns: 1fr 1fr; gap: .875rem; }
@media (min-width: 640px) { .assign-plans-grid { -ms-grid-columns: 1fr 1fr 1fr; grid-template-columns: repeat(3, 1fr); } }
.assign-plan-card { cursor: pointer; }
.assign-plan-radio { position: absolute; opacity: 0; width: 0; height: 0; }
.assign-plan-card__inner { border: 1.5px solid #e2e8f0; border-radius: .875rem; padding: 1rem; text-align: center; -webkit-transition: all .15s; transition: all .15s; }
.assign-plan-card input:checked + .assign-plan-card__inner { border-color: #4f46e5; background: #eef2ff; -webkit-box-shadow: 0 0 0 3px rgba(79,70,229,.15); box-shadow: 0 0 0 3px rgba(79,70,229,.15); }
.assign-plan-card__img { width: 48px; height: 48px; border-radius: .5rem; -o-object-fit: cover; object-fit: cover; margin: 0 auto .5rem; display: block; }
.assign-plan-card__name { font-size: .875rem; font-weight: 700; color: #1e293b; margin: 0 0 .3rem; }
.assign-plan-card__price { font-size: 1rem; font-weight: 800; margin: 0 0 .2rem; }
.assign-plan-card__price small { font-size: .7rem; font-weight: 400; color: #94a3b8; }
.assign-plan-card__dur { font-size: .75rem; color: #94a3b8; margin: 0; }

/* toggle */
.assign-toggle { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .875rem; cursor: pointer; }
.assign-toggle__input { position: absolute; opacity: 0; width: 0; height: 0; }
.assign-toggle__track { width: 44px; height: 24px; background: #e2e8f0; border-radius: 9999px; position: relative; -ms-flex-negative: 0; flex-shrink: 0; -webkit-transition: background .2s; transition: background .2s; }
.assign-toggle__track::after { content: ''; position: absolute; top: 3px; right: 3px; width: 18px; height: 18px; background: #fff; border-radius: 50%; -webkit-transition: right .2s; transition: right .2s; -webkit-box-shadow: 0 1px 3px rgba(0,0,0,.2); box-shadow: 0 1px 3px rgba(0,0,0,.2); }
.assign-toggle__input:checked ~ .assign-toggle__track { background: #22c55e; }
.assign-toggle__input:checked ~ .assign-toggle__track::after { right: calc(100% - 21px); }
.assign-toggle__label { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: .15rem; }
.assign-toggle__label strong { font-size: .875rem; color: #1e293b; font-weight: 700; }
.assign-toggle__label small { font-size: .75rem; color: #64748b; }

/* notice */
.assign-notice { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: start; -ms-flex-align: start; align-items: flex-start; gap: .5rem; padding: .75rem .875rem; border-radius: .625rem; font-size: .8rem; }
.assign-notice--info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.assign-notice--success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

.assign-footer { display: -webkit-box; display: -ms-flexbox; display: flex; gap: .75rem; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var users = @json($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name ?? '', 'mobile' => $u->mobile ?? '']));

    var searchInput   = document.getElementById('user-search');
    var hiddenInput   = document.getElementById('user_id_hidden');
    var resultsBox    = document.getElementById('user-results');
    var selectedBox   = document.getElementById('selected-user');
    var selAvatar     = document.getElementById('sel-avatar');
    var selName       = document.getElementById('sel-name');
    var selMobile     = document.getElementById('sel-mobile');
    var clearBtn      = document.getElementById('clear-user');
    var toggleInput   = document.getElementById('activate_now');
    var payNotice     = document.getElementById('payment-notice');
    var actNotice     = document.getElementById('activate-notice');

    function filterUsers(q) {
        q = q.toLowerCase();
        return users.filter(function (u) {
            return (u.name && u.name.toLowerCase().indexOf(q) !== -1)
                || (u.mobile && u.mobile.indexOf(q) !== -1);
        }).slice(0, 10);
    }

    function renderResults(filtered) {
        if (!filtered.length) {
            resultsBox.innerHTML = '<div style="padding:.75rem 1rem;color:#94a3b8;font-size:.82rem;">نتیجه‌ای یافت نشد.</div>';
        } else {
            resultsBox.innerHTML = filtered.map(function (u) {
                return '<div class="assign-result-item" data-id="' + u.id + '" data-name="' + u.name + '" data-mobile="' + u.mobile + '">'
                    + '<div class="assign-result-avatar">' + (u.name ? u.name.substring(0, 1) : '؟') + '</div>'
                    + '<div><div class="assign-result-name">' + (u.name || '—') + '</div>'
                    + '<div class="assign-result-mobile">' + u.mobile + '</div></div>'
                    + '</div>';
            }).join('');
        }
        resultsBox.style.display = 'block';
    }

    searchInput.addEventListener('input', function () {
        var q = this.value.trim();
        if (q.length < 1) { resultsBox.style.display = 'none'; return; }
        renderResults(filterUsers(q));
    });

    searchInput.addEventListener('focus', function () {
        var q = this.value.trim();
        if (q.length >= 1) renderResults(filterUsers(q));
    });

    resultsBox.addEventListener('click', function (e) {
        var item = e.target.closest('.assign-result-item');
        if (!item) return;
        var id     = item.dataset.id;
        var name   = item.dataset.name;
        var mobile = item.dataset.mobile;

        hiddenInput.value = id;
        selAvatar.textContent = name ? name.substring(0, 1) : '؟';
        selName.textContent   = name || '—';
        selMobile.textContent = mobile;
        searchInput.value     = '';
        resultsBox.style.display = 'none';
        selectedBox.style.display = '';
        searchInput.style.display = 'none';
    });

    clearBtn.addEventListener('click', function () {
        hiddenInput.value = '';
        selectedBox.style.display = 'none';
        searchInput.style.display = '';
        searchInput.focus();
    });

    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.style.display = 'none';
        }
    });

    toggleInput.addEventListener('change', function () {
        if (this.checked) {
            payNotice.style.display = 'none';
            actNotice.style.display = '';
        } else {
            payNotice.style.display = '';
            actNotice.style.display = 'none';
        }
    });
})();
</script>
@endpush

@endsection

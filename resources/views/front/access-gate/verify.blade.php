<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $gate->name }} — {{ \App\Helpers\SiteHelper::siteName() }}</title>
    <link rel="preload" as="font" href="{{ asset('assets/fonts/vazirmatn/Vazirmatn-Regular.woff2') }}" type="font/woff2" crossorigin>
    <style>
        @include('partials.vazirmatn-font-faces-inline')
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Vazirmatn, Tahoma, 'Segoe UI', Arial, sans-serif;
            direction: rtl; min-height: 100vh;
            background: linear-gradient(135deg, #f0f4ff 0%, #faf5ff 100%);
            display: flex; align-items: center; justify-content: center;
            padding: 1.5rem;
        }
        .card {
            background: #fff; border-radius: 1.25rem; padding: 2.5rem 2rem;
            box-shadow: 0 8px 40px rgba(99,102,241,.13), 0 2px 8px rgba(0,0,0,.06);
            width: 100%; max-width: 420px;
        }
        .icon-wrap {
            width: 64px; height: 64px; border-radius: 1rem; margin: 0 auto 1.25rem;
            background: linear-gradient(135deg, #6366f1, #7c3aed);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 16px rgba(99,102,241,.35);
        }
        .icon-wrap svg { width: 32px; height: 32px; color: #fff; }
        h1 { font-size: 1.25rem; font-weight: 800; color: #1e293b; text-align: center; }
        .desc { font-size: .875rem; color: #64748b; text-align: center; margin-top: .5rem; margin-bottom: 1.75rem; line-height: 1.6; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; font-size: .8125rem; font-weight: 600; color: #374151; margin-bottom: .375rem; }
        input[type=text] {
            width: 100%; padding: .625rem .875rem; border: 1.5px solid #e2e8f0;
            border-radius: .625rem; font-family: inherit; font-size: .9rem; color: #1e293b;
            transition: border-color .15s, box-shadow .15s;
        }
        input[type=text]:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
        .btn-submit {
            width: 100%; padding: .75rem 1rem; background: linear-gradient(135deg, #6366f1, #7c3aed);
            border: none; border-radius: .75rem; color: #fff; font-size: .9375rem; font-weight: 700;
            cursor: pointer; font-family: inherit; transition: opacity .15s;
            box-shadow: 0 3px 12px rgba(99,102,241,.3);
        }
        .btn-submit:hover { opacity: .9; }
        .error-box {
            background: #fef2f2; border: 1px solid #fecaca; border-radius: .625rem;
            padding: .75rem 1rem; font-size: .8125rem; color: #be123c; margin-bottom: 1rem;
        }
        .brand-link {
            display: block; text-align: center; font-size: .72rem; color: #94a3b8;
            margin-top: 1.5rem; text-decoration: none;
        }
        .brand-link:hover { color: #6366f1; }
    </style>
</head>
<body>
<div class="card">

    <div class="icon-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
    </div>

    <h1>{{ $gate->name }}</h1>
    @if($gate->description)
    <p class="desc">{{ $gate->description }}</p>
    @else
    <p class="desc">برای دسترسی به این بخش، اطلاعات خود را وارد کنید.</p>
    @endif

    @if($errors->any())
    <div class="error-box">{{ $errors->first('_') ?? $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('access-gate.verify', $gate->slug) }}">
        @csrf

        @php
            $matchType = $gate->accessList?->match_type ?? 'national_id';
        @endphp

        @if(in_array($matchType, ['national_id', 'both']))
        <div class="form-group">
            <label for="national_id">کد ملی</label>
            <input type="text" id="national_id" name="national_id" inputmode="numeric" maxlength="12"
                   value="{{ old('national_id') }}" placeholder="۱۲۳۴۵۶۷۸۹۰" autocomplete="off">
        </div>
        @endif

        @if(in_array($matchType, ['last_name', 'both']))
        <div class="form-group">
            <label for="last_name">نام خانوادگی</label>
            <input type="text" id="last_name" name="last_name"
                   value="{{ old('last_name') }}" placeholder="مثال: محمدی" autocomplete="off">
        </div>
        @endif

        <button type="submit" class="btn-submit">تأیید و ورود</button>
    </form>

    <a href="{{ url('/') }}" class="brand-link">بازگشت به {{ \App\Helpers\SiteHelper::siteName() }}</a>
</div>

<script>
(function(){
    var pd=['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    var inp = document.getElementById('national_id');
    if (!inp) return;
    inp.addEventListener('input', function(){
        // Convert persian digits typed on mobile
        this.value = this.value.replace(/[۰-۹]/g, function(d){ return String(pd.indexOf(d)); });
        this.value = this.value.replace(/\D/g, '');
    });
})();
</script>
</body>
</html>

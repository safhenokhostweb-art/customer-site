{{-- Names and functions a formula may use. --}}
<div class="card">
    <div class="card-header">متغیرها و توابع قابل‌استفاده</div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:1rem">
        <div>
            <p class="form-label" style="margin-bottom:.4rem">ورودی‌های کاربر</p>
            <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.35rem">
                @foreach($inputs as $name => $label)
                    <li class="text-sm"><span class="sc-code">{{ $name }}</span> <span class="text-slate-500">{{ $label }}</span></li>
                @endforeach
            </ul>
        </div>
        <div>
            <p class="form-label" style="margin-bottom:.4rem">نرخ ارز (تومان به ازای یک واحد)</p>
            <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.35rem">
                @forelse($rateVars as $name => $value)
                    <li class="text-sm"><span class="sc-code">{{ $name }}</span>
                        <span class="text-slate-500">{{ $value > 0 ? \SaaSPlatform\ThemeRuntime\Support\PersianNumber::format($value) . ' تومان' : 'ثبت نشده' }}</span></li>
                @empty
                    <li class="text-sm text-slate-500">هیچ ارز فعالی وجود ندارد.</li>
                @endforelse
            </ul>
            <a href="{{ route('admin.exchange-rates.index') }}" class="text-xs text-indigo-700">مدیریت نرخ ارز ←</a>
        </div>
        <div>
            <p class="form-label" style="margin-bottom:.4rem">توابع و عملگرها</p>
            <p class="text-sm" style="display:flex;flex-wrap:wrap;gap:.3rem">
                @foreach($functions as $fn)<span class="sc-code">{{ $fn }}()</span>@endforeach
            </p>
            <p class="sc-help" style="margin-top:.5rem">
                عملگرها: <span class="sc-code">+ - * / % ^</span> مقایسه: <span class="sc-code">&lt; &lt;= &gt; &gt;= == !=</span>
                منطقی: <span class="sc-code">and or not</span>.
                <span class="sc-code">if(شرط، مقدار۱، مقدار۲)</span>،
                <span class="sc-code">roundup(x, 0.5)</span> گرد به بالا به مضرب ۰٫۵،
                <span class="sc-code">round(x, 2)</span> گرد با دو رقم اعشار.
                نتیجهٔ مقایسه ۱ یا ۰ است.
            </p>
        </div>
    </div>
</div>

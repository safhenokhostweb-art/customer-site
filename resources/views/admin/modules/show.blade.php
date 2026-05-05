@extends('admin.layout')
@section('title', $title)
@section('header', $title)

@section('content')
<div class="space-y-6">
    <div class="card">
        <div class="card-body flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-slate-900 mb-1">{{ $title }}</h2>
                <p class="text-sm text-slate-600">
                    وضعیت این ماژول برای این سایت:
                    <span class="{{ $enabled ? 'text-emerald-600' : 'text-red-600' }} font-medium">
                        {{ $enabled ? 'فعال' : 'غیرفعال' }}
                    </span>
                </p>
            </div>
            <span class="badge {{ $enabled ? 'badge-success' : 'badge-danger' }}">
                {{ $enabled ? 'فعال' : 'غیرفعال' }}
            </span>
        </div>
    </div>

    @if($slug === 'advanced_search' && isset($advancedSearchConfig))
        <div class="space-y-3">
            @include('admin.partials.feature-guide-box', [
                'title' => 'نحوهٔ کار جستجوی پیشرفته',
                'content' => 'این قابلیت توسط ماژول فعال «جستجوی پیشرفته» فراهم شده است. جستجو روی محصولات و مطالب وبلاگ از همین‌جا تنظیم می‌شود و به‌طور مستقیم روی صفحهٔ جستجو و پیشنهادهای لحظه‌ای کاربران اثر می‌گذارد.'
            ])

            <div class="card">
                <div class="card-header">تنظیمات جستجوی پیشرفته</div>
                <div class="card-body space-y-4">
                    @unless($enabled)
                        <p class="text-sm text-amber-700">
                            این ماژول هنوز بر اساس لایسنس یا تنظیمات پلتفرم برای این سایت فعال نشده است؛
                            پس از فعال‌سازی در پلتفرم، تنظیمات زیر روی جستجو اعمال می‌شوند.
                        </p>
                    @endunless

                    <form method="post" action="{{ route('admin.modules.update', $slug) }}" class="space-y-4">
                    @csrf

                    <div class="flex flex-col gap-2">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox"
                                   name="include_products"
                                   value="1"
                                   class="form-checkbox"
                                   @checked(old('include_products', $advancedSearchConfig['include_products']))>
                            <span>جستجو در محصولات فروشگاه</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox"
                                   name="include_posts"
                                   value="1"
                                   class="form-checkbox"
                                   @checked(old('include_posts', $advancedSearchConfig['include_posts']))>
                            <span>جستجو در مطالب وبلاگ</span>
                        </label>
                    </div>

                    <div>
                        <label for="results_per_page" class="form-label">تعداد نتایج در هر صفحه</label>
                        <input type="number"
                               min="1"
                               max="60"
                               id="results_per_page"
                               name="results_per_page"
                               class="form-input w-32"
                               value="{{ old('results_per_page', $advancedSearchConfig['results_per_page']) }}">
                        <p class="text-xs text-slate-500 mt-1">
                            مقدار پیش‌فرض ۱۲ است؛ می‌توانید آن را متناسب با نیاز خود تغییر دهید.
                        </p>
                    </div>

                        <div class="flex items-center gap-3">
                            <button type="submit" class="btn btn-primary">
                                ذخیره تنظیمات جستجو
                            </button>
                            <a href="{{ route('search.index') }}"
                               class="btn btn-secondary btn-sm"
                               target="_blank"
                               rel="noopener">
                                مشاهده صفحهٔ جستجو
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @elseif($slug === 'menu' && isset($menuConfig))
        <div class="space-y-3">
            @include('admin.partials.feature-guide-box', [
                'title' => 'راهنمای منو و ناوبری',
                'content' => 'این بخش توسط ماژول فعال «منو و ناوبری» ارائه شده است. لینک‌ها و ساختار منوهای بالای سایت و فوتر را می‌توانید از همین‌جا مدیریت کنید؛ تغییرات به‌طور مستقیم روی صفحات فرانت اثر می‌گذارد.'
            ])

            <div class="card">
                <div class="card-header">تنظیمات منو و ناوبری</div>
                <div class="card-body space-y-6">
                    @unless($enabled)
                        <p class="text-sm text-amber-700">
                            این ماژول هنوز بر اساس لایسنس یا تنظیمات پلتفرم برای این سایت فعال نشده است؛
                            پس از فعال‌سازی، منوهای زیر روی سایت اعمال می‌شوند.
                        </p>
                    @endunless

                    <form method="post" action="{{ route('admin.modules.update', $slug) }}">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label">منبع منوها</label>
                        <div class="space-y-2 text-sm">
                            <label class="inline-flex items-center gap-2">
                                <input type="radio"
                                       name="menu_mode"
                                       value="platform"
                                       class="form-radio"
                                       @checked(old('menu_mode', $menuConfig['mode']) === 'platform')>
                                <span>استفاده از منوهای تعریف‌شده در پلتفرم</span>
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input type="radio"
                                       name="menu_mode"
                                       value="local"
                                       class="form-radio"
                                       @checked(old('menu_mode', $menuConfig['mode']) === 'local')>
                                <span>استفاده از منوهای سفارشی همین سایت</span>
                            </label>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            در حالت «منوهای سفارشی»، آیتم‌های زیر جایگزین منوهای ارسال‌شده از پلتفرم می‌شوند.
                        </p>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <h3 class="font-semibold text-slate-900 mb-2">منوی بالای سایت (Header)</h3>
                            <p class="text-xs text-slate-500 mb-3">
                                آیتم‌هایی که در نوار بالای سایت نمایش داده می‌شوند.
                            </p>
                            <div class="space-y-2">
                                @php
                                    $headerItems = old('header_title') !== null
                                        ? collect(old('header_title'))->map(function ($title, $i) {
                                            return [
                                                'title' => $title,
                                                'url' => old('header_url')[$i] ?? '',
                                                'target' => old('header_target')[$i] ?? '_self',
                                            ];
                                        })->all()
                                        : ($menuConfig['menus']['header']['items'] ?? []);
                                    $headerItems = array_values($headerItems);
                                    $extraHeaderRows = max(3, 5 - count($headerItems));
                                @endphp

                                @foreach($headerItems as $item)
                                    <div class="grid grid-cols-12 gap-2 items-center">
                                        <div class="col-span-4">
                                            <input type="text"
                                                   name="header_title[]"
                                                   class="form-input"
                                                   placeholder="عنوان"
                                                   value="{{ $item['title'] ?? '' }}">
                                        </div>
                                        <div class="col-span-6">
                                            <input type="text"
                                                   name="header_url[]"
                                                   class="form-input ltr:text-left"
                                                   placeholder="آدرس (URL)"
                                                   value="{{ $item['url'] ?? '' }}">
                                        </div>
                                        <div class="col-span-2">
                                            <select name="header_target[]" class="form-select">
                                                <option value="_self" @selected(($item['target'] ?? '_self') === '_self')>همین صفحه</option>
                                                <option value="_blank" @selected(($item['target'] ?? '_self') === '_blank')>زبانه جدید</option>
                                            </select>
                                        </div>
                                    </div>
                                @endforeach

                                @for($i = 0; $i < $extraHeaderRows; $i++)
                                    <div class="grid grid-cols-12 gap-2 items-center">
                                        <div class="col-span-4">
                                            <input type="text"
                                                   name="header_title[]"
                                                   class="form-input"
                                                   placeholder="عنوان">
                                        </div>
                                        <div class="col-span-6">
                                            <input type="text"
                                                   name="header_url[]"
                                                   class="form-input ltr:text-left"
                                                   placeholder="آدرس (URL)">
                                        </div>
                                        <div class="col-span-2">
                                            <select name="header_target[]" class="form-select">
                                                <option value="_self">همین صفحه</option>
                                                <option value="_blank">زبانه جدید</option>
                                            </select>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>

                        <div>
                            <h3 class="font-semibold text-slate-900 mb-2">منوی فوتر (Footer)</h3>
                            <p class="text-xs text-slate-500 mb-3">
                                لینک‌هایی که در پایین سایت نمایش داده می‌شوند.
                            </p>
                            <div class="space-y-2">
                                @php
                                    $footerItems = old('footer_title') !== null
                                        ? collect(old('footer_title'))->map(function ($title, $i) {
                                            return [
                                                'title' => $title,
                                                'url' => old('footer_url')[$i] ?? '',
                                                'target' => old('footer_target')[$i] ?? '_self',
                                            ];
                                        })->all()
                                        : ($menuConfig['menus']['footer']['items'] ?? []);
                                    $footerItems = array_values($footerItems);
                                    $extraFooterRows = max(3, 5 - count($footerItems));
                                @endphp

                                @foreach($footerItems as $item)
                                    <div class="grid grid-cols-12 gap-2 items-center">
                                        <div class="col-span-4">
                                            <input type="text"
                                                   name="footer_title[]"
                                                   class="form-input"
                                                   placeholder="عنوان"
                                                   value="{{ $item['title'] ?? '' }}">
                                        </div>
                                        <div class="col-span-6">
                                            <input type="text"
                                                   name="footer_url[]"
                                                   class="form-input ltr:text-left"
                                                   placeholder="آدرس (URL)"
                                                   value="{{ $item['url'] ?? '' }}">
                                        </div>
                                        <div class="col-span-2">
                                            <select name="footer_target[]" class="form-select">
                                                <option value="_self" @selected(($item['target'] ?? '_self') === '_self')>همین صفحه</option>
                                                <option value="_blank" @selected(($item['target'] ?? '_self') === '_blank')>زبانه جدید</option>
                                            </select>
                                        </div>
                                    </div>
                                @endforeach

                                @for($i = 0; $i < $extraFooterRows; $i++)
                                    <div class="grid grid-cols-12 gap-2 items-center">
                                        <div class="col-span-4">
                                            <input type="text"
                                                   name="footer_title[]"
                                                   class="form-input"
                                                   placeholder="عنوان">
                                        </div>
                                        <div class="col-span-6">
                                            <input type="text"
                                                   name="footer_url[]"
                                                   class="form-input ltr:text-left"
                                                   placeholder="آدرس (URL)">
                                        </div>
                                        <div class="col-span-2">
                                            <select name="footer_target[]" class="form-select">
                                                <option value="_self">همین صفحه</option>
                                                <option value="_blank">زبانه جدید</option>
                                            </select>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>

                        <div class="mt-6">
                            <button type="submit" class="btn btn-primary">
                                ذخیره تنظیمات منو
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @elseif($slug === 'bank_gateway' && isset($bankGatewayConfig))
        <div class="card">
            <div class="card-header">درگاه بانکی</div>
            <div class="card-body space-y-4">
                <p class="text-sm text-slate-700">
                    تنظیمات اصلی درگاه بانکی (اتصال به بانک، کلیدها و حالت تست/عملیاتی) همچنان از طریق پلتفرم مدیریت می‌شود.
                    در این صفحه خلاصه‌ای از وضعیت اتصال این سایت نمایش داده می‌شود.
                </p>
                <div class="bg-slate-50 border border-slate-200 rounded-md p-4 text-sm text-slate-700">
                    <div class="flex items-center justify-between gap-4 mb-2">
                        <span class="font-medium">آدرس بازگشت از بانک (Callback URL)</span>
                        <code class="text-xs ltr:font-mono rtl:font-mono break-all">
                            {{ $bankGatewayConfig['callback_url'] }}
                        </code>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">
                        این آدرس باید در تنظیمات درگاه بانکی داخل پنل پلتفرم برای این سایت تنظیم شود تا پرداخت‌ها به‌درستی به این سایت بازگردانده شوند.
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body">
                <p class="text-slate-700 mb-4">
                    برای این ماژول در حال حاضر در این نسخه صفحهٔ تنظیمات اختصاصی در پنل سایت وجود ندارد.
                    مدیریت آن (در صورت نیاز) از طریق سایر بخش‌های منو یا از طریق پلتفرم و پشتیبانی انجام می‌شود.
                </p>
            </div>
        </div>
    @endif

    <div>
        <a href="{{ route('admin.modules.index') }}" class="text-sm text-indigo-600 hover:underline">
            بازگشت به لیست ماژول‌ها
        </a>
    </div>
</div>
@endsection

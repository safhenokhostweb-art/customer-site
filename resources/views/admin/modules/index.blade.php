@extends('admin.layout')
@section('title', 'ماژول‌ها')
@section('header', 'ماژول‌ها و امکانات')
@section('content')
<div class="admin-modules-page">

@if(!$license || empty($license->license_key))
    <div class="card p-6 mb-6 bg-amber-50 border-amber-200">
        <p class="text-amber-800">
            برای مشاهده ماژول‌های خریداری‌شده، ابتدا <a href="{{ route('admin.license.index') }}" class="font-medium">لایسنس</a> را فعال کنید.
        </p>
    </div>
@endif

{{-- اعلان بروزرسانی موجود --}}
@if(!empty($moduleUpdates))
    <div class="card p-4 mb-6 bg-indigo-50 border-indigo-200">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <div>
                <h3 class="font-semibold text-indigo-900 mb-1">بروزرسانی ماژول‌ها موجود است</h3>
                <p class="text-sm text-indigo-800 mb-2">برای {{ count($moduleUpdates) }} ماژول بروزرسانی جدید در دسترس است:</p>
                <ul class="text-sm text-indigo-800 space-y-1 mb-3">
                    @foreach($moduleUpdates as $u)
                        <li>• {{ $u['title'] ?? $u['slug'] ?? '—' }} ({{ $u['installed_version'] ?? '—' }} → {{ $u['available_version'] ?? '—' }})</li>
                    @endforeach
                </ul>
                <a href="{{ route('platform-products.index') }}" class="text-indigo-700 font-medium text-sm hover:underline">دریافت بروزرسانی از پلتفرم</a>
            </div>
        </div>
    </div>
@endif

@php
    $blogModuleEnabled = in_array('blog', $modulesFromPlatform ?? [], true);
    $blogHasUpdate = !empty($blogUpdate);
    $shopModuleEnabled = in_array('shop', $modulesFromPlatform ?? [], true);
    $shopHasUpdate = !empty($shopUpdate);
    $menuModuleEnabled = in_array('menu', $modulesFromPlatform ?? [], true);
    $menuHasUpdate = !empty($menuUpdate);
@endphp

@if($blogModuleEnabled)
    <div class="card p-5 mb-6 border-sky-200 bg-sky-50">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 class="font-semibold text-sky-900">وضعیت حرفه‌ای ماژول وبلاگ</h3>
                <p class="text-sm text-sky-800 mt-1">
                    نسخه نصب‌شده: <code>{{ $blogInstalledVersion ?: '—' }}</code>
                    <span class="mx-1">|</span>
                    نسخه قابل دریافت: <code>{{ $blogAvailableVersion ?: '—' }}</code>
                </p>
                @if($blogHasUpdate)
                    <p class="text-xs text-amber-700 mt-1">
                        بروزرسانی برای وبلاگ آماده است. پیشنهاد می‌شود پس از دریافت از پلتفرم، همگام‌سازی لاگ نسخه را اجرا کنید.
                    </p>
                @else
                    <p class="text-xs text-emerald-700 mt-1">وبلاگ در حال حاضر همگام با آخرین نسخهٔ ثبت‌شده است.</p>
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                <form action="{{ route('admin.license.refresh-modules') }}" method="post">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">دریافت بروزرسانی ماژول‌ها</button>
                </form>
                <a href="{{ route('admin.modules.changelog', ['module' => 'blog']) }}" class="btn btn-secondary btn-sm">لاگ تغییرات وبلاگ</a>
                <a href="{{ route('admin.modules.changelog', ['sync' => 1, 'module' => 'blog']) }}" class="btn btn-secondary btn-sm">همگام‌سازی لاگ وبلاگ</a>
            </div>
        </div>

        @if(($blogLatestLogs ?? collect())->count() > 0)
            <div class="mt-4 space-y-2">
                @foreach($blogLatestLogs as $blogLog)
                    <article class="rounded-lg border border-sky-100 bg-white p-3">
                        <div class="flex items-center justify-between gap-2 text-xs text-slate-600">
                            <div>
                                <span class="font-mono">v{{ $blogLog->version }}</span>
                                <span class="badge badge-secondary mr-1">{{ strtoupper($blogLog->release_type) }}</span>
                            </div>
                            <div>{{ $blogLog->released_at ? \App\Helpers\Jalali::formatFa($blogLog->released_at, 'Y/m/d H:i') : '—' }}</div>
                        </div>
                        @if($blogLog->changelog)
                            <p class="mt-2 text-xs text-slate-700 whitespace-pre-line">{{ \Illuminate\Support\Str::limit($blogLog->changelog, 220) }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </div>
@endif

@if($shopModuleEnabled)
    <div class="card p-5 mb-6 border-emerald-200 bg-emerald-50">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 class="font-semibold text-emerald-900">وضعیت حرفه‌ای ماژول فروشگاه</h3>
                <p class="text-sm text-emerald-800 mt-1">
                    نسخه نصب‌شده: <code>{{ $shopInstalledVersion ?: '—' }}</code>
                    <span class="mx-1">|</span>
                    نسخه قابل دریافت: <code>{{ $shopAvailableVersion ?: '—' }}</code>
                </p>
                @if($shopHasUpdate)
                    <p class="text-xs text-amber-700 mt-1">
                        بروزرسانی برای فروشگاه آماده است. پیشنهاد می‌شود پس از دریافت از پلتفرم، لاگ نسخهٔ shop همگام‌سازی شود.
                    </p>
                @else
                    <p class="text-xs text-emerald-700 mt-1">فروشگاه در حال حاضر همگام با آخرین نسخهٔ ثبت‌شده است.</p>
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                <form action="{{ route('admin.license.refresh-modules') }}" method="post">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">دریافت بروزرسانی ماژول‌ها</button>
                </form>
                <a href="{{ route('admin.modules.changelog', ['module' => 'shop']) }}" class="btn btn-secondary btn-sm">لاگ تغییرات فروشگاه</a>
                <a href="{{ route('admin.modules.changelog', ['sync' => 1, 'module' => 'shop']) }}" class="btn btn-secondary btn-sm">همگام‌سازی لاگ فروشگاه</a>
            </div>
        </div>

        @if(($shopLatestLogs ?? collect())->count() > 0)
            <div class="mt-4 space-y-2">
                @foreach($shopLatestLogs as $shopLog)
                    <article class="rounded-lg border border-emerald-100 bg-white p-3">
                        <div class="flex items-center justify-between gap-2 text-xs text-slate-600">
                            <div>
                                <span class="font-mono">v{{ $shopLog->version }}</span>
                                <span class="badge badge-secondary mr-1">{{ strtoupper($shopLog->release_type) }}</span>
                            </div>
                            <div>{{ $shopLog->released_at ? \App\Helpers\Jalali::formatFa($shopLog->released_at, 'Y/m/d H:i') : '—' }}</div>
                        </div>
                        @if($shopLog->changelog)
                            <p class="mt-2 text-xs text-slate-700 whitespace-pre-line">{{ \Illuminate\Support\Str::limit($shopLog->changelog, 220) }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </div>
@endif

@if($menuModuleEnabled)
    <div class="card p-5 mb-6 border-violet-200 bg-violet-50">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 class="font-semibold text-violet-900">وضعیت حرفه‌ای ماژول منو</h3>
                <p class="text-sm text-violet-800 mt-1">
                    نسخه نصب‌شده: <code>{{ $menuInstalledVersion ?: '—' }}</code>
                    <span class="mx-1">|</span>
                    نسخه قابل دریافت: <code>{{ $menuAvailableVersion ?: '—' }}</code>
                </p>
                <p class="text-xs text-violet-700 mt-1">
                    حالت فعلی منو: <strong>{{ $menuMode === 'local' ? 'محلی' : 'پلتفرم' }}</strong>
                    <span class="mx-1">|</span>
                    آیتم‌های محلی هدر: <strong>{{ $menuLocalHeaderCount }}</strong>
                    <span class="mx-1">|</span>
                    آیتم‌های محلی فوتر: <strong>{{ $menuLocalFooterCount }}</strong>
                </p>
                @if($menuHasUpdate)
                    <p class="text-xs text-amber-700 mt-1">بروزرسانی جدید برای ماژول منو موجود است.</p>
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.modules.show', ['slug' => 'menu']) }}" class="btn btn-secondary btn-sm">تنظیمات ماژول منو</a>
                <a href="{{ route('admin.menus.index') }}" class="btn btn-secondary btn-sm">مدیریت منوهای محلی</a>
                <a href="{{ route('admin.modules.changelog', ['module' => 'menu']) }}" class="btn btn-secondary btn-sm">لاگ تغییرات منو</a>
                <a href="{{ route('admin.modules.changelog', ['sync' => 1, 'module' => 'menu']) }}" class="btn btn-secondary btn-sm">همگام‌سازی لاگ منو</a>
            </div>
        </div>
        @if(($menuLatestLogs ?? collect())->count() > 0)
            <div class="mt-4 space-y-2">
                @foreach($menuLatestLogs as $menuLog)
                    <article class="rounded-lg border border-violet-100 bg-white p-3">
                        <div class="flex items-center justify-between gap-2 text-xs text-slate-600">
                            <div>
                                <span class="font-mono">v{{ $menuLog->version }}</span>
                                <span class="badge badge-secondary mr-1">{{ strtoupper($menuLog->release_type) }}</span>
                            </div>
                            <div>{{ $menuLog->released_at ? \App\Helpers\Jalali::formatFa($menuLog->released_at, 'Y/m/d H:i') : '—' }}</div>
                        </div>
                        @if($menuLog->changelog)
                            <p class="mt-2 text-xs text-slate-700 whitespace-pre-line">{{ \Illuminate\Support\Str::limit($menuLog->changelog, 220) }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </div>
@endif

{{-- بروزرسانی ماژول‌ها از پلتفرم --}}
@if($license && $license->license_key)
    <div class="mb-6 flex items-center gap-2">
        <form action="{{ route('admin.license.refresh-modules') }}" method="post">
            @csrf
            <button type="submit" class="btn btn-secondary">بروزرسانی لیست ماژول‌ها از پلتفرم</button>
        </form>
        <a href="{{ route('admin.modules.changelog') }}" class="btn btn-secondary">تاریخچه نسخه و تغییرات</a>
    </div>
@endif

{{-- لیست ماژول‌های خریداری‌شده --}}
<div class="card">
    <div class="card-header">ماژول‌های خریداری‌شده از پلتفرم</div>
    <div class="card-body">
        @if(!empty($modulesFromPlatform))
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($modulesFromPlatform as $slug)
                    @php
                        $info = $localModules[$slug] ?? ['name' => $slug, 'enabled' => false];
                        $updateRow = collect($moduleUpdates ?? [])->firstWhere('slug', $slug);
                        $latestLog = $latestModuleLogs[$slug] ?? null;
                        $hasUpdate = !empty($updateRow);
                        $installedVersion = $installedVersions[$slug] ?? ($updateRow['installed_version'] ?? '1.0.0');
                        $availableVersion = $updateRow['available_version'] ?? $installedVersion;

                        // نگاشت slug ماژول به کلید منو برای آیکن
                        $iconMap = [
                            'blog' => 'posts',
                            'shop' => 'products',
                            'forms' => 'forms',
                            'advanced_search' => 'advanced_search',
                            'menu' => 'menu',
                        ];
                        $iconKey = $iconMap[$slug] ?? $slug;

                        // دسته‌بندی ماژول برای راهنمای بصری
                        $category = 'سایر';
                        $categoryColor = 'bg-slate-100 text-slate-700';
                        if (in_array($slug, ['blog', 'forms', 'advanced_search', 'menu', 'gallery', 'podcast', 'video', 'portfolio', 'projects', 'qa', 'services'], true)) {
                            $category = 'محتوا و سایت';
                            $categoryColor = 'bg-sky-100 text-sky-800';
                        } elseif (in_array($slug, ['shop', 'coupon', 'reviews', 'referral', 'subscription', 'courses', 'invoice', 'business_card', 'social_links', 'wallet', 'product_bundling', 'inventory', 'shipping', 'multi_currency', 'marketplace_sync'], true)) {
                            $category = 'فروش و تجارت';
                            $categoryColor = 'bg-emerald-100 text-emerald-800';
                        } elseif (in_array($slug, ['seo_advanced', 'analytics', 'marketing_automation', 'announcements', 'notifications', 'whatsapp', 'sms', 'omnichannel', 'crm'], true)) {
                            $category = 'مارکتینگ و ارتباط';
                            $categoryColor = 'bg-amber-100 text-amber-800';
                        } elseif (in_array($slug, ['accounting', 'backup', 'cloud', 'brand', 'i18n', 'tasks'], true)) {
                            $category = 'سیستم و زیرساخت';
                            $categoryColor = 'bg-indigo-100 text-indigo-800';
                        }

                        // توضیح کوتاه برای روش استفاده
                        $usageText = match ($slug) {
                            'blog' => 'برای مدیریت مقالات وبلاگ از منوی «محتوا → مطالب وبلاگ» و «دسته‌بندی‌ها» استفاده کنید.',
                            'shop' => 'برای مدیریت محصولات و سفارش‌ها از منوی «فروشگاه → محصولات / سفارشات / کوپن‌ها» استفاده کنید.',
                            'forms' => 'برای ساخت فرم‌های سفارشی از منوی «فرم و محتوا → فرم‌ساز» و برای گزارش‌گیری از «گزارش ارسال فرم‌ها» استفاده کنید.',
                            'advanced_search' => 'تنظیمات جستجوی پیشرفته در صفحهٔ «ماژول‌ها → جستجوی پیشرفته» در ادمین این سایت قابل ویرایش است.',
                            'menu' => 'برای تنظیم منوهای هدر/فوتر از صفحهٔ «ماژول‌ها → منو و ناوبری» در ادمین این سایت استفاده کنید.',
                            'seo_advanced' => 'داشبورد سئو پیشرفته در «سئو پیشرفته» در منوی ماژول‌های از پلتفرم در ادمین این سایت در دسترس است.',
                            'accounting' => 'نمای کلی مالی سایت در صفحهٔ «حسابداری» در منوی ماژول‌های از پلتفرم نمایش داده می‌شود.',
                            'analytics' => 'پس از فعال‌سازی، گزارش‌های تحلیلی در صفحهٔ «تحلیل پیشرفته» از منوی ماژول‌های از پلتفرم در دسترس خواهد بود.',
                            'wallet' => 'جزئیات کیف پول کاربران و تراکنش‌ها از طریق صفحهٔ «کیف پول» در منوی ماژول‌های از پلتفرم قابل مشاهده است.',
                            default => 'پس از فعال‌سازی، این ماژول یک صفحهٔ اختصاصی با آیکن مربوطه در گروه «ماژول‌های از پلتفرم» در منوی سمت راست اضافه می‌کند.',
                        };
                    @endphp
                    <div class="p-4 rounded-xl border {{ $info['enabled'] ? 'bg-green-50 border-green-100' : 'bg-slate-50 border-slate-200' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="module-menu-icon" aria-hidden="true">
                                    @include('admin.partials.sidebar-icon', ['key' => $iconKey])
                                </span>
                                <div>
                                    <div class="font-semibold text-slate-900">
                                        {{ $info['name'] ?? $slug }}
                                    </div>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        slug: <code class="text-[11px]">{{ $slug }}</code>
                                    </div>
                                </div>
                            </div>
                            <div class="text-left space-y-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $categoryColor }}">
                                    {{ $category }}
                                </span>
                                <div>
                                    <span class="badge {{ $info['enabled'] ? 'badge-success' : 'badge-warning' }}">
                                        {{ $info['enabled'] ? 'فعال در این سایت' : 'غیرفعال در این سایت' }}
                                    </span>
                                </div>
                                @if($hasUpdate)
                                    <div class="text-[11px] text-amber-700">
                                        بروزرسانی جدید برای این ماژول موجود است.
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="mt-3 text-xs text-slate-700 leading-relaxed">
                            {{ $usageText }}
                        </div>
                        <div class="mt-2 text-xs text-slate-600">
                            نسخه نصب‌شده: <code>{{ $installedVersion ?: '—' }}</code>
                            @if($hasUpdate)
                                <span class="mx-1">→</span>
                                نسخه قابل دریافت: <code>{{ $availableVersion }}</code>
                            @endif
                        </div>
                        @if($latestLog)
                            <div class="mt-2 text-xs text-slate-600 leading-relaxed">
                                آخرین انتشار: <code>v{{ $latestLog->version }}</code>
                                @if($latestLog->changelog)
                                    <div class="mt-1 text-slate-700">{{ \Illuminate\Support\Str::limit($latestLog->changelog, 140) }}</div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-slate-500 text-sm">
                @if($license && $license->license_key)
                    ماژولی از پلتفرم دریافت نشده. در پنل پلتفرم (سایت‌ها → ویرایش سایت → ماژول‌ها) ماژول‌ها را به این سایت تخصیص دهید، سپس دکمهٔ «بروزرسانی لیست ماژول‌ها از پلتفرم» را بزنید.
                @else
                    ابتدا لایسنس را فعال کنید.
                @endif
            </p>
        @endif
    </div>
</div>

{{-- وضعیت پشتیبانی (لینک به صفحه پشتیبانی) --}}
@if($license && $license->support_expires_at)
    <div class="card mt-6">
        <div class="card-header">وضعیت پشتیبانی</div>
        <div class="card-body">
            @php
                $daysUntil = $license->support_expires_at->isPast() ? 0 : $license->support_expires_at->diffInDays(now(), false);
                $supportStatus = $license->support_expires_at->isPast() ? 'expired' : ($daysUntil <= 30 ? 'expiring_soon' : 'active');
            @endphp
            <div class="flex items-center justify-between">
                <div>
                    <span class="font-medium">انقضای پشتیبانی:</span>
                    <span class="{{ $supportStatus === 'expired' ? 'text-red-600' : ($supportStatus === 'expiring_soon' ? 'text-amber-600' : 'text-slate-600') }}">
                        {{ \App\Helpers\Jalali::formatFa($license->support_expires_at, 'Y/m/d') }}
                    </span>
                    @if($supportStatus === 'expired')
                        <span class="badge badge-danger mr-2">منقضی</span>
                    @elseif($supportStatus === 'expiring_soon')
                        <span class="badge badge-warning mr-2">به‌زودی منقضی</span>
                    @else
                        <span class="badge badge-success mr-2">فعال</span>
                    @endif
                </div>
                <a href="{{ route('admin.support.index') }}" class="btn btn-secondary btn-sm">پشتیبانی</a>
            </div>
        </div>
    </div>
@endif
</div>
@endsection

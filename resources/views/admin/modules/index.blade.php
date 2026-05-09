@extends('admin.layout')
@section('title', 'ماژول‌ها')
@section('header', 'ماژول‌ها و امکانات')
@section('content')
<div class="admin-modules-page">

@if(!$license || empty($license->license_key))
    <div class="admin-pro-banner admin-pro-banner--license" role="status">
        <svg class="admin-pro-banner__icon" style="color:#b45309" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        <div>
            <p class="admin-pro-module-card__meta" style="margin:0;color:#92400e">
                برای مشاهده ماژول‌های خریداری‌شده، ابتدا <a href="{{ route('admin.license.index') }}" class="font-medium">لایسنس</a> را فعال کنید.
            </p>
        </div>
    </div>
@endif

{{-- اعلان بروزرسانی موجود --}}
@if(!empty($moduleUpdates))
    <div class="admin-pro-banner admin-pro-banner--updates" role="status">
        <svg class="admin-pro-banner__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        <div>
            <h3 class="admin-pro-module-card__title" style="color:#312e81">بروزرسانی ماژول‌ها موجود است</h3>
            <p class="admin-pro-module-card__meta" style="color:#3730a3;margin-top:0.25rem">برای {{ count($moduleUpdates) }} ماژول بروزرسانی جدید در دسترس است:</p>
            <ul class="admin-pro-module-card__meta space-y-1 mb-2" style="color:#3730a3;margin:0.5rem 0 0;padding-right:1rem">
                @foreach($moduleUpdates as $u)
                    <li>{{ $u['title'] ?? $u['slug'] ?? '—' }} ({{ $u['installed_version'] ?? '—' }} → {{ $u['available_version'] ?? '—' }})</li>
                @endforeach
            </ul>
            <a href="{{ route('platform-products.index') }}" class="btn btn-outline-primary btn-sm" style="margin-top:0.35rem">دریافت بروزرسانی از پلتفرم</a>
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
    <section class="admin-pro-module-card admin-pro-module-card--blog" aria-labelledby="blog-module-status-title">
        <div class="admin-pro-module-card__top">
            <div>
                <h3 id="blog-module-status-title" class="admin-pro-module-card__title">وضعیت حرفه‌ای ماژول وبلاگ</h3>
                <p class="admin-pro-module-card__meta">
                    نسخه نصب‌شده: <code>{{ $blogInstalledVersion ?: '—' }}</code>
                    <span aria-hidden="true"> | </span>
                    نسخه قابل دریافت: <code>{{ $blogAvailableVersion ?: '—' }}</code>
                </p>
                @if($blogHasUpdate)
                    <p class="admin-pro-module-card__note" style="color:#b45309">
                        بروزرسانی برای وبلاگ آماده است. پیشنهاد می‌شود پس از دریافت از پلتفرم، همگام‌سازی لاگ نسخه را اجرا کنید.
                    </p>
                @else
                    <p class="admin-pro-module-card__note" style="color:#15803d">وبلاگ در حال حاضر همگام با آخرین نسخهٔ ثبت‌شده است.</p>
                @endif
            </div>
            <div class="admin-pro-module-card__actions">
                <form action="{{ route('admin.license.refresh-modules') }}" method="post">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">دریافت بروزرسانی ماژول‌ها</button>
                </form>
                <a href="{{ route('admin.modules.changelog', ['module' => 'blog']) }}" class="btn btn-secondary btn-sm">لاگ تغییرات وبلاگ</a>
                <a href="{{ route('admin.modules.changelog', ['sync' => 1, 'module' => 'blog']) }}" class="btn btn-secondary btn-sm">همگام‌سازی لاگ وبلاگ</a>
            </div>
        </div>

        @if(($blogLatestLogs ?? collect())->count() > 0)
            <div class="admin-pro-module-card__logs">
                @foreach($blogLatestLogs as $blogLog)
                    <article class="admin-pro-log-entry">
                        <div class="admin-pro-log-entry__row">
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
    </section>
@endif

@if($shopModuleEnabled)
    <section class="admin-pro-module-card admin-pro-module-card--shop" aria-labelledby="shop-module-status-title">
        <div class="admin-pro-module-card__top">
            <div>
                <h3 id="shop-module-status-title" class="admin-pro-module-card__title">وضعیت حرفه‌ای ماژول فروشگاه</h3>
                <p class="admin-pro-module-card__meta">
                    نسخه نصب‌شده: <code>{{ $shopInstalledVersion ?: '—' }}</code>
                    <span aria-hidden="true"> | </span>
                    نسخه قابل دریافت: <code>{{ $shopAvailableVersion ?: '—' }}</code>
                </p>
                @if($shopHasUpdate)
                    <p class="admin-pro-module-card__note" style="color:#b45309">
                        بروزرسانی برای فروشگاه آماده است. پیشنهاد می‌شود پس از دریافت از پلتفرم، لاگ نسخهٔ shop همگام‌سازی شود.
                    </p>
                @else
                    <p class="admin-pro-module-card__note" style="color:#15803d">فروشگاه در حال حاضر همگام با آخرین نسخهٔ ثبت‌شده است.</p>
                @endif
            </div>
            <div class="admin-pro-module-card__actions">
                <form action="{{ route('admin.license.refresh-modules') }}" method="post">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">دریافت بروزرسانی ماژول‌ها</button>
                </form>
                <a href="{{ route('admin.modules.changelog', ['module' => 'shop']) }}" class="btn btn-secondary btn-sm">لاگ تغییرات فروشگاه</a>
                <a href="{{ route('admin.modules.changelog', ['sync' => 1, 'module' => 'shop']) }}" class="btn btn-secondary btn-sm">همگام‌سازی لاگ فروشگاه</a>
            </div>
        </div>

        @if(($shopLatestLogs ?? collect())->count() > 0)
            <div class="admin-pro-module-card__logs">
                @foreach($shopLatestLogs as $shopLog)
                    <article class="admin-pro-log-entry">
                        <div class="admin-pro-log-entry__row">
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
    </section>
@endif

@if($menuModuleEnabled)
    <section class="admin-pro-module-card admin-pro-module-card--menu" aria-labelledby="menu-module-status-title">
        <div class="admin-pro-module-card__top">
            <div>
                <h3 id="menu-module-status-title" class="admin-pro-module-card__title">وضعیت حرفه‌ای ماژول منو</h3>
                <p class="admin-pro-module-card__meta">
                    نسخه نصب‌شده: <code>{{ $menuInstalledVersion ?: '—' }}</code>
                    <span aria-hidden="true"> | </span>
                    نسخه قابل دریافت: <code>{{ $menuAvailableVersion ?: '—' }}</code>
                </p>
                <p class="admin-pro-module-card__note" style="color:#5b21b6">
                    حالت فعلی منو: <strong>{{ $menuMode === 'local' ? 'محلی' : 'پلتفرم' }}</strong>
                    <span aria-hidden="true"> | </span>
                    آیتم‌های محلی هدر: <strong>{{ $menuLocalHeaderCount }}</strong>
                    <span aria-hidden="true"> | </span>
                    آیتم‌های محلی فوتر: <strong>{{ $menuLocalFooterCount }}</strong>
                </p>
                @if($menuHasUpdate)
                    <p class="admin-pro-module-card__note" style="color:#b45309">بروزرسانی جدید برای ماژول منو موجود است.</p>
                @endif
            </div>
            <div class="admin-pro-module-card__actions">
                <a href="{{ route('admin.modules.show', ['slug' => 'menu']) }}" class="btn btn-secondary btn-sm">تنظیمات ماژول منو</a>
                <a href="{{ route('admin.menus.index') }}" class="btn btn-secondary btn-sm">مدیریت منوهای محلی</a>
                <a href="{{ route('admin.modules.changelog', ['module' => 'menu']) }}" class="btn btn-secondary btn-sm">لاگ تغییرات منو</a>
                <a href="{{ route('admin.modules.changelog', ['sync' => 1, 'module' => 'menu']) }}" class="btn btn-secondary btn-sm">همگام‌سازی لاگ منو</a>
            </div>
        </div>
        @if(($menuLatestLogs ?? collect())->count() > 0)
            <div class="admin-pro-module-card__logs">
                @foreach($menuLatestLogs as $menuLog)
                    <article class="admin-pro-log-entry">
                        <div class="admin-pro-log-entry__row">
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
    </section>
@endif

{{-- بروزرسانی ماژول‌ها از پلتفرم --}}
@if($license && $license->license_key)
    <div class="admin-toolbar-row">
        <form action="{{ route('admin.license.refresh-modules') }}" method="post">
            @csrf
            <button type="submit" class="btn btn-secondary">بروزرسانی لیست ماژول‌ها از پلتفرم</button>
        </form>
        <a href="{{ route('admin.modules.changelog') }}" class="btn btn-outline-secondary">تاریخچه نسخه و تغییرات</a>
    </div>
@endif

{{-- لیست ماژول‌های خریداری‌شده --}}
<div class="card">
    <div class="card-header">ماژول‌های خریداری‌شده از پلتفرم</div>
    <div class="card-body">
        @if(!empty($modulesFromPlatform))
            <div class="admin-module-grid">
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
                            'blog' => 'برای مدیریت مقالات از منوی «محتوا → مطالب وبلاگ» و «دسته‌بندی‌ها» استفاده کنید؛ در فرم مطلب ویرایشگر پیشرفته (TinyMCE) و زمان انتشار با تقویم و ساعت شمسی است و در فهرست ادمین تاریخ‌ها با ارقام فارسی دیده می‌شوند.',
                            'shop' => 'برای مدیریت محصولات و سفارش‌ها از منوی «فروشگاه → محصولات / سفارشات / کوپن‌ها» استفاده کنید؛ در فرم محصول توضیحات و سیاست فروش با ویرایشگر پیشرفته و پایان حراج با تاریخ و ساعت شمسی تنظیم می‌شود و در فهرست، ثبت/ویرایش و پایان حراج شمسی نمایش داده می‌شود.',
                            'forms' => 'از «فرم و محتوا → فرم‌ساز» فرم بسازید؛ فیلدها، نامک، متن راهنمای بالای فرم و متا سئو (عنوان/توضیح/canonical/robots) را در همان بخش تنظیم کنید. گزارش ارسال‌ها در «گزارش ارسال فرم‌ها» است؛ فرم‌های فعال در sitemap و با breadcrumb/JSON-LD در فرانت دیده می‌شوند.',
                            'advanced_search' => 'تنظیمات جستجوی پیشرفته در صفحهٔ «ماژول‌ها → جستجوی پیشرفته» در ادمین این سایت قابل ویرایش است.',
                            'menu' => 'برای تنظیم منوهای هدر/فوتر از صفحهٔ «ماژول‌ها → منو و ناوبری» در ادمین این سایت استفاده کنید.',
                            'seo_advanced' => 'داشبورد سئو پیشرفته در «سئو پیشرفته» در منوی ماژول‌های از پلتفرم در ادمین این سایت در دسترس است.',
                            'accounting' => 'نمای کلی مالی سایت در صفحهٔ «حسابداری» در منوی ماژول‌های از پلتفرم نمایش داده می‌شود.',
                            'analytics' => 'پس از فعال‌سازی، گزارش‌های تحلیلی در صفحهٔ «تحلیل پیشرفته» از منوی ماژول‌های از پلتفرم در دسترس خواهد بود.',
                            'wallet' => 'جزئیات کیف پول کاربران و تراکنش‌ها از طریق صفحهٔ «کیف پول» در منوی ماژول‌های از پلتفرم قابل مشاهده است.',
                            default => 'پس از فعال‌سازی، این ماژول یک صفحهٔ اختصاصی با آیکن مربوطه در گروه «ماژول‌های از پلتفرم» در منوی سمت راست اضافه می‌کند.',
                        };
                    @endphp
                    <div class="admin-module-tile {{ $info['enabled'] ? 'admin-module-tile--enabled' : 'admin-module-tile--disabled' }}">
                        <div class="admin-module-tile__head">
                            <div class="flex items-center gap-2">
                                <span class="module-menu-icon" aria-hidden="true">
                                    @include('admin.partials.sidebar-icon', ['key' => $iconKey])
                                </span>
                                <div>
                                    <p class="admin-module-tile__title">{{ $info['name'] ?? $slug }}</p>
                                    <p class="admin-module-tile__slug">slug: <code>{{ $slug }}</code></p>
                                </div>
                            </div>
                            <div class="admin-module-tile__badges">
                                <span class="admin-module-category-pill {{ $categoryColor }}">{{ $category }}</span>
                                <span class="badge {{ $info['enabled'] ? 'badge-success' : 'badge-warning' }}">
                                    {{ $info['enabled'] ? 'فعال در این سایت' : 'غیرفعال در این سایت' }}
                                </span>
                                @if($hasUpdate)
                                    <span class="admin-field-hint" style="color:#b45309;font-size:0.68rem">بروزرسانی جدید برای این ماژول موجود است.</span>
                                @endif
                            </div>
                        </div>
                        <p class="admin-module-tile__usage">{{ $usageText }}</p>
                        <p class="admin-module-tile__usage" style="margin-top:0.35rem;color:#475569">
                            نسخه نصب‌شده: <code>{{ $installedVersion ?: '—' }}</code>
                            @if($hasUpdate)
                                <span aria-hidden="true"> → </span>
                                نسخه قابل دریافت: <code>{{ $availableVersion }}</code>
                            @endif
                        </p>
                        @if($latestLog)
                            <p class="admin-module-tile__usage" style="margin-top:0.35rem">
                                آخرین انتشار: <code>v{{ $latestLog->version }}</code>
                                @if($latestLog->changelog)
                                    <span class="block mt-1" style="color:#334155">{{ \Illuminate\Support\Str::limit($latestLog->changelog, 140) }}</span>
                                @endif
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="admin-empty-state" role="status">
                <svg class="admin-empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <p class="admin-empty-state__title">ماژولی نمایش داده نمی‌شود</p>
                <p class="admin-empty-state__text">
                    @if($license && $license->license_key)
                        ماژولی از پلتفرم دریافت نشده. در پنل پلتفرم (سایت‌ها → ویرایش سایت → ماژول‌ها) ماژول‌ها را به این سایت تخصیص دهید، سپس دکمهٔ «بروزرسانی لیست ماژول‌ها از پلتفرم» را بزنید.
                    @else
                        ابتدا لایسنس را فعال کنید.
                    @endif
                </p>
            </div>
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

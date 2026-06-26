@extends('admin.layout')
@section('title', 'اعلان‌ها')
@section('header', 'اعلان‌ها')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">اعلان‌ها و نوار پیام</h1>
            <p class="text-slate-600 text-sm mt-1">مدیریت اعلان‌های نوار اطلاع‌رسانی (تولبار) برای بازدیدکنندگان سایت و اعلان‌های داخلی کاربران.</p>
            <p class="text-slate-500 text-xs mt-1">
                اعلان‌های ادمین این سایت از پلتفرم اصلی (saas-platform) در دکمهٔ زنگولهٔ پایین راست پنل مدیریت نمایش داده می‌شوند.
                برای همگام‌سازی اعلان‌های ادمین از پلتفرم، در
                <a href="{{ route('admin.license.index') }}" class="text-indigo-600 hover:underline">صفحهٔ لایسنس</a>
                روی «بازخوانی تنظیمات و اعلان‌ها» کلیک کنید.
            </p>
        </div>
        <div class="admin-page-tools">
            <a href="{{ route('admin.notifications.send-users.form') }}" class="btn btn-secondary">
                <x-core-ui::icon name="message" class="w-4 h-4" />
                ارسال اعلان به کاربران
            </a>
            @if($canManage)
                <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary">
                    <x-core-ui::icon name="message" class="w-4 h-4" />
                    اعلان جدید (تولبار)
                </a>
            @else
                <button type="button" class="btn btn-primary opacity-60 cursor-not-allowed" disabled>
                    اعلان جدید (لایسنس منقضی)
                </button>
            @endif
        </div>
    </div>

<details class="mb-6 bg-white border border-slate-200 rounded-xl">
    <summary class="px-4 py-3 cursor-pointer flex items-center justify-between">
        <div class="flex items-center gap-2">
            <x-core-ui::icon name="message" class="w-4 h-4 text-indigo-600" />
            <span class="font-semibold text-sm">راهنمای ماژول اعلان‌ها</span>
        </div>
        <span class="text-xs text-slate-500">کلیک برای باز و بسته کردن</span>
    </summary>
    <div class="px-4 pb-4 pt-1 text-sm text-slate-600 space-y-2">
        <p>این ماژول دو نوع اعلان را مدیریت می‌کند: ۱) اعلان‌های نوار بالای سایت (تولبار) برای همهٔ بازدیدکنندگان، ۲) اعلان‌های داخل «حساب من» برای کاربران ثبت‌نام‌کرده.</p>
        <ul class="list-disc pr-5 space-y-1 text-slate-600">
            <li>برای اعلان‌های عمومی سایت (بنر اطلاعیه، تخفیف، هشدار سرویس)، از لیست تولبار در همین صفحه استفاده کنید.</li>
            <li>برای ارسال پیام به کاربران خاص (مثلاً فقط خریداران یا کاربران ثبت‌نام‌شده در بازهٔ زمانی مشخص)، از دکمهٔ «ارسال اعلان به کاربران» استفاده کنید.</li>
            <li>تاریخ‌ها را همیشه به‌صورت شمسی وارد کنید؛ سیستم به‌طور خودکار آن‌ها را به فرمت دیتابیس تبدیل می‌کند.</li>
            <li>به‌جای حذف اعلان‌های قدیمی، ترجیحاً آن‌ها را غیرفعال کنید تا تاریخچهٔ کمپین‌ها و اطلاعیه‌ها حفظ شود.</li>
        </ul>
    </div>
</details>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div class="card">
        <div class="card-body">
            <div class="text-xs text-slate-500 mb-1">تعداد کل اعلان‌های تولبار</div>
            <div class="text-2xl font-bold text-slate-800">@numFa($stats['total'] ?? 0)</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="text-xs text-slate-500 mb-1">اعلان‌های فعال</div>
            <div class="text-2xl font-bold text-emerald-700">@numFa($stats['active'] ?? 0)</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="text-xs text-slate-500 mb-1">وضعیت لایسنس اعلان‌ها</div>
            @php
                $licenseValid = $license && $license->isValid();
            @endphp
            @if($license && is_array($license->modules ?? []) && in_array('notifications', $license->modules, true))
                @if($licenseValid)
                    <div class="text-sm text-emerald-700 font-semibold">فعال و قابل مدیریت</div>
                @else
                    <div class="text-sm text-amber-700 font-semibold">ماژول قبلاً فعال بوده؛ لایسنس فعلی معتبر نیست (فقط مشاهده مجاز است).</div>
                @endif
            @else
                <div class="text-sm text-slate-500">این ماژول در لایسنس فعلی ثبت نشده است.</div>
            @endif
        </div>
    </div>
</div>

<div class="card mb-4 admin-sticky-filters admin-hover-lift">
    <div class="card-body">
        <form method="get" action="{{ route('admin.notifications.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="form-group mb-0">
                <label class="form-label">جستجو در عنوان/متن</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control w-56" placeholder="مثلاً تخفیف، اطلاعیه مهم...">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">وضعیت</label>
                <select name="status" class="form-control w-40">
                    <option value="">همه</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>فقط فعال</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>فقط غیرفعال</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">از تاریخ (شمسی)</label>
                <x-persian-date-input name="from" :value="request('from')" placeholder="۱۴۰۳/۰۱/۰۱" class="form-control w-36" />
            </div>
            <div class="form-group mb-0">
                <label class="form-label">تا تاریخ (شمسی)</label>
                <x-persian-date-input name="to" :value="request('to')" placeholder="۱۴۰۳/۱۲/۲۹" class="form-control w-36" />
            </div>
            <div class="form-group mb-0">
                <label class="form-label">مرتب‌سازی</label>
                <select name="sort" class="form-control w-40">
                    <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>جدیدترین ابتدا</option>
                    <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>قدیمی‌ترین ابتدا</option>
                </select>
            </div>
            <div class="flex items-center gap-2 mt-2">
                <button type="submit" class="btn btn-primary btn-sm">اعمال فیلتر</button>
                <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary btn-sm">پاک کردن</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('admin.notifications.bulk') }}">
            @csrf
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <div class="text-sm text-slate-600">اعلان‌های تولبار سایت</div>
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <label class="flex items-center gap-1">
                        <input type="radio" name="action" value="activate" class="mr-1" checked>
                        <span>فعال کردن انتخاب‌شده‌ها</span>
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="radio" name="action" value="deactivate" class="mr-1">
                        <span>غیرفعال کردن انتخاب‌شده‌ها</span>
                    </label>
                    @if(!$canManage)
                        <span class="text-amber-600 ml-2">لایسنس منقضی است؛ اعمال گروهی غیرفعال شده است.</span>
                    @endif
                    <button type="submit" class="btn btn-secondary btn-sm {{ $canManage ? '' : 'opacity-50 cursor-not-allowed' }}" {{ $canManage ? '' : 'disabled' }}>اعمال برگزیده‌ها</button>
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                    <tr>
                        <th style="width:40px">
                            <input type="checkbox" onclick="document.querySelectorAll('.notif-row-checkbox').forEach(cb => cb.checked = this.checked);">
                        </th>
                        <th>عنوان</th>
                        <th class="hidden md:table-cell">مخاطب</th>
                        <th>نوع</th>
                        <th>لوگوی لرزان</th>
                        <th>بازه نمایش</th>
                        <th>وضعیت</th>
                        <th>نمایش در سایت</th>
                        <th style="width:200px">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($notifications as $n)
                        <tr>
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $n->id }}" class="notif-row-checkbox">
                            </td>
                            <td class="font-medium">{{ $n->title }}</td>
                            <td class="hidden md:table-cell"><span class="badge badge-success">کاربر نهایی</span></td>
                            <td>
                                @php
                                    $types = ['info' => 'اطلاع', 'success' => 'موفق', 'warning' => 'هشدار', 'danger' => 'مهم'];
                                @endphp
                                <span class="badge badge-{{ $n->type === 'danger' ? 'danger' : ($n->type === 'warning' ? 'warning' : 'success') }}">{{ $types[$n->type] ?? $n->type }}</span>
                            </td>
                            <td>{{ $n->show_shaking_logo ? '✓' : '—' }}</td>
                            <td class="text-sm">
                                @if($n->starts_at || $n->expires_at)
                                    {{ $n->starts_at ? \App\Helpers\Jalali::formatFa($n->starts_at, 'Y/m/d H:i') : '—' }}
                                    تا
                                    {{ $n->expires_at ? \App\Helpers\Jalali::formatFa($n->expires_at, 'Y/m/d H:i') : '—' }}
                                @else
                                    همیشه
                                @endif
                            </td>
                            <td>
                                @if($n->is_active)
                                    <span class="badge badge-success">فعال</span>
                                @else
                                    <span class="badge badge-danger">غیرفعال</span>
                                @endif
                            </td>
                            <td>
                                @if($canManage)
                                    <form method="post" action="{{ route('admin.notifications.toggle', $n) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-secondary btn-sm">
                                            {{ $n->is_active ? 'غیرفعال کن' : 'فعال کن' }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">فقط قابل مشاهده</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.notifications.edit', $n) }}" class="btn btn-primary btn-sm {{ $canManage ? '' : 'opacity-50 cursor-not-allowed' }}" @if(!$canManage) aria-disabled="true" @endif>ویرایش</a>
                                    @if($canManage)
                                        <form method="post" action="{{ route('admin.notifications.destroy', $n) }}" class="inline" onsubmit="return confirm('حذف این اعلان؟ این کار روی اعلان‌های کاربر تاثیری ندارد، اما بنر مربوطه در سایت نمایش داده نخواهد شد.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="admin-empty">
                                هنوز اعلانی ثبت نشده است.
                                @if($canManage)
                                    <a href="{{ route('admin.notifications.create') }}" class="text-indigo-600 hover:underline">اولین اعلان را ایجاد کنید</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>
    @if($notifications->hasPages())
        <div class="admin-pagination">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection



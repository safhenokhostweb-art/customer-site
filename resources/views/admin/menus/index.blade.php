@extends('admin.layout')
@section('title', 'منوها')
@section('header', 'منوها')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">منوها</h1>
            <p class="admin-section-lead" style="margin-top:0.35rem">مدیریت منوهای هدر، فوتر، موبایل، ثانویه و نوار بالا.</p>
        </div>
        <div class="admin-page-tools">
            <a href="{{ route('admin.menus.create') }}" class="btn btn-primary">منوی جدید</a>
        </div>
    </div>

    <form method="get" class="card mb-4 admin-sticky-filters" aria-label="فیلتر منوها">
        <div class="card-body admin-filter-bar">
            <div class="form-group mb-0">
                <label class="form-label" for="menu-filter-location">فیلتر موقعیت</label>
                <select id="menu-filter-location" name="location" onchange="this.form.submit()">
                    <option value="">همه</option>
                    @foreach($locations as $k => $lbl)
                        <option value="{{ $k }}" @selected(request('location') === $k)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div></div>
            <div class="flex items-end">
                <noscript><button type="submit" class="btn btn-secondary" style="min-height:2.75rem">اعمال</button></noscript>
            </div>
        </div>
    </form>

    <div class="card admin-table-wrap">
        <div class="table-responsive">
            <table>
                <thead><tr><th>نام</th><th>موقعیت</th><th>وضعیت</th><th>عملیات</th></tr></thead>
                <tbody>
                    @forelse($menus as $menu)
                        <tr>
                            <td>{{ $menu->name }}</td>
                            <td>{{ $locations[$menu->location] ?? $menu->location }}</td>
                            <td>
                                @if($menu->status === 'published')
                                    <span class="badge badge-published">منتشر</span>
                                @else
                                    <span class="badge badge-draft">پیش‌نویس</span>
                                @endif
                            </td>
                            <td>
                                <div class="admin-table-actions">
                                    <a href="{{ route('admin.menus.edit', $menu) }}" class="btn btn-primary btn-sm">ویرایش و مدیریت آیتم‌ها</a>
                                    <form method="post" action="{{ route('admin.menus.destroy', $menu) }}" class="inline" onsubmit="return confirm('حذف این منو؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-0">
                                <div class="admin-empty-state">
                                    <svg class="admin-empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/>
                                    </svg>
                                    <p class="admin-empty-state__title">هنوز منویی ایجاد نشده است</p>
                                    <p class="admin-empty-state__text">با «منوی جدید» یک منو برای موقعیت مورد نظر بسازید.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="admin-section-lead" style="margin-top:0.5rem">موقعیت‌ها: منوی اصلی (هدر)، فوتر، موبایل، نوار بالا. اگر برای موقعیت «منوی اصلی» منو بسازید، در هدر سایت نمایش داده می‌شود؛ در غیر این صورت از منوی پلتفرم استفاده می‌شود.</p>
</div>
@endsection

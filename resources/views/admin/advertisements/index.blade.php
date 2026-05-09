@extends('admin.layout')
@section('title', 'تبلیغات')
@section('header', 'تبلیغات')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">تبلیغات و بنرها</h1>
            <p class="admin-section-lead" style="margin-top:0.35rem">مدیریت بنر، متن متحرک و انواع نمایش در هدر، فوتر، نوار کناری و بالا/پایین محتوا؛ بازهٔ زمانی با تاریخ شمسی در فرم ویرایش.</p>
        </div>
        <div class="admin-page-tools">
            <a href="{{ route('admin.advertisements.create') }}" class="btn btn-primary">تبلیغ جدید</a>
        </div>
    </div>

    <div class="admin-kpi-grid mb-4">
        <div class="admin-kpi-card">
            <p class="admin-kpi-card__label">همهٔ رکوردها</p>
            <p class="admin-kpi-card__value">{{ number_format($stats['total'] ?? 0) }}</p>
        </div>
        <div class="admin-kpi-card">
            <p class="admin-kpi-card__label">فعال</p>
            <p class="admin-kpi-card__value text-emerald-700">{{ number_format($stats['active'] ?? 0) }}</p>
        </div>
        <div class="admin-kpi-card">
            <p class="admin-kpi-card__label">غیرفعال</p>
            <p class="admin-kpi-card__value text-slate-600">{{ number_format($stats['inactive'] ?? 0) }}</p>
        </div>
    </div>

    <div class="card mb-4 admin-sticky-filters">
        <div class="card-body">
            <form method="get" class="admin-filter-bar" aria-label="فیلتر تبلیغات">
                <div class="form-group mb-0">
                    <label class="form-label" for="ad-filter-type">نوع</label>
                    <select id="ad-filter-type" name="type" class="w-full min-w-[10rem]" onchange="this.form.submit()">
                        <option value="">همه انواع</option>
                        @foreach(\App\Models\Advertisement::typeLabels() as $k => $v)
                            <option value="{{ $k }}" @selected(request('type') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label" for="ad-filter-pos">موقعیت</label>
                    <select id="ad-filter-pos" name="position" class="w-full min-w-[10rem]" onchange="this.form.submit()">
                        <option value="">همه موقعیت‌ها</option>
                        @foreach(\App\Models\Advertisement::positionLabels() as $k => $v)
                            <option value="{{ $k }}" @selected(request('position') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-0 flex items-end">
                    <a href="{{ route('admin.advertisements.index') }}" class="btn btn-secondary w-full" style="min-height:2.75rem">حذف فیلتر</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card admin-table-wrap">
        <div class="table-responsive">
            <table>
            <thead>
                <tr>
                    <th>عنوان / خلاصه</th>
                    <th>نوع</th>
                    <th>موقعیت</th>
                    <th>ترتیب</th>
                    <th>بازهٔ نمایش (شمسی)</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($advertisements as $ad)
                    <tr>
                        <td>
                            <div class="font-medium">{{ $ad->title ?: '—' }}</div>
                            @if($ad->content)
                                <div class="text-xs text-slate-500 mt-0.5">{{ Str::limit(strip_tags($ad->content), 48) }}</div>
                            @endif
                            @if($ad->link)
                                <div class="text-xs text-indigo-600 truncate max-w-[14rem]" dir="ltr">{{ $ad->link }}</div>
                            @endif
                        </td>
                        <td>{{ \App\Models\Advertisement::typeLabels()[$ad->type] ?? $ad->type }}</td>
                        <td>{{ \App\Models\Advertisement::positionLabels()[$ad->position] ?? $ad->position }}</td>
                        <td>{{ $ad->order }}</td>
                        <td class="text-xs text-slate-600">
                            @if($ad->starts_at)
                                <div>از: {{ \App\Helpers\Jalali::formatFa($ad->starts_at, 'Y/m/d H:i') }}</div>
                            @endif
                            @if($ad->expires_at)
                                <div>تا: {{ \App\Helpers\Jalali::formatFa($ad->expires_at, 'Y/m/d H:i') }}</div>
                            @endif
                            @if(!$ad->starts_at && !$ad->expires_at)
                                —
                            @endif
                        </td>
                        <td>
                            @if($ad->is_active)
                                <span class="badge badge-published">فعال</span>
                            @else
                                <span class="badge badge-draft">غیرفعال</span>
                            @endif
                        </td>
                        <td>
                            <div class="admin-table-actions flex flex-wrap gap-1">
                                <a href="{{ route('admin.advertisements.edit', $ad) }}" class="btn btn-primary btn-sm">ویرایش</a>
                                <form method="post" action="{{ route('admin.advertisements.destroy', $ad) }}" class="inline" onsubmit="return confirm('حذف این تبلیغ؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-0">
                            <div class="admin-empty-state">
                                <p class="admin-empty-state__title">تبلیغی ثبت نشده</p>
                                <p class="admin-empty-state__text">برای نمایش بنر یا متن در سایت، اولین تبلیغ را بسازید.</p>
                                <a href="{{ route('admin.advertisements.create') }}" class="btn btn-primary btn-sm mt-2">تبلیغ جدید</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($advertisements->hasPages())
        <div class="admin-pagination">{{ $advertisements->links() }}</div>
    @endif
</div>
@endsection

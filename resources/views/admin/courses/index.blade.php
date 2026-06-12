@extends('admin.layout')
@section('title', 'دوره‌ها و کلاس‌ها')
@section('header', 'دوره‌ها و کلاس‌ها')
@push('head')
<style>
.course-card{border-radius:1rem;background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(15,23,42,.05);margin-bottom:1rem;overflow:hidden;transition:all .2s}
.course-card:hover{box-shadow:0 6px 24px rgba(99,102,241,.1);border-color:#c7d2fe;transform:translateY(-1px)}
.course-card-head{display:flex;align-items:flex-start;gap:1rem;padding:1rem 1.25rem}
.course-thumb{width:4rem;height:4rem;border-radius:.75rem;background:linear-gradient(135deg,#6366f1,#4f46e5);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden}
.course-thumb img{width:100%;height:100%;object-fit:cover}
.course-title{font-size:.95rem;font-weight:700;color:#1e293b}
.course-meta{display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.5rem}
.course-chip{display:inline-flex;align-items:center;gap:.2rem;padding:.18rem .55rem;border-radius:9999px;font-size:.68rem;font-weight:600}
.course-chip--green{background:#dcfce7;color:#166534}
.course-chip--slate{background:#f1f5f9;color:#475569}
.course-chip--amber{background:#fef9c3;color:#92400e}
.course-chip--violet{background:#ede9fe;color:#6d28d9}
.course-chip--sky{background:#e0f2fe;color:#075985}
.course-price{font-size:1.1rem;font-weight:800;color:#6366f1;white-space:nowrap}
.course-free{font-size:.85rem;font-weight:700;color:#16a34a}
</style>
@endpush
@section('content')

@include('admin.partials.breadcrumb', ['crumbs' => [
    ['label' => 'داشبورد', 'url' => route('admin.dashboard')],
    ['label' => 'دوره‌ها', 'url' => null],
]])

@if(session('success'))
    <div class="alert alert-success mb-4">{{ session('success') }}</div>
@endif

<div class="admin-page">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">دوره‌ها و کلاس‌ها</h1>
            <p class="admin-section-lead" style="margin-top:.3rem">مدیریت دوره‌های آموزشی، کلاس‌ها و ثبت‌نام‌های کاربران.</p>
        </div>
        <div class="admin-page-tools">
            <a href="{{ route('admin.courses.create') }}" class="btn btn-primary">
                <x-core-ui::icon name="plus" class="w-4 h-4" />
                دوره جدید
            </a>
        </div>
    </div>

    {{-- KPIs --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:1rem;margin-bottom:1.5rem">
        <div class="card" style="padding:.875rem 1.25rem">
            <div style="font-size:.72rem;color:#64748b;font-weight:600;margin-bottom:.25rem">کل دوره‌ها</div>
            <div style="font-size:1.6rem;font-weight:800;color:#1e293b">{{ \App\Helpers\NumberHelper::formatFa($stats['total']) }}</div>
        </div>
        <div class="card" style="padding:.875rem 1.25rem">
            <div style="font-size:.72rem;color:#64748b;font-weight:600;margin-bottom:.25rem">دوره فعال</div>
            <div style="font-size:1.6rem;font-weight:800;color:#16a34a">{{ \App\Helpers\NumberHelper::formatFa($stats['active']) }}</div>
        </div>
        <div class="card" style="padding:.875rem 1.25rem">
            <div style="font-size:.72rem;color:#64748b;font-weight:600;margin-bottom:.25rem">دوره رایگان</div>
            <div style="font-size:1.6rem;font-weight:800;color:#0284c7">{{ \App\Helpers\NumberHelper::formatFa($stats['free']) }}</div>
        </div>
        <div class="card" style="padding:.875rem 1.25rem">
            <div style="font-size:.72rem;color:#64748b;font-weight:600;margin-bottom:.25rem">ثبت‌نام‌ها</div>
            <div style="font-size:1.6rem;font-weight:800;color:#7c3aed">{{ \App\Helpers\NumberHelper::formatFa($stats['enrollments']) }}</div>
        </div>
    </div>

    {{-- Courses --}}
    @forelse($courses as $course)
        <div class="course-card">
            <div class="course-card-head">
                <div class="course-thumb">
                    @if($course->image)
                        <img src="{{ $course->image }}" alt="{{ $course->title }}" loading="lazy">
                    @else
                        <x-core-ui::icon name="book-open" class="w-6 h-6 text-white" />
                    @endif
                </div>
                <div style="flex:1;min-width:0">
                    <div class="course-title">{{ $course->title }}</div>
                    @if($course->description)
                        <div style="font-size:.78rem;color:#64748b;margin-top:.15rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ Str::limit(strip_tags($course->description), 80) }}
                        </div>
                    @endif
                    <div class="course-meta">
                        @if($course->is_active)
                            <span class="course-chip course-chip--green">✓ فعال</span>
                        @else
                            <span class="course-chip course-chip--slate">غیرفعال</span>
                        @endif
                        <span class="course-chip course-chip--sky">
                            {{ \App\Helpers\NumberHelper::formatFa($course->classes_count) }} کلاس
                        </span>
                        <span class="course-chip course-chip--violet">
                            {{ \App\Helpers\NumberHelper::formatFa($course->enrollments_count) }} ثبت‌نام
                        </span>
                        @if($course->isFree())
                            <span class="course-chip course-chip--green">رایگان</span>
                        @else
                            <span class="course-chip course-chip--amber">پولی</span>
                        @endif
                    </div>
                </div>
                <div style="text-align:left;flex-shrink:0">
                    @if($course->isFree())
                        <div class="course-free">رایگان</div>
                    @else
                        <div class="course-price">{{ \App\Helpers\NumberHelper::formatFa((int)$course->price) }}</div>
                        <div style="font-size:.68rem;color:#94a3b8">تومان</div>
                    @endif
                </div>
            </div>
            <div style="padding:.625rem 1.25rem;border-top:1px solid #f1f5f9;display:flex;gap:.5rem;background:#fafafa">
                <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-primary btn-sm">ویرایش</a>
                <form method="post" action="{{ route('admin.courses.destroy', $course) }}" style="display:inline"
                      onsubmit="return confirm('دوره «{{ $course->title }}» حذف شود؟');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                </form>
            </div>
        </div>
    @empty
        <div class="admin-empty-state">
            <x-core-ui::icon name="book-open" class="admin-empty-state__icon" />
            <p class="admin-empty-state__title">دوره‌ای ثبت نشده</p>
            <p class="admin-empty-state__text">با دکمهٔ «دوره جدید» اولین دوره آموزشی خود را بسازید.</p>
            <a href="{{ route('admin.courses.create') }}" class="btn btn-primary mt-3">ساخت اولین دوره</a>
        </div>
    @endforelse

    @if($courses->hasPages())
        <div class="admin-pagination">{{ $courses->links() }}</div>
    @endif
</div>
@endsection

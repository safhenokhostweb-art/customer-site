@extends('themes.default.layout')

@section('title', 'دوره‌های آموزشی')
@section('meta_description', 'مشاهده و ثبت‌نام در دوره‌های آموزشی')
@section('canonical', route('courses.index'))

@section('breadcrumb')
    <a href="{{ route('home') }}">خانه</a>
    <span>/</span>
    <span>دوره‌های آموزشی</span>
@endsection

@section('content')
@push('head')
<style>
.courses-page .course-card { display:flex; flex-direction:column; background:#fff; border:1px solid #e5e7eb; border-radius:1rem; overflow:hidden; transition:box-shadow .15s,transform .15s; text-decoration:none; color:inherit; }
.courses-page .course-card:hover { box-shadow:0 8px 24px rgba(0,0,0,.1); transform:translateY(-2px); }
.courses-page .course-card-img { aspect-ratio:16/9; background:#f3f4f6; overflow:hidden; }
.courses-page .course-card-img img { width:100%; height:100%; object-fit:cover; }
.courses-page .course-card-img .no-img { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:3rem; color:#d1d5db; }
.courses-page .course-card-body { flex:1; display:flex; flex-direction:column; padding:1.25rem; }
.courses-page .course-badge { display:inline-flex; align-items:center; gap:.3rem; font-size:.72rem; font-weight:700; padding:.25rem .65rem; border-radius:999px; margin-bottom:.6rem; }
.courses-page .badge-free { background:#d1fae5; color:#065f46; }
.courses-page .badge-paid { background:#fef3c7; color:#92400e; }
.courses-page .badge-enrolled { background:#dbeafe; color:#1e40af; }
.courses-page .filter-tabs { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1.5rem; }
.courses-page .filter-tab { padding:.45rem 1rem; border-radius:.5rem; font-size:.875rem; font-weight:600; text-decoration:none; border:1px solid #e5e7eb; color:#374151; background:#f9fafb; transition:all .15s; }
.courses-page .filter-tab.active { background:#1f2937; color:#fff; border-color:#1f2937; }
.courses-page .course-meta { display:flex; align-items:center; gap:.75rem; font-size:.78rem; color:#6b7280; margin-top:auto; padding-top:.75rem; border-top:1px solid #f3f4f6; }
@media(max-width:768px){ .courses-grid { grid-template-columns:1fr !important; } }
</style>
@endpush

<div class="courses-page">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
        <div>
            <h1 style="font-size:1.75rem; font-weight:800; margin-bottom:.25rem;">دوره‌های آموزشی</h1>
            <p style="color:#6b7280; font-size:.9rem;">دانش خود را با دوره‌های تخصصی ارتقا دهید</p>
        </div>
        @auth
        <a href="{{ route('courses.my-learning') }}" style="display:inline-flex; align-items:center; gap:.5rem; padding:.6rem 1.2rem; background:var(--platform-primary,#4f46e5); color:#fff; border-radius:.6rem; text-decoration:none; font-weight:600; font-size:.875rem;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
            دوره‌های من
        </a>
        @endauth
    </div>

    {{-- فیلتر + جستجو --}}
    <div style="display:flex; flex-wrap:wrap; gap:1rem; align-items:center; margin-bottom:1.5rem;">
        <form method="get" action="{{ route('courses.index') }}" style="flex:1; min-width:200px; display:flex; gap:.5rem;">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="جستجو در دوره‌ها..." style="flex:1; padding:.5rem .85rem; border:1px solid #d1d5db; border-radius:.5rem; font-family:inherit;">
            <button type="submit" style="padding:.5rem 1rem; background:var(--platform-primary,#4f46e5); color:#fff; border:none; border-radius:.5rem; cursor:pointer; font-weight:600;">جستجو</button>
        </form>
        <div class="filter-tabs">
            <a href="{{ route('courses.index', array_filter(['q' => request('q'), 'filter' => 'all'])) }}" class="filter-tab {{ $filter === 'all' ? 'active' : '' }}">همه</a>
            <a href="{{ route('courses.index', array_filter(['q' => request('q'), 'filter' => 'free'])) }}" class="filter-tab {{ $filter === 'free' ? 'active' : '' }}">رایگان</a>
            <a href="{{ route('courses.index', array_filter(['q' => request('q'), 'filter' => 'paid'])) }}" class="filter-tab {{ $filter === 'paid' ? 'active' : '' }}">پولی</a>
        </div>
    </div>

    {{-- گرید دوره‌ها --}}
    @if($courses->isNotEmpty())
    <div class="courses-grid" style="display:grid; gap:1.5rem; grid-template-columns:repeat(3,minmax(0,1fr)); margin-bottom:2rem;">
        @foreach($courses as $course)
        <a href="{{ route('courses.show', $course->slug) }}" class="course-card">
            <div class="course-card-img">
                @if($course->image)
                    <img src="{{ \App\Helpers\ImageHelper::url($course->image) }}" alt="{{ $course->title }}" loading="lazy">
                @else
                    <div class="no-img">🎓</div>
                @endif
            </div>
            <div class="course-card-body">
                @if(in_array($course->id, $enrolledIds))
                    <span class="course-badge badge-enrolled">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        ثبت‌نام شده
                    </span>
                @elseif($course->isFree())
                    <span class="course-badge badge-free">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        رایگان
                    </span>
                @else
                    <span class="course-badge badge-paid">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                        {{ number_format($course->price) }} تومان
                    </span>
                @endif

                <h2 style="font-weight:700; font-size:1rem; margin-bottom:.5rem; line-height:1.4;">{{ $course->title }}</h2>
                @if($course->description)
                <p style="color:#6b7280; font-size:.83rem; line-height:1.5; flex:1; margin-bottom:.5rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">{{ $course->description }}</p>
                @endif

                <div class="course-meta">
                    <span style="display:flex; align-items:center; gap:.3rem;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        {{ $course->enrollments_count }} دانشجو
                    </span>
                </div>
            </div>
        </a>
        @endforeach
    </div>

    {{ $courses->links() }}

    @else
    <div style="text-align:center; padding:4rem 2rem; color:#9ca3af;">
        <div style="font-size:3.5rem; margin-bottom:1rem;">🎓</div>
        <p style="font-size:1.1rem; font-weight:600; margin-bottom:.5rem;">دوره‌ای یافت نشد</p>
        @if(request('q'))
        <p style="font-size:.875rem;">جستجوی «{{ request('q') }}» نتیجه‌ای نداشت.</p>
        @else
        <p style="font-size:.875rem;">به زودی دوره‌های جدید اضافه خواهند شد.</p>
        @endif
    </div>
    @endif
</div>
@endsection

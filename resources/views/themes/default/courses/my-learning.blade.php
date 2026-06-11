@extends('themes.default.layout')

@section('title', 'دوره‌های من')
@section('canonical', route('courses.my-learning'))

@section('breadcrumb')
    <a href="{{ route('home') }}">خانه</a>
    <span>/</span>
    <a href="{{ route('courses.index') }}">دوره‌های آموزشی</a>
    <span>/</span>
    <span>دوره‌های من</span>
@endsection

@section('content')
@push('head')
<style>
.my-learning .course-card { display:flex; gap:1.25rem; background:#fff; border:1px solid #e5e7eb; border-radius:1rem; padding:1.25rem; text-decoration:none; color:inherit; transition:box-shadow .15s; align-items:flex-start; }
.my-learning .course-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.08); }
.my-learning .course-thumb { width:100px; height:70px; border-radius:.5rem; overflow:hidden; flex-shrink:0; background:#f3f4f6; }
.my-learning .course-thumb img { width:100%; height:100%; object-fit:cover; }
.my-learning .no-thumb { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:1.75rem; color:#d1d5db; }
.my-learning .prog-bar { height:6px; background:#e5e7eb; border-radius:999px; overflow:hidden; margin:.5rem 0 .35rem; }
.my-learning .prog-fill { height:100%; border-radius:999px; }
.status-badge { display:inline-flex; align-items:center; gap:.3rem; font-size:.72rem; font-weight:700; padding:.2rem .65rem; border-radius:999px; }
.status-active { background:#dbeafe; color:#1e40af; }
.status-completed { background:#d1fae5; color:#065f46; }
@media(max-width:640px){ .my-learning .course-card { flex-direction:column; } .my-learning .course-thumb { width:100%; height:160px; } }
</style>
@endpush

<div class="my-learning">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:1.75rem;">
        <div>
            <h1 style="font-size:1.6rem; font-weight:800; margin-bottom:.25rem;">دوره‌های من</h1>
            <p style="color:#6b7280; font-size:.875rem;">{{ $enrollments->count() }} دوره ثبت‌نام شده</p>
        </div>
        <a href="{{ route('courses.index') }}" style="display:inline-flex; align-items:center; gap:.5rem; padding:.6rem 1.2rem; border:1px solid #e5e7eb; border-radius:.6rem; text-decoration:none; color:#374151; font-weight:600; font-size:.875rem; background:#fff;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            مشاهده همه دوره‌ها
        </a>
    </div>

    @if($enrollments->isNotEmpty())
    <div style="display:flex; flex-direction:column; gap:1rem;">
        @foreach($enrollments as $en)
        @if($en->course)
        @php
            $prog = $progressMap[$en->course_id] ?? 0;
            $progColor = $prog === 100 ? '#22c55e' : 'var(--platform-primary,#4f46e5)';
        @endphp
        <div class="course-card">
            <a href="{{ route('courses.show', $en->course->slug) }}" class="course-thumb">
                @if($en->course->image)
                    <img src="{{ \App\Helpers\ImageHelper::url($en->course->image) }}" alt="{{ $en->course->title }}">
                @else
                    <div class="no-thumb">🎓</div>
                @endif
            </a>
            <div style="flex:1; min-width:0;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:.5rem; flex-wrap:wrap; margin-bottom:.35rem;">
                    <a href="{{ route('courses.show', $en->course->slug) }}" style="text-decoration:none; color:inherit;">
                        <h2 style="font-size:1rem; font-weight:700; line-height:1.4;">{{ $en->course->title }}</h2>
                    </a>
                    <span class="status-badge {{ $en->status === 'completed' ? 'status-completed' : 'status-active' }}">
                        @if($en->status === 'completed')
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        تکمیل شده
                        @else
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        در حال یادگیری
                        @endif
                    </span>
                </div>

                <div class="prog-bar">
                    <div class="prog-fill" style="width:{{ $prog }}%; background:{{ $progColor }};"></div>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:.75rem; color:#6b7280;">
                    <span>{{ $en->course->classes_count ?? 0 }} جلسه</span>
                    <span style="font-weight:600; color:{{ $progColor }};">{{ $prog }}٪</span>
                </div>

                <div style="margin-top:.75rem; display:flex; gap:.75rem; align-items:center; flex-wrap:wrap;">
                    @if($en->status === 'completed')
                    <a href="{{ route('courses.learn', $en->course->slug) }}" style="display:inline-flex; align-items:center; gap:.4rem; padding:.45rem 1rem; border:1px solid #bbf7d0; background:#f0fdf4; color:#065f46; border-radius:.5rem; text-decoration:none; font-weight:600; font-size:.8rem;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.41"/></svg>
                        مرور مجدد
                    </a>
                    @else
                    <a href="{{ route('courses.learn', $en->course->slug) }}" style="display:inline-flex; align-items:center; gap:.4rem; padding:.45rem 1rem; background:var(--platform-primary,#4f46e5); color:#fff; border-radius:.5rem; text-decoration:none; font-weight:600; font-size:.8rem;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        ادامه یادگیری
                    </a>
                    @endif
                    <span style="font-size:.75rem; color:#9ca3af;">
                        ثبت‌نام: {{ $en->enrolled_at ? \App\Helpers\Jalali::formatFa($en->enrolled_at, 'Y/m/d') : '' }}
                    </span>
                </div>
            </div>
        </div>
        @endif
        @endforeach
    </div>
    @else
    <div style="text-align:center; padding:4rem 2rem; background:#fff; border-radius:1rem; border:1px solid #e5e7eb;">
        <div style="font-size:3.5rem; margin-bottom:1rem;">📚</div>
        <h2 style="font-size:1.25rem; font-weight:700; margin-bottom:.5rem;">هنوز دوره‌ای ندارید</h2>
        <p style="color:#6b7280; margin-bottom:1.5rem; font-size:.875rem;">در دوره‌های آموزشی ثبت‌نام کنید و یادگیری را شروع کنید</p>
        <a href="{{ route('courses.index') }}" style="display:inline-flex; align-items:center; gap:.5rem; padding:.75rem 1.75rem; background:var(--platform-primary,#4f46e5); color:#fff; border-radius:.75rem; text-decoration:none; font-weight:700;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
            مشاهده دوره‌ها
        </a>
    </div>
    @endif
</div>
@endsection

@extends('themes.default.layout')

@section('title', $course->title . ' — دوره آموزشی')
@section('meta_description', Str::limit(strip_tags($course->description ?? ''), 160))
@section('canonical', route('courses.show', $course->slug))

@section('breadcrumb')
    <a href="{{ route('home') }}">خانه</a>
    <span>/</span>
    <a href="{{ route('courses.index') }}">دوره‌های آموزشی</a>
    <span>/</span>
    <span>{{ $course->title }}</span>
@endsection

@section('content')
@push('head')
<style>
.course-show .class-item { display:flex; align-items:center; gap:.75rem; padding:.75rem 1rem; border-radius:.6rem; background:#f9fafb; border:1px solid #e5e7eb; margin-bottom:.5rem; }
.course-show .class-item.completed { background:#f0fdf4; border-color:#bbf7d0; }
.course-show .class-check { width:22px; height:22px; border-radius:50%; border:2px solid #d1d5db; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.course-show .class-item.completed .class-check { background:#22c55e; border-color:#22c55e; color:#fff; }
.course-show .progress-bar { height:8px; background:#e5e7eb; border-radius:999px; overflow:hidden; }
.course-show .progress-fill { height:100%; background:var(--platform-primary,#4f46e5); border-radius:999px; transition:width .4s; }
</style>
@endpush

<div class="course-show" style="max-width:900px; margin:0 auto;">

    {{-- هدر --}}
    <div class="card" style="overflow:hidden; padding:0; margin-bottom:1.5rem;">
        @if($course->image)
        <div style="aspect-ratio:21/9; background:#f3f4f6; overflow:hidden;">
            <img src="{{ \App\Helpers\ImageHelper::url($course->image) }}" alt="{{ $course->title }}" style="width:100%; height:100%; object-fit:cover;">
        </div>
        @endif
        <div style="padding:1.75rem 2rem;">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
                <div style="flex:1;">
                    <h1 style="font-size:1.75rem; font-weight:800; margin-bottom:.5rem;">{{ $course->title }}</h1>
                    <div style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap; color:#6b7280; font-size:.875rem;">
                        <span style="display:flex; align-items:center; gap:.3rem;">
                            <x-core-ui::icon name="users" class="" />
                            {{ $course->enrollments_count }} دانشجو
                        </span>
                        <span style="display:flex; align-items:center; gap:.3rem;">
                            <x-core-ui::icon name="book-open" class="" />
                            {{ $course->classes->count() }} جلسه
                        </span>
                        @if($course->isFree())
                        <span style="background:#d1fae5; color:#065f46; font-weight:700; padding:.2rem .7rem; border-radius:999px; font-size:.75rem;">رایگان</span>
                        @else
                        <span style="background:#fef3c7; color:#92400e; font-weight:700; padding:.2rem .7rem; border-radius:999px; font-size:.75rem;">@numFa($course->price) تومان</span>
                        @endif
                    </div>
                </div>

                {{-- دکمه اقدام --}}
                <div style="min-width:160px;">
                    @auth
                        @if($enrollment)
                            @if($progress === 100)
                            <div style="text-align:center; padding:1rem; background:#f0fdf4; border-radius:.75rem; border:1px solid #bbf7d0;">
                                <div style="font-size:1.5rem; margin-bottom:.25rem;">🏆</div>
                                <div style="font-weight:700; color:#065f46; font-size:.875rem;">دوره تکمیل شد!</div>
                            </div>
                            @else
                            <a href="{{ route('courses.learn', [$course->slug]) }}" style="display:block; text-align:center; padding:.85rem 1.5rem; background:var(--platform-primary,#4f46e5); color:#fff; border-radius:.75rem; text-decoration:none; font-weight:700; font-size:1rem;">
                                ادامه یادگیری
                                <x-core-ui::icon name="chevron-left" class="" style="display:inline; vertical-align:middle; margin-right:.3rem;" />
                            </a>
                            @endif
                        @elseif($course->isFree())
                        <form method="POST" action="{{ route('courses.enroll', $course->slug) }}">
                            @csrf
                            <button type="submit" style="width:100%; padding:.85rem 1.5rem; background:#22c55e; color:#fff; border:none; border-radius:.75rem; font-weight:700; font-size:1rem; cursor:pointer;">
                                ثبت‌نام رایگان
                            </button>
                        </form>
                        @else
                        <div style="text-align:center; padding:1rem; background:#fef3c7; border-radius:.75rem; border:1px solid #fde68a;">
                            <div style="font-weight:700; color:#92400e; font-size:.875rem; margin-bottom:.5rem;">@numFa($course->price) تومان</div>
                            <a href="{{ route('pricing.index') }}" style="font-size:.78rem; color:#92400e; text-decoration:underline;">خرید اشتراک</a>
                        </div>
                        @endif
                    @else
                        <a href="{{ route('login', ['redirect' => request()->fullUrl()]) }}" style="display:block; text-align:center; padding:.85rem 1.5rem; background:var(--platform-primary,#4f46e5); color:#fff; border-radius:.75rem; text-decoration:none; font-weight:700; font-size:1rem;">
                            ورود برای ثبت‌نام
                        </a>
                    @endauth
                </div>
            </div>

            {{-- نوار پیشرفت --}}
            @if($enrollment && $progress > 0)
            <div style="margin-top:1.25rem;">
                <div style="display:flex; justify-content:space-between; font-size:.8rem; margin-bottom:.4rem;">
                    <span style="color:#6b7280;">پیشرفت شما</span>
                    <span style="font-weight:700; color:var(--platform-primary,#4f46e5);">{{ $progress }}٪</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:{{ $progress }}%;"></div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div style="display:grid; gap:1.5rem; grid-template-columns:1fr 360px;" id="course-layout">

        {{-- توضیحات --}}
        <div>
            @if($course->description)
            <div class="card" style="margin-bottom:1.5rem;">
                <h2 style="font-size:1.1rem; font-weight:700; margin-bottom:1rem; display:flex; align-items:center; gap:.5rem;">
                    <x-core-ui::icon name="info-circle" class="w-4 h-4" />
                    درباره این دوره
                </h2>
                <div style="color:#374151; line-height:1.8; white-space:pre-line;">{{ $course->description }}</div>
            </div>
            @endif
        </div>

        {{-- فهرست جلسات --}}
        <div>
            <div class="card">
                <h2 style="font-size:1.1rem; font-weight:700; margin-bottom:1rem; display:flex; align-items:center; gap:.5rem;">
                    <x-core-ui::icon name="book-open" class="w-4 h-4" />
                    محتوای دوره
                </h2>
                @forelse($course->classes as $class)
                <div class="class-item {{ in_array($class->id, $completedClassIds) ? 'completed' : '' }}">
                    <div class="class-check">
                        @if(in_array($class->id, $completedClassIds))
                        <x-core-ui::icon name="check" class="w-3 h-3" />
                        @else
                        <span style="font-size:.7rem; color:#9ca3af;">{{ $class->order }}</span>
                        @endif
                    </div>
                    <div style="flex:1; min-width:0;">
                        @if($enrollment)
                        <a href="{{ route('courses.learn', [$course->slug, $class->order]) }}" style="text-decoration:none; color:#1f2937; font-size:.875rem; font-weight:600; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $class->title }}</a>
                        @else
                        <span style="color:#6b7280; font-size:.875rem; font-weight:600;">{{ $class->title }}</span>
                        @endif
                    </div>
                    @if(!$enrollment)
                    <x-core-ui::icon name="lock" class="w-3.5 h-3.5" style="color:#9ca3af" />
                    @endif
                </div>
                @empty
                <p style="color:#9ca3af; font-size:.875rem; text-align:center; padding:1rem;">جلسه‌ای هنوز اضافه نشده است.</p>
                @endforelse
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
// responsive layout
function adjustLayout(){
    const layout = document.getElementById('course-layout');
    if(layout) layout.style.gridTemplateColumns = window.innerWidth < 768 ? '1fr' : '1fr 360px';
}
window.addEventListener('resize', adjustLayout);
adjustLayout();
</script>
@endpush
@endsection

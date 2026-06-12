@extends('themes.default.layout')

@section('title', $currentClass->title . ' — ' . $course->title)
@section('canonical', route('courses.learn', [$course->slug, $currentClass->order]))

@section('breadcrumb')
    <a href="{{ route('home') }}">خانه</a>
    <span>/</span>
    <a href="{{ route('courses.index') }}">دوره‌ها</a>
    <span>/</span>
    <a href="{{ route('courses.show', $course->slug) }}">{{ $course->title }}</a>
    <span>/</span>
    <span>{{ $currentClass->title }}</span>
@endsection

@section('content')
@push('head')
<style>
.learn-page { display:grid; grid-template-columns:280px 1fr; gap:1.5rem; align-items:start; }
.learn-sidebar { position:sticky; top:1rem; }
.learn-sidebar .class-nav-item { display:flex; align-items:center; gap:.65rem; padding:.65rem .9rem; border-radius:.6rem; text-decoration:none; color:#374151; font-size:.875rem; transition:background .1s; margin-bottom:.3rem; }
.learn-sidebar .class-nav-item:hover { background:#f3f4f6; }
.learn-sidebar .class-nav-item.active { background:var(--platform-primary-light,#ede9fe); color:var(--platform-primary,#4f46e5); font-weight:700; }
.learn-sidebar .class-nav-item.done .check-icon { background:#22c55e; border-color:#22c55e; color:#fff; }
.check-icon { width:20px; height:20px; border-radius:50%; border:2px solid #d1d5db; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:.65rem; }
.learn-progress { height:6px; background:#e5e7eb; border-radius:999px; overflow:hidden; margin:1rem 0; }
.learn-progress-fill { height:100%; background:var(--platform-primary,#4f46e5); border-radius:999px; transition:width .5s ease; }
.learn-content-body { line-height:1.9; color:#374151; font-size:.95rem; white-space:pre-line; }
@media(max-width:900px){ .learn-page { grid-template-columns:1fr; } .learn-sidebar { position:static; } }
</style>
@endpush

<div class="learn-page">

    {{-- سایدبار جلسات --}}
    <aside class="learn-sidebar card" style="padding:1.25rem;">
        <div style="margin-bottom:.75rem;">
            <a href="{{ route('courses.show', $course->slug) }}" style="font-size:.78rem; color:#6b7280; text-decoration:none; display:flex; align-items:center; gap:.3rem;">
                <x-core-ui::icon name="chevron-right" class="" />
                بازگشت به دوره
            </a>
            <h3 style="font-weight:700; font-size:.95rem; margin-top:.5rem; margin-bottom:.25rem;">{{ $course->title }}</h3>
        </div>

        <div class="learn-progress">
            <div class="learn-progress-fill" id="sidebar-progress" style="width:{{ $progress }}%;"></div>
        </div>
        <div style="font-size:.75rem; color:#6b7280; text-align:left; margin-bottom:1rem;"><span id="sidebar-pct">{{ $progress }}</span>٪ تکمیل شده</div>

        @foreach($classes as $cls)
        <a href="{{ route('courses.learn', [$course->slug, $cls->order]) }}"
           class="class-nav-item {{ $cls->id === $currentClass->id ? 'active' : '' }} {{ in_array($cls->id, $completedClassIds) ? 'done' : '' }}"
           id="nav-{{ $cls->id }}">
            <div class="check-icon" id="check-{{ $cls->id }}">
                @if(in_array($cls->id, $completedClassIds))
                <x-core-ui::icon name="check" class="" />
                @else
                <span>{{ $cls->order }}</span>
                @endif
            </div>
            <span style="flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $cls->title }}</span>
        </a>
        @endforeach
    </aside>

    {{-- محتوای اصلی --}}
    <main>
        <div class="card" style="margin-bottom:1rem;">
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem; margin-bottom:1.25rem;">
                <div>
                    <div style="font-size:.78rem; color:#6b7280; margin-bottom:.25rem;">جلسه {{ $currentClass->order }} از {{ $classes->count() }}</div>
                    <h1 style="font-size:1.35rem; font-weight:800;">{{ $currentClass->title }}</h1>
                </div>

                {{-- دکمه تکمیل جلسه --}}
                @if(!in_array($currentClass->id, $completedClassIds))
                <button id="complete-btn"
                    onclick="markComplete({{ $currentClass->id }})"
                    style="display:flex; align-items:center; gap:.5rem; padding:.65rem 1.25rem; background:#22c55e; color:#fff; border:none; border-radius:.65rem; font-weight:700; font-size:.875rem; cursor:pointer;">
                    <x-core-ui::icon name="check" class="" />
                    تکمیل این جلسه
                </button>
                @else
                <div id="complete-btn" style="display:flex; align-items:center; gap:.5rem; padding:.65rem 1.25rem; background:#d1fae5; color:#065f46; border-radius:.65rem; font-weight:700; font-size:.875rem;">
                    <x-core-ui::icon name="check" class="" />
                    تکمیل شد
                </div>
                @endif
            </div>

            <hr style="border:none; border-top:1px solid #f3f4f6; margin-bottom:1.25rem;">

            <div class="learn-content-body">
                {!! nl2br(e($currentClass->content ?? 'محتوای این جلسه هنوز بارگذاری نشده است.')) !!}
            </div>
        </div>

        {{-- ناوبری بین جلسات --}}
        <div style="display:flex; justify-content:space-between; gap:1rem;">
            @if($prevClass)
            <a href="{{ route('courses.learn', [$course->slug, $prevClass->order]) }}" style="display:inline-flex; align-items:center; gap:.5rem; padding:.65rem 1.25rem; border:1px solid #e5e7eb; border-radius:.65rem; text-decoration:none; color:#374151; font-weight:600; font-size:.875rem; background:#fff;">
                <x-core-ui::icon name="chevron-right" class="" />
                جلسه قبل
            </a>
            @else
            <div></div>
            @endif

            @if($nextClass)
            <a href="{{ route('courses.learn', [$course->slug, $nextClass->order]) }}" style="display:inline-flex; align-items:center; gap:.5rem; padding:.65rem 1.25rem; background:var(--platform-primary,#4f46e5); color:#fff; border-radius:.65rem; text-decoration:none; font-weight:600; font-size:.875rem;">
                جلسه بعد
                <x-core-ui::icon name="chevron-left" class="" />
            </a>
            @else
            <a href="{{ route('courses.show', $course->slug) }}" style="display:inline-flex; align-items:center; gap:.5rem; padding:.65rem 1.25rem; background:#7c3aed; color:#fff; border-radius:.65rem; text-decoration:none; font-weight:600; font-size:.875rem;">
                <x-core-ui::icon name="check-circle" class="" />
                پایان دوره
            </a>
            @endif
        </div>
    </main>
</div>

@push('scripts')
<script>
const totalClasses = {{ $classes->count() }};

function markComplete(classId) {
    const btn = document.getElementById('complete-btn');
    if (!btn) return;
    btn.disabled = true;
    btn.style.opacity = '0.7';

    fetch('{{ route('courses.class.complete', [$course->slug, ':id']) }}'.replace(':id', classId), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.completed) {
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> تکمیل شد';
            btn.style.background = '#d1fae5';
            btn.style.color = '#065f46';
            btn.style.cursor = 'default';

            // update sidebar check
            const check = document.getElementById('check-' + classId);
            if (check) {
                check.style.background = '#22c55e';
                check.style.borderColor = '#22c55e';
                check.style.color = '#fff';
                check.innerHTML = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>';
            }

            // update progress
            const pct = data.progress;
            const fill = document.getElementById('sidebar-progress');
            const pctEl = document.getElementById('sidebar-pct');
            if (fill) fill.style.width = pct + '%';
            if (pctEl) pctEl.textContent = pct;

            if (pct === 100) {
                setTimeout(() => {
                    if (confirm('🎉 تبریک! دوره را با موفقیت تکمیل کردید. بازگشت به صفحه دوره؟')) {
                        window.location.href = '{{ route('courses.show', $course->slug) }}';
                    }
                }, 500);
            } else {
                @if($nextClass)
                setTimeout(() => {
                    window.location.href = '{{ route('courses.learn', [$course->slug, $nextClass->order]) }}';
                }, 800);
                @endif
            }
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.style.opacity = '1';
    });
}
</script>
@endpush
@endsection

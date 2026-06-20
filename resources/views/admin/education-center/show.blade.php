@extends('admin.layout')

@section('title', ($article['title'] ?? 'مستند') . ' — مرکز آموزش')

@push('head')
<style>
/* Article body typography */
.edu-article-body h2 { font-size:1.1rem; font-weight:700; color:#1e293b; margin:1.5rem 0 .75rem; padding-bottom:.4rem; border-bottom:2px solid #e2e8f0; }
.edu-article-body h3 { font-size:.95rem; font-weight:600; color:#334155; margin:1.25rem 0 .5rem; }
.edu-article-body p { color:#374151; line-height:1.8; margin-bottom:.75rem; }
.edu-article-body ul, .edu-article-body ol { padding-right:1.5rem; margin-bottom:.75rem; }
.edu-article-body li { color:#374151; line-height:1.7; margin-bottom:.3rem; }
.edu-article-body table { width:100%; border-collapse:collapse; margin:1rem 0; font-size:.875rem; }
.edu-article-body th { background:#f8fafc; font-weight:600; color:#374151; border:1px solid #e2e8f0; padding:.5rem .75rem; text-align:right; }
.edu-article-body td { border:1px solid #e2e8f0; padding:.5rem .75rem; color:#4b5563; }
.edu-article-body code { background:#f1f5f9; border-radius:3px; padding:.1rem .35rem; font-size:.8rem; color:#0f172a; }
.edu-article-body dl dt { font-weight:600; color:#1e293b; margin-top:1rem; }
.edu-article-body dl dd { color:#4b5563; padding-right:1rem; margin-bottom:.5rem; line-height:1.7; }
.edu-article-body .edu-meta-bar { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1.5rem; padding:.75rem; background:#f8fafc; border-radius:.5rem; border:1px solid #e2e8f0; }
.edu-article-body .edu-badge { display:inline-flex; align-items:center; font-size:.7rem; border-radius:9999px; padding:.15rem .65rem; }
.edu-article-body .edu-badge--module { background:#dbeafe; color:#1d4ed8; }
.edu-article-body .edu-badge--route { background:#dcfce7; color:#166534; }
.edu-article-body .edu-badge--updated { background:#fef3c7; color:#92400e; font-weight:600; }
.edu-article-body .edu-changelog-table th, .edu-article-body .edu-changelog-table td { padding:.4rem .75rem; }
.edu-article-body .edu-field-table { font-size:.85rem; }
.edu-toc { position:sticky; top:1.5rem; }
.edu-toc a { display:block; padding:.35rem .5rem; border-radius:.375rem; font-size:.8rem; color:#4b5563; text-decoration:none; transition:background .1s,color .1s; }
.edu-toc a:hover { background:#f1f5f9; color:#1d4ed8; }
</style>
@endpush

@section('content')
<div dir="rtl" class="p-6 max-w-7xl mx-auto">

    {{-- Breadcrumb --}}
    <nav class="text-sm text-gray-500 mb-5 flex items-center gap-1">
        <a href="{{ route('admin.education-center.index') }}" class="hover:text-blue-600">مرکز آموزش</a>
        <span>/</span>
        <span class="text-gray-800">{{ $article['title'] ?? '' }}</span>
    </nav>

    <div class="flex gap-6">

        {{-- ── Main content ── --}}
        <div class="flex-1 min-w-0">
            <div class="bg-white rounded-xl border border-gray-200 p-6 md:p-8">

                {{-- Title + metadata --}}
                <div class="mb-6">
                    <div class="flex items-start justify-between flex-wrap gap-3 mb-3">
                        <span class="inline-flex items-center text-xs bg-blue-50 text-blue-600 border border-blue-200 rounded-full px-3 py-1">
                            {{ $article['module_slug'] ?? '' }}
                        </span>
                        {{-- Rule 10: Last Updated badge (prominent) --}}
                        <span class="text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 rounded-full px-3 py-1">
                            {{ $article['last_updated_label'] ?? ('آخرین بروزرسانی: ' . (isset($article['updated_at']) ? \App\Helpers\Jalali::formatFa(\Carbon\Carbon::parse($article['updated_at']), 'Y/m/d') : '—')) }}
                        </span>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900 leading-snug">{{ $article['title'] ?? '' }}</h1>
                    @if(!empty($article['excerpt']))
                    <p class="text-sm text-gray-600 mt-2 leading-relaxed">{{ strip_tags($article['excerpt']) }}</p>
                    @endif
                </div>

                <hr class="border-gray-100 mb-6">

                {{-- Article body — Rule 5 rich content --}}
                <div class="edu-article-body prose-sm max-w-none">
                    {!! $article['body'] ?? '' !!}
                </div>

                {{-- Back link --}}
                <div class="mt-8 pt-6 border-t border-gray-100">
                    <a href="{{ route('admin.education-center.index') }}"
                       class="inline-flex items-center gap-2 text-sm text-blue-600 hover:underline">
                        <x-core-ui::icon name="arrow-right" class="w-4 h-4 rotate-180" />
                        بازگشت به مرکز آموزش
                    </a>
                </div>

            </div>
        </div>

        {{-- ── Sidebar TOC ── --}}
        <aside class="hidden xl:block w-52 shrink-0">
            <div class="edu-toc bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-semibold text-gray-500 mb-3 uppercase tracking-wide">فهرست مطالب</p>
                <nav>
                    <a href="#overview">۱. بررسی اجمالی</a>
                    <a href="#use-cases">۲. کاربردهای کسب‌وکار</a>
                    <a href="#when-to-use">۳. چه زمانی استفاده کنیم</a>
                    <a href="#config-guide">۴. راهنمای پیکربندی</a>
                    <a href="#field-reference">۵. مرجع فیلدها</a>
                    <a href="#best-practices">۶. بهترین شیوه‌ها</a>
                    <a href="#mistakes">۷. اشتباهات رایج</a>
                    <a href="#security">۸. نکات امنیتی</a>
                    <a href="#faq">۹. سوالات متداول</a>
                    <a href="#related">۱۰. ویژگی‌های مرتبط</a>
                    <a href="#troubleshooting">۱۱. رفع اشکال</a>
                    <a href="#changelog">۱۲. تاریخچه تغییرات</a>
                </nav>

                @if(!empty($article['documentation_version']))
                <div class="mt-4 pt-3 border-t border-gray-100">
                    <p class="text-xs text-gray-400">نسخه مستند: v{{ $article['documentation_version'] }}</p>
                </div>
                @endif
            </div>
        </aside>

    </div>
</div>
@endsection

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پیش‌نمایش قالب — پیش‌نویس</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --color-primary:    {{ $globalStyles['primary_color']    ?? '#4f46e5' }};
            --color-secondary:  {{ $globalStyles['secondary_color']  ?? '#7c3aed' }};
            --color-background: {{ $globalStyles['background_color'] ?? '#f9fafb' }};
            --font-family:      '{{ $globalStyles['font_family'] ?? 'Vazirmatn' }}', Vazirmatn, sans-serif;
        }
        * { font-family: var(--font-family); }
        body { background-color: var(--color-background); }
        .btn-primary { background-color: var(--color-primary); color: #fff; }
        .text-primary { color: var(--color-primary); }
        .bg-primary   { background-color: var(--color-primary); }
        .border-primary { border-color: var(--color-primary); }
    </style>
</head>
<body>

    {{-- Preview Banner --}}
    <div class="fixed top-0 inset-x-0 z-50 bg-amber-500 text-white text-center py-2 text-sm font-semibold flex items-center justify-center gap-3 shadow-lg">
        <span>📝 پیش‌نمایش پیش‌نویس — این نسخه هنوز منتشر نشده</span>
        <a href="{{ url('/admin/theme-design/canvas/edit') }}" class="underline hover:no-underline text-amber-900 font-bold">بازگشت به ویرایشگر</a>
    </div>

    <div class="pt-10">
        {{-- Header blocks --}}
        @if($renderedHeader)
            <header>{!! $renderedHeader !!}</header>
        @endif

        {{-- Page blocks --}}
        <main>
            {!! $renderedBlocks !!}
        </main>

        {{-- Footer blocks --}}
        @if($renderedFooter)
            <footer>{!! $renderedFooter !!}</footer>
        @endif
    </div>

</body>
</html>

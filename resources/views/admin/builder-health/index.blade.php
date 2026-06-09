@extends('admin.layout')

@section('title', 'سلامت المان‌های سازنده')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">سلامت المان‌های سازنده</h1>
    <p class="text-gray-600">وضعیت تست خودکار المان‌های صفحه‌ساز. در صورت شکست المان‌های حیاتی، انتشار ممکن است محدود شود.</p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
            <div class="text-sm text-gray-500">کل المان‌ها</div>
            <div class="text-2xl font-bold text-gray-900">{{ $total }}</div>
        </div>
        <div class="bg-white rounded-lg border border-green-200 p-4 shadow-sm">
            <div class="text-sm text-gray-500">موفق</div>
            <div class="text-2xl font-bold text-green-700">{{ $passedCount }}</div>
        </div>
        <div class="bg-white rounded-lg border border-red-200 p-4 shadow-sm">
            <div class="text-sm text-gray-500">ناموفق</div>
            <div class="text-2xl font-bold text-red-700">{{ $failedCount }}</div>
        </div>
    </div>

    @if($failedCount > 0)
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <h2 class="font-semibold text-red-800 mb-2">المان‌های ناموفق</h2>
            <ul class="list-disc list-inside space-y-1 text-red-700">
                @foreach($failed as $type => $reasons)
                    <li><strong>{{ $type }}</strong>: {{ is_array($reasons) ? implode('؛ ', $reasons) : $reasons }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">نوع</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">نام</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">وضعیت</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($allElements as $type => $meta)
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $type }}</td>
                        <td class="px-4 py-2 text-sm text-gray-600">{{ $meta['name'] ?? '' }}</td>
                        <td class="px-4 py-2">
                            @if(in_array($type, $passed))
                                <span class="text-green-600 text-sm">✓ موفق</span>
                            @elseif(isset($failed[$type]))
                                <span class="text-red-600 text-sm">✗ ناموفق</span>
                            @else
                                <span class="text-gray-400 text-sm">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="text-sm text-gray-500">برای اجرای مجدد تست‌ها این صفحه را رفرش کنید. دستور آرتیزان: <code class="bg-gray-100 px-1 rounded">php artisan builder:test-elements</code></p>
</div>
@endsection

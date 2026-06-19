@php
    use App\Models\Setting;
    use App\Models\SiteLifecycleInvoice;

    $status        = Setting::get('site_lifecycle_status', 'active');
    $isNormal      = in_array($status, ['active', 'grace_period']);
    $pendingInvoice = null;

    // Try to get pending lifecycle invoice if table exists
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('site_lifecycle_invoices')) {
            $pendingInvoice = \Illuminate\Support\Facades\DB::table('site_lifecycle_invoices')
                ->where('status', 'pending')
                ->latest()
                ->first();
        }
    } catch (\Throwable) {}

    $statusMeta = match($status) {
        'active'       => ['label' => 'فعال',         'color' => 'green',  'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',   'bg' => 'bg-green-50 border-green-200',  'text' => 'text-green-800'],
        'grace_period' => ['label' => 'دوره اعتبار', 'color' => 'yellow', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',       'bg' => 'bg-yellow-50 border-yellow-200', 'text' => 'text-yellow-800'],
        'suspended'    => ['label' => 'معلق',         'color' => 'red',    'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636', 'bg' => 'bg-red-50 border-red-200', 'text' => 'text-red-800'],
        'archived'     => ['label' => 'آرشیو',        'color' => 'gray',   'icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',          'bg' => 'bg-gray-100 border-gray-300',  'text' => 'text-gray-700'],
        default        => ['label' => $status,         'color' => 'gray',   'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'bg' => 'bg-gray-50 border-gray-200', 'text' => 'text-gray-700'],
    };
@endphp

@if(! $isNormal)
<div class="mb-6 border rounded-xl overflow-hidden {{ $statusMeta['bg'] }}" dir="rtl">
    <div class="px-5 py-4 flex items-start gap-4">
        <div class="mt-0.5 shrink-0">
            <svg class="w-8 h-8 {{ $statusMeta['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $statusMeta['icon'] }}"/>
            </svg>
        </div>
        <div class="flex-1">
            <div class="flex items-center gap-2">
                <span class="font-bold text-base {{ $statusMeta['text'] }}">وضعیت سایت: {{ $statusMeta['label'] }}</span>
            </div>

            @if($status === 'grace_period')
            <p class="text-sm text-yellow-700 mt-1">
                اشتراک سایت منقضی شده است. سایت همچنان فعال است اما لطفاً هرچه زودتر اشتراک خود را تمدید کنید تا سایت معلق نشود.
            </p>
            @elseif($status === 'suspended')
            <p class="text-sm text-red-700 mt-1">
                سایت به دلیل عدم تمدید اشتراک برای بازدیدکنندگان عمومی غیرفعال شده است. برای فعال‌سازی مجدد، با پشتیبانی تماس بگیرید و فاکتور زیر را پرداخت کنید.
            </p>
            @elseif($status === 'archived')
            <p class="text-sm text-gray-700 mt-1">
                سایت آرشیو شده است. اطلاعات نگهداری می‌شود اما سرویس غیرفعال است. برای بازگردانی با پشتیبانی تماس بگیرید.
            </p>
            @endif

            @if($pendingInvoice)
            <div class="mt-3 p-3 bg-white bg-opacity-70 border border-current border-opacity-20 rounded-lg text-sm">
                <div class="font-semibold {{ $statusMeta['text'] }} mb-1">فاکتور در انتظار پرداخت</div>
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div class="text-gray-600">
                        <span>شماره: {{ $pendingInvoice->invoice_number }}</span>
                        <span class="mx-2">|</span>
                        <span class="font-bold text-lg text-gray-900">{{ number_format($pendingInvoice->amount) }} ریال</span>
                    </div>
                    <a href="/admin/subscription" class="px-4 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-semibold hover:bg-indigo-700 transition">
                        پرداخت و تمدید
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endif

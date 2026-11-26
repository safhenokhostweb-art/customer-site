@extends('admin.layout')

@section('title', 'مارکت‌پلیس دارایی‌های Canvas')

@php
    use SaaSPlatform\ThemeRuntime\Access\AssetRef;
    $buttons = $catalog['buttons'] ?? [];
    $icons = $catalog['icons'] ?? [];
    $headers = $catalog['headers'] ?? [];
    $motions = $catalog['motions'] ?? [];
@endphp

@section('content')
<div class="space-y-6" dir="rtl" x-data="{
    activeTab: '{{ $activeTab ?? 'buttons' }}',
    filterType: '{{ $typeFilter ?? 'all' }}',
    filterStatus: '{{ $statusFilter ?? 'all' }}',
    showChangelogModal: false,
    selectedChangelog: null,
    openChangelog(item) {
        this.selectedChangelog = item;
        this.showChangelogModal = true;
    }
}">

    {{-- Top Header --}}
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">مارکت‌پلیس دارایی‌های Canvas</h1>
            <p class="text-sm text-gray-500 mt-1">کتابخانه‌های دکمه، آیکن‌های SVG اختصاصی، قالب‌های آماده هدر و موشن گرافیک‌ها را بررسی و به ویرایشگر سایت خود اضافه کنید.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.theme-design.canvas') }}" target="_blank"
               class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2.5 rounded-xl transition shadow-sm">
                <x-core-ui::icon name="palette" class="w-4 h-4" />
                <span>ورود به ویرایشگر Canvas</span>
            </a>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(!empty($error))
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
            <x-core-ui::icon name="alert-triangle" class="w-5 h-5 text-amber-600 shrink-0" />
            <span>{{ $error }}</span>
        </div>
    @endif
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
            <x-core-ui::icon name="check-circle" class="w-5 h-5 text-emerald-600 shrink-0" />
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
            <x-core-ui::icon name="x-circle" class="w-5 h-5 text-rose-600 shrink-0" />
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Navigation Tabs & Filter Bar --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-3 flex flex-wrap gap-3 items-center justify-between">
        <div class="flex flex-wrap gap-1.5">
            <a href="{{ route('admin.canvas-marketplace.index', ['tab' => 'buttons', 'type' => $typeFilter, 'status' => $statusFilter]) }}"
               class="px-4 py-2 rounded-xl text-sm font-medium transition flex items-center gap-2 {{ $activeTab === 'buttons' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                <span>کتابخانه دکمه‌ها</span>
                <span class="text-xs px-2 py-0.5 rounded-full {{ $activeTab === 'buttons' ? 'bg-indigo-700 text-indigo-100' : 'bg-gray-100 text-gray-600' }}">{{ count($buttons) }}</span>
            </a>

            <a href="{{ route('admin.canvas-marketplace.index', ['tab' => 'icons', 'type' => $typeFilter, 'status' => $statusFilter]) }}"
               class="px-4 py-2 rounded-xl text-sm font-medium transition flex items-center gap-2 {{ $activeTab === 'icons' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                <span>آیکن‌های اختصاصی</span>
                <span class="text-xs px-2 py-0.5 rounded-full {{ $activeTab === 'icons' ? 'bg-indigo-700 text-indigo-100' : 'bg-gray-100 text-gray-600' }}">{{ count($icons) }}</span>
            </a>

            <a href="{{ route('admin.canvas-marketplace.index', ['tab' => 'headers', 'type' => $typeFilter, 'status' => $statusFilter]) }}"
               class="px-4 py-2 rounded-xl text-sm font-medium transition flex items-center gap-2 {{ $activeTab === 'headers' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                <span>قالب‌های آماده هدر</span>
                <span class="text-xs px-2 py-0.5 rounded-full {{ $activeTab === 'headers' ? 'bg-indigo-700 text-indigo-100' : 'bg-gray-100 text-gray-600' }}">{{ count($headers) }}</span>
            </a>

            <a href="{{ route('admin.canvas-marketplace.index', ['tab' => 'motions', 'type' => $typeFilter, 'status' => $statusFilter]) }}"
               class="px-4 py-2 rounded-xl text-sm font-medium transition flex items-center gap-2 {{ $activeTab === 'motions' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                <span>موشن گرافیک‌ها</span>
                <span class="text-xs px-2 py-0.5 rounded-full {{ $activeTab === 'motions' ? 'bg-indigo-700 text-indigo-100' : 'bg-gray-100 text-gray-600' }}">{{ count($motions) }}</span>
            </a>
        </div>

        {{-- Filters (Free/Paid and Ownership) --}}
        <div class="flex items-center gap-2 flex-wrap">
            <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-xl text-xs">
                <a href="{{ route('admin.canvas-marketplace.index', ['tab' => $activeTab, 'type' => 'all', 'status' => $statusFilter]) }}"
                   class="px-2.5 py-1 rounded-lg transition {{ $typeFilter === 'all' ? 'bg-white text-gray-900 font-bold shadow-2xs' : 'text-gray-500' }}">همه</a>
                <a href="{{ route('admin.canvas-marketplace.index', ['tab' => $activeTab, 'type' => 'free', 'status' => $statusFilter]) }}"
                   class="px-2.5 py-1 rounded-lg transition {{ $typeFilter === 'free' ? 'bg-emerald-600 text-white font-bold' : 'text-gray-500' }}">رایگان</a>
                <a href="{{ route('admin.canvas-marketplace.index', ['tab' => $activeTab, 'type' => 'paid', 'status' => $statusFilter]) }}"
                   class="px-2.5 py-1 rounded-lg transition {{ $typeFilter === 'paid' ? 'bg-purple-600 text-white font-bold' : 'text-gray-500' }}">پولی</a>
            </div>

            <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-xl text-xs">
                <a href="{{ route('admin.canvas-marketplace.index', ['tab' => $activeTab, 'type' => $typeFilter, 'status' => 'all']) }}"
                   class="px-2.5 py-1 rounded-lg transition {{ $statusFilter === 'all' ? 'bg-white text-gray-900 font-bold shadow-2xs' : 'text-gray-500' }}">همه وضعیت‌ها</a>
                <a href="{{ route('admin.canvas-marketplace.index', ['tab' => $activeTab, 'type' => $typeFilter, 'status' => 'owned']) }}"
                   class="px-2.5 py-1 rounded-lg transition {{ $statusFilter === 'owned' ? 'bg-indigo-600 text-white font-bold' : 'text-gray-500' }}">فعال روی سایت</a>
                <a href="{{ route('admin.canvas-marketplace.index', ['tab' => $activeTab, 'type' => $typeFilter, 'status' => 'unowned']) }}"
                   class="px-2.5 py-1 rounded-lg transition {{ $statusFilter === 'unowned' ? 'bg-indigo-600 text-white font-bold' : 'text-gray-500' }}">قابل دریافت</a>
            </div>
        </div>
    </div>

    {{-- ===================================================================== --}}
    {{-- TAB 1: BUTTONS --}}
    {{-- ===================================================================== --}}
    @if($activeTab === 'buttons')
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($buttons as $item)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col justify-between hover:border-indigo-200 transition">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-xs px-2 py-0.5 rounded-md bg-gray-100 text-gray-600 font-medium">{{ $item['category_label'] ?? 'عمومی' }}</span>
                                <h3 class="font-bold text-gray-900 text-base mt-1.5">{{ $item['title'] }}</h3>
                                <div class="text-xs text-gray-400 font-mono mt-0.5">{{ $item['slug'] }}</div>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                @if(($item['access_type'] ?? '') === 'free')
                                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-medium">رایگان</span>
                                @else
                                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200 font-medium">{{ number_format($item['price'] ?? 0) }} تومان</span>
                                @endif
                                <button type="button" @click="openChangelog({{ json_encode($item) }})"
                                        class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-mono font-bold transition cursor-pointer"
                                        title="مشاهده تاریخچه نسخه‌ها و تغییرات">
                                    <span dir="ltr">v{{ $item['current_version'] ?? '1.0.0' }}</span>
                                    <span class="text-[10px]">📋</span>
                                </button>
                            </div>
                        </div>

                        @if(!empty($item['description']))
                            <p class="text-xs text-gray-500 line-clamp-2 leading-5">{{ $item['description'] }}</p>
                        @endif

                        {{-- Presets Preview --}}
                        <div class="bg-gray-50 rounded-xl p-3 space-y-2 border border-gray-100">
                            <div class="text-xs text-gray-500 font-medium">پریست‌های آماده ({{ $item['presets_count'] ?? count($item['presets'] ?? []) }}):</div>
                            <div class="flex flex-wrap gap-1.5">
                                @forelse($item['presets'] ?? [] as $preset)
                                    <span class="inline-flex items-center text-xs px-2 py-1 rounded-lg bg-white border border-gray-200 text-gray-700 shadow-2xs">
                                        {{ $preset['title'] ?? $preset['slug'] }}
                                    </span>
                                @empty
                                    <span class="text-xs text-gray-400 italic">بدون پیش‌نمایش</span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- Card Footer / Action Button --}}
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between gap-2 mt-4">
                        @if(!empty($item['is_granted']))
                            <div class="flex items-center gap-1.5 text-xs text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200 font-medium w-full justify-center">
                                <x-core-ui::icon name="check" class="w-4 h-4 text-emerald-600" />
                                <span>فعال روی سایت شما</span>
                            </div>
                        @elseif(($item['access_type'] ?? '') === 'free')
                            <form method="POST" action="{{ route('admin.canvas-marketplace.claim') }}" class="w-full">
                                @csrf
                                <input type="hidden" name="asset_kind" value="{{ AssetRef::KIND_BUTTON_PACK }}">
                                <input type="hidden" name="asset_key" value="{{ $item['slug'] }}">
                                <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center justify-center gap-1.5">
                                    <x-core-ui::icon name="download" class="w-4 h-4" />
                                    <span>دریافت رایگان پکیج</span>
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.canvas-marketplace.purchase') }}" class="w-full">
                                @csrf
                                <input type="hidden" name="asset_kind" value="{{ AssetRef::KIND_BUTTON_PACK }}">
                                <input type="hidden" name="asset_key" value="{{ $item['slug'] }}">
                                <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center justify-center gap-1.5">
                                    <x-core-ui::icon name="shopping-cart" class="w-4 h-4" />
                                    <span>خرید و فعال‌سازی آنی</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center bg-white rounded-2xl border border-gray-100">
                    <p class="text-sm text-gray-400">هیچ پکیج دکمه‌ای با فیلترهای انتخابی یافت نشد.</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- ===================================================================== --}}
    {{-- TAB 2: ICONS --}}
    {{-- ===================================================================== --}}
    @if($activeTab === 'icons')
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($icons as $item)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col justify-between hover:border-indigo-200 transition">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-xs px-2 py-0.5 rounded-md bg-gray-100 text-gray-600 font-medium">{{ $item['category_label'] ?? 'طبیعت و اشیاء' }}</span>
                                <h3 class="font-bold text-gray-900 text-base mt-1.5">{{ $item['title'] }}</h3>
                                <div class="text-xs text-gray-400 font-mono mt-0.5">{{ $item['slug'] }}</div>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                @if(($item['access_type'] ?? '') === 'free')
                                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-medium">رایگان</span>
                                @else
                                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200 font-medium">{{ number_format($item['price'] ?? 0) }} تومان</span>
                                @endif
                                <button type="button" @click="openChangelog({{ json_encode($item) }})"
                                        class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-mono font-bold transition cursor-pointer"
                                        title="مشاهده تاریخچه نسخه‌ها و تغییرات">
                                    <span dir="ltr">v{{ $item['current_version'] ?? '1.0.0' }}</span>
                                    <span class="text-[10px]">📋</span>
                                </button>
                            </div>
                        </div>

                        @if(!empty($item['description']))
                            <p class="text-xs text-gray-500 line-clamp-2 leading-5">{{ $item['description'] }}</p>
                        @endif

                        {{-- Icons SVG Preview Grid --}}
                        <div class="bg-gray-50 rounded-xl p-3 space-y-2 border border-gray-100">
                            <div class="text-xs text-gray-500 font-medium">نمونه آیکن‌های پکیج:</div>
                            <div class="flex flex-wrap gap-2 max-h-32 overflow-y-auto">
                                @forelse($item['sample_items'] ?? [] as $icon)
                                    <div class="w-10 h-10 bg-white border border-gray-200 rounded-lg flex items-center justify-center p-1.5 shadow-2xs text-gray-700" title="{{ $icon['title'] ?? '' }}">
                                        {!! $icon['svg_content'] ?? '' !!}
                                    </div>
                                @empty
                                    <span class="text-xs text-gray-400 italic">بدون نمونه</span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- Card Footer --}}
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between gap-2 mt-4">
                        @if(!empty($item['is_granted']))
                            <div class="flex items-center gap-1.5 text-xs text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200 font-medium w-full justify-center">
                                <x-core-ui::icon name="check" class="w-4 h-4 text-emerald-600" />
                                <span>فعال روی سایت شما</span>
                            </div>
                        @elseif(($item['access_type'] ?? '') === 'free')
                            <form method="POST" action="{{ route('admin.canvas-marketplace.claim') }}" class="w-full">
                                @csrf
                                <input type="hidden" name="asset_kind" value="{{ AssetRef::KIND_ICON_PACK }}">
                                <input type="hidden" name="asset_key" value="{{ $item['slug'] }}">
                                <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center justify-center gap-1.5">
                                    <x-core-ui::icon name="download" class="w-4 h-4" />
                                    <span>دریافت رایگان پکیج</span>
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.canvas-marketplace.purchase') }}" class="w-full">
                                @csrf
                                <input type="hidden" name="asset_kind" value="{{ AssetRef::KIND_ICON_PACK }}">
                                <input type="hidden" name="asset_key" value="{{ $item['slug'] }}">
                                <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center justify-center gap-1.5">
                                    <x-core-ui::icon name="shopping-cart" class="w-4 h-4" />
                                    <span>خرید و فعال‌سازی آنی</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center bg-white rounded-2xl border border-gray-100">
                    <p class="text-sm text-gray-400">هیچ پکیج آیکنی با فیلترهای انتخابی یافت نشد.</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- ===================================================================== --}}
    {{-- TAB 3: HEADERS --}}
    {{-- ===================================================================== --}}
    @if($activeTab === 'headers')
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($headers as $item)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col justify-between hover:border-indigo-200 transition">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-xs px-2 py-0.5 rounded-md bg-gray-100 text-gray-600 font-medium">{{ $item['category_label'] ?? 'هدر' }}</span>
                                <h3 class="font-bold text-gray-900 text-base mt-1.5">{{ $item['title'] }}</h3>
                                <div class="text-xs text-gray-400 font-mono mt-0.5">{{ $item['slug'] }}</div>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                @if(($item['access_type'] ?? '') === 'free')
                                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-medium">رایگان</span>
                                @else
                                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200 font-medium">{{ number_format($item['price'] ?? 0) }} تومان</span>
                                @endif
                                <button type="button" @click="openChangelog({{ json_encode($item) }})"
                                        class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-mono font-bold transition cursor-pointer"
                                        title="مشاهده تاریخچه نسخه‌ها و تغییرات">
                                    <span dir="ltr">v{{ $item['current_version'] ?? '1.0.0' }}</span>
                                    <span class="text-[10px]">📋</span>
                                </button>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-xl p-3 border border-gray-100 text-xs text-gray-600 space-y-1">
                            <div>کامپوننت زیربنایی: <code class="text-indigo-600 font-mono">{{ $item['component_alias'] ?? 'nav_header' }}</code></div>
                            <div>امکان استفاده مستقیم در بخش Header تمام صفحات Canvas</div>
                        </div>
                    </div>

                    {{-- Card Footer --}}
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between gap-2 mt-4">
                        @if(!empty($item['is_granted']))
                            <div class="flex items-center gap-1.5 text-xs text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200 font-medium w-full justify-center">
                                <x-core-ui::icon name="check" class="w-4 h-4 text-emerald-600" />
                                <span>فعال روی سایت شما</span>
                            </div>
                        @elseif(($item['access_type'] ?? '') === 'free')
                            <form method="POST" action="{{ route('admin.canvas-marketplace.claim') }}" class="w-full">
                                @csrf
                                <input type="hidden" name="asset_kind" value="{{ AssetRef::KIND_HEADER }}">
                                <input type="hidden" name="asset_key" value="{{ $item['slug'] }}">
                                <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center justify-center gap-1.5">
                                    <x-core-ui::icon name="download" class="w-4 h-4" />
                                    <span>دریافت رایگان قالب هدر</span>
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.canvas-marketplace.purchase') }}" class="w-full">
                                @csrf
                                <input type="hidden" name="asset_kind" value="{{ AssetRef::KIND_HEADER }}">
                                <input type="hidden" name="asset_key" value="{{ $item['slug'] }}">
                                <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center justify-center gap-1.5">
                                    <x-core-ui::icon name="shopping-cart" class="w-4 h-4" />
                                    <span>خرید و فعال‌سازی آنی</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center bg-white rounded-2xl border border-gray-100">
                    <p class="text-sm text-gray-400">هیچ قالب هدری یافت نشد.</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- ===================================================================== --}}
    {{-- TAB 4: MOTIONS --}}
    {{-- ===================================================================== --}}
    @if($activeTab === 'motions')
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($motions as $item)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col justify-between hover:border-indigo-200 transition">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-xs px-2 py-0.5 rounded-md bg-gray-100 text-gray-600 font-medium">{{ $item['engine'] ?? 'Lottie' }}</span>
                                <h3 class="font-bold text-gray-900 text-base mt-1.5">{{ $item['title'] ?? $item['slug'] }}</h3>
                                <div class="text-xs text-gray-400 font-mono mt-0.5">{{ $item['slug'] }}</div>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                @if(($item['access_type'] ?? 'free') === 'free')
                                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-medium">رایگان</span>
                                @else
                                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200 font-medium">{{ number_format($item['price'] ?? 0) }} تومان</span>
                                @endif
                                <button type="button" @click="openChangelog({{ json_encode($item) }})"
                                        class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-mono font-bold transition cursor-pointer"
                                        title="مشاهده تاریخچه نسخه‌ها و تغییرات">
                                    <span dir="ltr">v{{ $item['current_version'] ?? '1.0.0' }}</span>
                                    <span class="text-[10px]">📋</span>
                                </button>
                            </div>
                        </div>

                        @if(!empty($item['description']))
                            <p class="text-xs text-gray-500 line-clamp-2 leading-5">{{ $item['description'] }}</p>
                        @endif

                        <div class="bg-gray-50 rounded-xl p-3 border border-gray-100 text-xs text-gray-600">
                            فرمت فایل: <code class="text-indigo-600 font-mono">{{ $item['asset_type'] ?? 'json' }}</code> — قابل استفاده در ویجت موشن گرافیک Canvas
                        </div>
                    </div>

                    {{-- Card Footer --}}
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between gap-2 mt-4">
                        @if(!empty($item['is_granted']))
                            <div class="flex items-center gap-1.5 text-xs text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200 font-medium w-full justify-center">
                                <x-core-ui::icon name="check" class="w-4 h-4 text-emerald-600" />
                                <span>فعال روی سایت شما</span>
                            </div>
                        @elseif(($item['access_type'] ?? 'free') === 'free')
                            <form method="POST" action="{{ route('admin.canvas-marketplace.claim') }}" class="w-full">
                                @csrf
                                <input type="hidden" name="asset_kind" value="{{ AssetRef::KIND_MOTION }}">
                                <input type="hidden" name="asset_key" value="{{ $item['slug'] }}">
                                <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center justify-center gap-1.5">
                                    <x-core-ui::icon name="download" class="w-4 h-4" />
                                    <span>دریافت رایگان موشن</span>
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.canvas-marketplace.purchase') }}" class="w-full">
                                @csrf
                                <input type="hidden" name="asset_kind" value="{{ AssetRef::KIND_MOTION }}">
                                <input type="hidden" name="asset_key" value="{{ $item['slug'] }}">
                                <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center justify-center gap-1.5">
                                    <x-core-ui::icon name="shopping-cart" class="w-4 h-4" />
                                    <span>خرید و فعال‌سازی آنی</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center bg-white rounded-2xl border border-gray-100">
                    <p class="text-sm text-gray-400">هیچ موشن گرافیکی یافت نشد.</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- Changelog History Modal --}}
    <div x-show="showChangelogModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         @keydown.escape.window="showChangelogModal = false">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 space-y-4" @click.outside="showChangelogModal = false">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
                        v
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900" x-text="selectedChangelog?.title"></h3>
                        <p class="text-xs text-gray-500 font-mono" x-text="selectedChangelog?.slug"></p>
                    </div>
                </div>
                <button @click="showChangelogModal = false" class="text-gray-400 hover:text-gray-600 text-lg leading-none cursor-pointer">✕</button>
            </div>

            <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                <template x-for="(ver, idx) in (selectedChangelog?.versions_history || [])" :key="idx">
                    <div class="p-3.5 rounded-xl border border-gray-100 bg-gray-50/70 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono font-bold px-2 py-0.5 rounded bg-indigo-100 text-indigo-800" dir="ltr" x-text="'v' + ver.version"></span>
                            <span class="text-[11px] text-gray-400 font-mono" x-text="ver.published_at || 'نسخه جاری'"></span>
                        </div>
                        <p class="text-xs text-gray-700 leading-5" x-text="ver.changelog || 'تغییرات ثبت نشده است.'"></p>
                    </div>
                </template>
                <template x-if="!selectedChangelog?.versions_history || selectedChangelog?.versions_history.length === 0">
                    <p class="text-xs text-gray-400 text-center py-4">تاریخچه تغییری برای این دارایی ثبت نشده است.</p>
                </template>
            </div>

            <div class="pt-2 border-t border-gray-100 flex justify-end">
                <button type="button" @click="showChangelogModal = false"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition cursor-pointer">
                    بستن
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

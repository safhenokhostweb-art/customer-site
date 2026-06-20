@extends('admin.layout')

@section('title', 'مرکز اعلان‌ها')
@section('header', 'مرکز اعلان‌ها')

@section('content')
<div x-data="notifCenter()" x-init="init()" class="pb-10">

    {{-- ═══ HERO HEADER ═══ --}}
    <div class="relative overflow-hidden rounded-2xl mb-8"
         style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #9333ea 100%);">
        <div class="absolute inset-0 opacity-10"
             style="background-image: radial-gradient(circle at 20% 50%, #fff 1px, transparent 1px),
                    radial-gradient(circle at 80% 20%, #fff 1px, transparent 1px);
                    background-size: 40px 40px;"></div>
        <div class="relative px-8 py-7 flex items-center justify-between">
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-white">مرکز اعلان‌ها</h1>
                    <p class="text-white/70 text-sm mt-1">هشدارهای سیستمی، اعلان‌های سایت و پیام‌های کاربران — یکجا</p>
                </div>
            </div>
            @if($unreadAlertsCount > 0)
            <div class="flex items-center gap-3">
                <span class="px-4 py-2 rounded-full bg-white/20 text-white text-sm font-bold">
                    {{ $unreadAlertsCount }} هشدار جدید
                </span>
                <button @click="markAllRead()"
                        class="flex items-center gap-2 px-4 py-2 rounded-full bg-white text-indigo-700 text-sm font-bold hover:bg-indigo-50 transition shadow">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    همه را خواندم
                </button>
            </div>
            @endif
        </div>
    </div>

    {{-- ═══ STATS CARDS ═══ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

        {{-- Unread Alerts --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex items-center gap-4 cursor-pointer hover:shadow-md transition"
             @click="switchTab('alerts')">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background: linear-gradient(135deg, #fef3c7, #fde68a);">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-800">{{ $unreadAlertsCount }}</p>
                <p class="text-xs text-slate-500 mt-0.5">هشدار خوانده‌نشده</p>
            </div>
        </div>

        {{-- Active Banners --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex items-center gap-4 cursor-pointer hover:shadow-md transition"
             @click="switchTab('banners')">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background: linear-gradient(135deg, #dcfce7, #bbf7d0);">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-800">{{ $activeBannersCount }}</p>
                <p class="text-xs text-slate-500 mt-0.5">اعلان بنری فعال</p>
            </div>
        </div>

        {{-- Sent This Month --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex items-center gap-4 cursor-pointer hover:shadow-md transition"
             @click="switchTab('send')">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background: linear-gradient(135deg, #ede9fe, #ddd6fe);">
                <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-800">{{ $sentThisMonth }}</p>
                <p class="text-xs text-slate-500 mt-0.5">پیام ارسال‌شده این ماه</p>
            </div>
        </div>

        {{-- Users with Unread --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex items-center gap-4 cursor-pointer hover:shadow-md transition"
             @click="switchTab('send')">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background: linear-gradient(135deg, #fee2e2, #fecaca);">
                <svg class="w-6 h-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-800">{{ $usersWithUnread }}</p>
                <p class="text-xs text-slate-500 mt-0.5">کاربر با پیام نخوانده</p>
            </div>
        </div>

    </div>

    {{-- ═══ TABS NAV ═══ --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        <div class="flex border-b border-slate-200">
            {{-- Tab: هشدارهای سیستمی --}}
            <button @click="switchTab('alerts')"
                    :class="activeTab === 'alerts'
                        ? 'border-b-2 border-indigo-600 text-indigo-700 bg-indigo-50/60 font-bold'
                        : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50'"
                    class="flex-1 flex items-center justify-center gap-2.5 px-6 py-4 text-sm font-medium transition border-b-2 border-transparent">
                <div class="relative">
                    <svg class="w-4.5 h-4.5" style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    @if($unreadAlertsCount > 0)
                    <span class="absolute -top-2 -left-2 w-4 h-4 rounded-full bg-rose-500 text-white text-[9px] font-black flex items-center justify-center">
                        {{ $unreadAlertsCount > 9 ? '9+' : $unreadAlertsCount }}
                    </span>
                    @endif
                </div>
                <span>هشدارهای سیستمی</span>
            </button>

            {{-- Tab: اعلان‌های بنری --}}
            <button @click="switchTab('banners')"
                    :class="activeTab === 'banners'
                        ? 'border-b-2 border-emerald-600 text-emerald-700 bg-emerald-50/60 font-bold'
                        : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50'"
                    class="flex-1 flex items-center justify-center gap-2.5 px-6 py-4 text-sm font-medium transition border-b-2 border-transparent">
                <svg style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
                <span>اعلان‌های بنری</span>
                @if($activeBannersCount > 0)
                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700">
                    {{ $activeBannersCount }} فعال
                </span>
                @endif
            </button>

            {{-- Tab: پیام به کاربران --}}
            <button @click="switchTab('send')"
                    :class="activeTab === 'send'
                        ? 'border-b-2 border-violet-600 text-violet-700 bg-violet-50/60 font-bold'
                        : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50'"
                    class="flex-1 flex items-center justify-center gap-2.5 px-6 py-4 text-sm font-medium transition border-b-2 border-transparent">
                <svg style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                <span>پیام به کاربران</span>
            </button>
        </div>

        {{-- ══════════════════════════════════════════════ --}}
        {{-- TAB 1: هشدارهای سیستمی                       --}}
        {{-- ══════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'alerts'" x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

            {{-- Toolbar --}}
            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <form method="GET" action="{{ route('admin.notification-center.index') }}" class="flex items-center gap-2 flex-1">
                    <input type="hidden" name="tab" value="alerts">
                    <select name="alert_type" onchange="this.form.submit()"
                            class="text-xs border border-slate-200 rounded-lg px-3 py-1.5 bg-white text-slate-700 focus:ring-2 focus:ring-indigo-300 outline-none">
                        <option value="">همه انواع</option>
                        <option value="order" {{ request('alert_type') === 'order' ? 'selected' : '' }}>🛒 سفارش</option>
                        <option value="ticket" {{ request('alert_type') === 'ticket' ? 'selected' : '' }}>💬 تیکت</option>
                        <option value="subscription" {{ request('alert_type') === 'subscription' ? 'selected' : '' }}>⭐ اشتراک</option>
                        <option value="system" {{ request('alert_type') === 'system' ? 'selected' : '' }}>🔔 سیستم</option>
                    </select>
                    <select name="alert_status" onchange="this.form.submit()"
                            class="text-xs border border-slate-200 rounded-lg px-3 py-1.5 bg-white text-slate-700 focus:ring-2 focus:ring-indigo-300 outline-none">
                        <option value="">همه وضعیت‌ها</option>
                        <option value="unread" {{ request('alert_status') === 'unread' ? 'selected' : '' }}>خوانده‌نشده</option>
                        <option value="read" {{ request('alert_status') === 'read' ? 'selected' : '' }}>خوانده‌شده</option>
                    </select>
                </form>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-400">{{ $alerts->total() }} مورد</span>
                    @if($unreadAlertsCount > 0)
                    <button @click="markAllRead()"
                            class="text-xs flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        همه خوانده‌شده
                    </button>
                    @endif
                </div>
            </div>

            {{-- Alert list --}}
            <div class="divide-y divide-slate-100">
                @forelse($alerts as $alert)
                @php
                    $typeMap = [
                        'order'        => ['emoji'=>'🛒','bg'=>'bg-emerald-100','text'=>'text-emerald-700','label'=>'سفارش'],
                        'ticket'       => ['emoji'=>'💬','bg'=>'bg-blue-100','text'=>'text-blue-700','label'=>'تیکت'],
                        'subscription' => ['emoji'=>'⭐','bg'=>'bg-violet-100','text'=>'text-violet-700','label'=>'اشتراک'],
                        'system'       => ['emoji'=>'🔔','bg'=>'bg-gray-100','text'=>'text-gray-600','label'=>'سیستم'],
                    ];
                    $tm = $typeMap[$alert->type] ?? $typeMap['system'];
                @endphp
                <div id="alert-{{ $alert->id }}"
                     class="flex items-start gap-4 px-6 py-4 {{ $alert->is_read ? '' : 'bg-indigo-50/30' }} hover:bg-slate-50/80 transition group">

                    {{-- Type badge --}}
                    <div class="mt-0.5 w-11 h-11 rounded-xl flex-shrink-0 flex items-center justify-center text-lg {{ $tm['bg'] }} shadow-sm">
                        {{ $tm['emoji'] }}
                    </div>

                    {{-- Content --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 mb-0.5">
                            @if(! $alert->is_read)
                            <span class="w-2 h-2 rounded-full bg-indigo-500 flex-shrink-0 animate-pulse"></span>
                            @endif
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $tm['bg'] }} {{ $tm['text'] }}">{{ $tm['label'] }}</span>
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ $alert->title }}</p>
                        </div>
                        @if($alert->body)
                        <p class="text-xs text-slate-500 truncate">{{ $alert->body }}</p>
                        @endif
                        <p class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ \App\Helpers\Jalali::diffForHumansFa($alert->created_at) }}
                        </p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition flex-shrink-0">
                        @if($alert->link)
                        <a href="{{ $alert->link }}"
                           class="text-xs flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            مشاهده
                        </a>
                        @endif
                        @if(! $alert->is_read)
                        <button onclick="markAlertRead({{ $alert->id }})"
                                class="text-xs flex items-center gap-1 px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-medium transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            خواندم
                        </button>
                        @endif
                    </div>
                </div>
                @empty
                <div class="py-20 text-center">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-slate-100 flex items-center justify-center text-4xl">🔕</div>
                    <p class="text-slate-500 font-medium">هیچ هشداری وجود ندارد</p>
                    <p class="text-slate-400 text-xs mt-1">هشدارهای سیستمی به‌صورت خودکار اینجا نمایش داده می‌شوند</p>
                </div>
                @endforelse
            </div>

            @if($alerts->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">{{ $alerts->links() }}</div>
            @endif
        </div>

        {{-- ══════════════════════════════════════════════ --}}
        {{-- TAB 2: اعلان‌های بنری                        --}}
        {{-- ══════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'banners'" x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

            {{-- Toolbar --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <span class="text-xs text-slate-500">{{ $banners->count() }} اعلان —
                        <span class="text-emerald-600 font-bold">{{ $activeBannersCount }} فعال</span>
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.notifications.create') }}"
                       class="flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        اعلان جدید
                    </a>
                </div>
            </div>

            {{-- Banner grid --}}
            @if($banners->isEmpty())
            <div class="py-20 text-center">
                <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-emerald-50 flex items-center justify-center text-4xl">📢</div>
                <p class="text-slate-500 font-medium">هیچ اعلان بنری‌ای وجود ندارد</p>
                <a href="{{ route('admin.notifications.create') }}" class="mt-3 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 transition">
                    اولین اعلان را بسازید
                </a>
            </div>
            @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-6">
                @foreach($banners as $banner)
                @php
                    $bannerTypeStyle = match($banner->type) {
                        'success' => ['border'=>'border-emerald-200','bg'=>'bg-emerald-50','badge'=>'bg-emerald-100 text-emerald-700','icon'=>'✅'],
                        'warning' => ['border'=>'border-amber-200','bg'=>'bg-amber-50','badge'=>'bg-amber-100 text-amber-700','icon'=>'⚠️'],
                        'danger'  => ['border'=>'border-rose-200','bg'=>'bg-rose-50','badge'=>'bg-rose-100 text-rose-700','icon'=>'🚨'],
                        default   => ['border'=>'border-blue-200','bg'=>'bg-blue-50','badge'=>'bg-blue-100 text-blue-700','icon'=>'ℹ️'],
                    };
                @endphp
                <div class="border {{ $bannerTypeStyle['border'] }} {{ $banner->is_active ? '' : 'opacity-60' }} rounded-xl overflow-hidden shadow-sm hover:shadow-md transition">
                    {{-- Card header --}}
                    <div class="{{ $bannerTypeStyle['bg'] }} px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">{{ $bannerTypeStyle['icon'] }}</span>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $bannerTypeStyle['badge'] }}">
                                {{ $banner->type }}
                            </span>
                            @if($banner->is_active)
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500 text-white">فعال</span>
                            @else
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-300 text-slate-600">غیرفعال</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5">
                            {{-- Toggle --}}
                            <form method="POST" action="{{ route('admin.notification-center.toggle-banner', $banner) }}">
                                @csrf
                                <button type="submit" title="{{ $banner->is_active ? 'غیرفعال کردن' : 'فعال کردن' }}"
                                        class="p-1.5 rounded-lg {{ $banner->is_active ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }} transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $banner->is_active ? 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21' : 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' }}"/>
                                    </svg>
                                </button>
                            </form>
                            {{-- Edit --}}
                            <a href="{{ route('admin.notifications.edit', $banner) }}"
                               class="p-1.5 rounded-lg bg-slate-100 text-slate-600 hover:bg-indigo-100 hover:text-indigo-700 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    {{-- Card body --}}
                    <div class="bg-white px-4 py-3">
                        <p class="text-sm font-bold text-slate-800 truncate">{{ $banner->title }}</p>
                        @if($banner->body)
                        <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ $banner->body }}</p>
                        @endif
                        <div class="flex items-center gap-3 mt-2.5 text-[11px] text-slate-400">
                            <span>موقعیت: <strong class="text-slate-600">{{ $banner->position }}</strong></span>
                            @if($banner->starts_at)
                            <span>از {{ \App\Helpers\Jalali::formatFa($banner->starts_at, 'Y/m/d') }}</span>
                            @endif
                            @if($banner->expires_at)
                            <span>تا {{ \App\Helpers\Jalali::formatFa($banner->expires_at, 'Y/m/d') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- ══════════════════════════════════════════════ --}}
        {{-- TAB 3: پیام به کاربران                       --}}
        {{-- ══════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'send'" x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

            <div class="grid grid-cols-1 lg:grid-cols-5 divide-y lg:divide-y-0 lg:divide-x lg:divide-x-reverse divide-slate-100">

                {{-- Send Form (right side) --}}
                <div class="lg:col-span-2 p-6 bg-slate-50/40">
                    <h3 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        ارسال پیام جدید
                    </h3>
                    <a href="{{ route('admin.notifications.send-users.form') }}"
                       class="flex items-center justify-center gap-2 w-full px-4 py-3 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold transition shadow-sm mb-4">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        ارسال پیام به کاربران
                    </a>

                    {{-- Quick Stats --}}
                    <div class="space-y-3 mt-4">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-white border border-slate-200">
                            <span class="text-xs text-slate-500">ارسال این ماه</span>
                            <span class="text-sm font-black text-violet-700">{{ $sentThisMonth }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-white border border-slate-200">
                            <span class="text-xs text-slate-500">کاربران با پیام نخوانده</span>
                            <span class="text-sm font-black text-rose-600">{{ $usersWithUnread }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-white border border-slate-200">
                            <span class="text-xs text-slate-500">مجموع دسته‌های ارسالی</span>
                            <span class="text-sm font-black text-slate-700">{{ $batches->total() }}</span>
                        </div>
                    </div>
                </div>

                {{-- Batch History (left side) --}}
                <div class="lg:col-span-3 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            آرشیو پیام‌های ارسال‌شده
                        </h3>
                        <a href="{{ route('admin.notifications.archive') }}"
                           class="text-xs text-violet-600 hover:underline font-medium">مشاهده همه</a>
                    </div>

                    @forelse($batches as $batch)
                    @php
                        $batchStyle = match($batch->type ?? 'info') {
                            'success' => 'bg-emerald-100 text-emerald-700',
                            'warning' => 'bg-amber-100 text-amber-700',
                            'danger'  => 'bg-rose-100 text-rose-700',
                            'promo'   => 'bg-pink-100 text-pink-700',
                            'ad'      => 'bg-orange-100 text-orange-700',
                            default   => 'bg-blue-100 text-blue-700',
                        };
                        $batchIcon = match($batch->type ?? 'info') {
                            'success' => '✅', 'warning' => '⚠️', 'danger' => '🚨',
                            'promo' => '🎁', 'ad' => '📣', default => '📩',
                        };
                    @endphp
                    <div class="flex items-start gap-3 py-3 border-b border-slate-100 last:border-0 hover:bg-slate-50 rounded-xl px-2 transition group">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-base {{ $batchStyle }} flex-shrink-0">
                            {{ $batchIcon }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ $batch->title }}</p>
                            <div class="flex items-center gap-3 mt-0.5 text-[11px] text-slate-400">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                                    </svg>
                                    {{ number_format($batch->recipient_count ?? 0) }} کاربر
                                </span>
                                <span>{{ $batch->sent_at ? \App\Helpers\Jalali::diffForHumansFa($batch->sent_at) : '' }}</span>
                                @if($batch->createdByUser)
                                <span>توسط {{ $batch->createdByUser->name ?? '—' }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $batchStyle }} flex-shrink-0">
                            {{ $batch->type }}
                        </span>
                    </div>
                    @empty
                    <div class="py-12 text-center">
                        <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-violet-50 flex items-center justify-center text-3xl">📭</div>
                        <p class="text-slate-500 text-sm">هنوز پیامی ارسال نشده</p>
                    </div>
                    @endforelse

                    @if($batches->hasPages())
                    <div class="mt-4">{{ $batches->links() }}</div>
                    @endif
                </div>
            </div>
        </div>

    </div>{{-- /card --}}
</div>

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function notifCenter() {
    return {
        activeTab: '{{ $activeTab }}',

        init() {},

        switchTab(tab) {
            this.activeTab = tab;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url.toString());
        },

        markAllRead() {
            fetch('{{ route('admin.notification-center.mark-all-read') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            }).then(r => r.json()).then(d => {
                if (d.success) {
                    document.querySelectorAll('[id^="alert-"]').forEach(el => {
                        el.classList.remove('bg-indigo-50/30');
                        el.querySelector('.animate-pulse')?.remove();
                        el.querySelector('[onclick^="markAlertRead"]')?.closest('button')?.remove();
                    });
                    document.querySelectorAll('.unread-dot').forEach(el => el.remove());
                }
            });
        },
    };
}

function markAlertRead(id) {
    fetch(`{{ url('admin/notification-center/alerts') }}/${id}/read`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    }).then(r => r.json()).then(d => {
        if (d.success) {
            const row = document.getElementById('alert-' + id);
            if (row) {
                row.classList.remove('bg-indigo-50/30');
                row.querySelector('.animate-pulse')?.remove();
                row.querySelector(`[onclick="markAlertRead(${id})"]`)?.closest('button')?.remove();
            }
        }
    });
}
</script>
@endpush
@endsection

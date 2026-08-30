{{--
  Phase 1h — generic structured-dependency presentation. Renders any
  dependency_type without hardcoded branching (module/motion_asset/
  motion_pack/icon_asset/icon_pack/block_pack/design_system/anything future)
  and never presents an "unresolved" release as dependency-free.

  Expects: $theme (the raw theme-marketplace API array).
--}}
@php
  $dependencies = \App\Services\ThemeMarketplacePresenter::dependencies($theme);
  $dependencyStatus = \App\Services\ThemeMarketplacePresenter::dependencyStatus($theme);
@endphp

@if($dependencyStatus === 'unresolved')
  <div class="flex items-start gap-2 text-sm bg-blue-50 border border-blue-100 rounded-lg px-3 py-2.5 text-blue-700">
    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span><span class="font-semibold">اطلاعات نیازمندی‌های این قالب هنوز به‌طور کامل تأیید نشده است.</span> این به معنای «بدون نیازمندی» نیست — جزئیات به‌زودی تکمیل می‌شود.</span>
  </div>
@elseif(!empty($dependencies))
  <div class="space-y-2">
    @foreach($dependencies as $dep)
      <div class="flex items-center justify-between gap-3 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
        <div class="flex items-center gap-2 min-w-0">
          <span class="shrink-0 bg-white border border-gray-200 text-gray-500 text-xs px-2 py-0.5 rounded-full font-medium">
            {{ \App\Services\ThemeMarketplacePresenter::dependencyTypeLabel($dep['type'] ?? '') }}
          </span>
          <span class="font-mono text-sm text-gray-700 truncate">{{ $dep['identifier'] ?? '—' }}</span>
          @if(!empty($dep['version_constraint']))
            <span class="text-xs text-gray-400 font-mono shrink-0">{{ $dep['version_constraint'] }}</span>
          @endif
        </div>
        <span class="shrink-0 text-xs font-medium {{ ($dep['is_required'] ?? false) ? 'text-rose-600' : 'text-gray-400' }}">
          {{ ($dep['is_required'] ?? false) ? 'الزامی' : 'اختیاری' }}
        </span>
      </div>
      @if(!empty($dep['metadata']['description'] ?? null))
        <p class="text-xs text-gray-400 px-1 -mt-1">{{ $dep['metadata']['description'] }}</p>
      @endif
    @endforeach
  </div>
@else
  <p class="text-sm text-gray-400">این قالب نیازمندی خاصی ندارد.</p>
@endif

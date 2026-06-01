@php
    /**
     * Theme Engine Token Injector (customer-site)
     * Uses TenantThemeResolver to emit CSS custom property overrides
     * based on the tenant's ThemeSetting (preset or custom colors).
     * Injected AFTER design-tokens.css — cascade takes effect without modifying it.
     */
    try {
        $resolver  = app(\App\Services\ThemeEngine\TenantThemeResolver::class);
        $cssBlock  = $resolver->renderCssBlock(null);
    } catch (\Throwable $e) {
        $cssBlock = '';
    }
@endphp
@if($cssBlock)
{!! $cssBlock !!}
@endif

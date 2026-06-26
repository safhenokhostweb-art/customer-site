@php
    /**
     * Dashboard Theme Token Injector
     * Outputs ONLY --admin-sidebar-* CSS vars for the admin layout.
     * Site-track vars (--ui-*, --ds-*, --tb-*) are intentionally excluded
     * to prevent the site theme preset from bleeding into the admin dashboard.
     */
    try {
        $resolver  = app(\App\Services\ThemeEngine\TenantThemeResolver::class);
        $cssBlock  = $resolver->renderDashboardCssBlock(null);
    } catch (\Throwable $e) {
        $cssBlock = '';
    }
@endphp
@if($cssBlock)
{!! $cssBlock !!}
@endif

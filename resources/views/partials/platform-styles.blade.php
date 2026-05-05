@php
    $cssVars = \App\Helpers\PlatformUx::getCssVariables();
    $fontImports = \App\Helpers\PlatformUx::getFontImports();
@endphp
@if(!empty($cssVars))
@foreach($fontImports as $url)
    <link rel="stylesheet" href="{{ $url }}" crossorigin="anonymous">
@endforeach
<style id="platform-theme">
:root {
@foreach($cssVars as $var => $value)
    {{ $var }}: {{ $value }};
@endforeach
}
body { font-family: var(--ds-font-body); font-size: var(--ds-font-size-base); }
h1, h2, h3, h4, h5, h6, .font-title { font-family: var(--ds-font-title); font-size: calc(1em * var(--ds-font-size-title)); color: var(--ds-title-color); }
.navbar { background: var(--ds-menu-bg) !important; }
.navbar-brand { color: var(--ds-primary) !important; }
.navbar-link { color: var(--ds-menu-text) !important; }
.navbar-link:hover, .navbar-link.text-blue-600 { color: var(--ds-menu-link-active) !important; }
.btn-primary { background: var(--ds-button-primary-bg) !important; color: var(--ds-button-primary-text) !important; }
a { color: var(--ds-link-color); }
a:hover { color: var(--ds-link-hover); }
.sidebar-link { color: var(--ds-sidebar-text); }
.sidebar-link:hover, .sidebar-link.active { background: var(--ds-sidebar-active-bg); color: var(--ds-sidebar-active-text); }
</style>
@else
@php
    $theme = \App\Helpers\PlatformUx::allTheme();
    $font = \App\Helpers\PlatformUx::allFont();
@endphp
@for($i = 1; $i <= 5; $i++)
    @if(!empty($font['font_custom_' . $i . '_url']))
        <link rel="stylesheet" href="{{ $font['font_custom_' . $i . '_url'] }}" crossorigin="anonymous">
    @endif
@endfor
<style id="platform-theme">
:root {
    --ds-primary: {{ $theme['theme_primary'] ?? '#4f46e5' }};
    --ds-secondary: {{ $theme['theme_secondary'] ?? '#6366f1' }};
    --ds-menu-bg: {{ $theme['theme_menu_bg'] ?? '#ffffff' }};
    --ds-menu-text: {{ $theme['theme_menu_text'] ?? '#374151' }};
    --ds-menu-link-active: {{ $theme['theme_menu_link_active'] ?? '#2563eb' }};
    --ds-title-color: {{ $theme['theme_title_color'] ?? '#111827' }};
    --ds-link-color: {{ $theme['theme_link_color'] ?? '#4f46e5' }};
    --ds-link-hover: {{ $theme['theme_link_hover'] ?? '#4338ca' }};
    --ds-sidebar-bg: {{ $theme['theme_sidebar_bg'] ?? '#ffffff' }};
    --ds-sidebar-text: {{ $theme['theme_sidebar_text'] ?? '#374151' }};
    --ds-sidebar-active-bg: {{ $theme['theme_sidebar_active_bg'] ?? '#eff6ff' }};
    --ds-sidebar-active-text: {{ $theme['theme_sidebar_active_text'] ?? '#2563eb' }};
    --ds-button-primary-bg: {{ $theme['theme_button_primary_bg'] ?? '#4f46e5' }};
    --ds-button-primary-text: {{ $theme['theme_button_primary_text'] ?? '#ffffff' }};
    --ds-font-body: {{ $font['font_body'] ?? 'Vazirmatn, Tahoma, sans-serif' }};
    --ds-font-title: {{ $font['font_title'] ?? 'Vazirmatn, Tahoma, sans-serif' }};
    --ds-font-size-base: {{ isset($font['font_size_base']) && $font['font_size_base'] !== '' ? $font['font_size_base'] . 'px' : '16px' }};
    --ds-font-size-title: {{ isset($font['font_size_title']) && $font['font_size_title'] !== '' ? $font['font_size_title'] : '1.25' }};
}
body { font-family: var(--ds-font-body); font-size: var(--ds-font-size-base); }
h1, h2, h3, h4, h5, h6, .font-title { font-family: var(--ds-font-title); font-size: calc(1em * var(--ds-font-size-title)); color: var(--ds-title-color); }
.navbar { background: var(--ds-menu-bg) !important; }
.navbar-brand { color: var(--ds-primary) !important; }
.navbar-link { color: var(--ds-menu-text) !important; }
.navbar-link:hover, .navbar-link.text-blue-600 { color: var(--ds-menu-link-active) !important; }
.btn-primary { background: var(--ds-button-primary-bg) !important; color: var(--ds-button-primary-text) !important; }
a { color: var(--ds-link-color); }
a:hover { color: var(--ds-link-hover); }
.sidebar-link { color: var(--ds-sidebar-text); }
.sidebar-link:hover, .sidebar-link.active { background: var(--ds-sidebar-active-bg); color: var(--ds-sidebar-active-text); }
</style>
@endif

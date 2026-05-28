@php
    $sb = \App\Helpers\AdminSidebarTheme::all();
@endphp
<style id="customer-admin-sidebar-theme">
:root {
    --admin-sidebar-bg1: {{ $sb['admin_sidebar_bg1'] }};
    --admin-sidebar-bg2: {{ $sb['admin_sidebar_bg2'] }};
    --admin-sidebar-text: {{ $sb['admin_sidebar_text'] }};
    --admin-sidebar-muted: {{ $sb['admin_sidebar_muted'] }};
    --admin-sidebar-accent: {{ $sb['admin_sidebar_accent'] }};
    --admin-sidebar-border: {{ $sb['admin_sidebar_border'] }};
    --admin-sidebar-hover: color-mix(in srgb, {{ $sb['admin_sidebar_accent'] }} 20%, transparent);
    --admin-sidebar-active: color-mix(in srgb, {{ $sb['admin_sidebar_accent'] }} 32%, transparent);
}
#admin-sidebar {
    box-shadow: -4px 0 24px color-mix(in srgb, var(--admin-sidebar-bg2) 50%, transparent) !important;
    border-left-color: color-mix(in srgb, var(--admin-sidebar-border) 55%, transparent) !important;
}
.admin-sidebar-brand {
    background: color-mix(in srgb, var(--admin-sidebar-bg2) 78%, #0f172a 22%) !important;
    border-bottom-color: color-mix(in srgb, var(--admin-sidebar-border) 45%, transparent) !important;
}
.account-sidebar-shell {
    background: linear-gradient(165deg, var(--admin-sidebar-bg1) 0%, var(--admin-sidebar-bg2) 100%) !important;
    border-left: 1px solid color-mix(in srgb, var(--admin-sidebar-border) 55%, transparent) !important;
    box-shadow: -4px 0 20px color-mix(in srgb, var(--admin-sidebar-bg2) 45%, transparent) !important;
}
.account-sidebar-brand {
    background: color-mix(in srgb, var(--admin-sidebar-bg2) 78%, #0f172a 22%) !important;
    border-bottom-color: color-mix(in srgb, var(--admin-sidebar-border) 45%, transparent) !important;
}
#account-sidebar .account-sidebar-brand svg {
    color: var(--admin-sidebar-accent) !important;
}
#account-sidebar .account-sidebar-brand .text-slate-100 {
    color: var(--admin-sidebar-text) !important;
}
#account-sidebar nav .mb-4 > div.px-3.py-2.text-xs {
    color: var(--admin-sidebar-muted) !important;
    border-bottom-color: color-mix(in srgb, var(--admin-sidebar-border) 45%, transparent) !important;
}
.account-sidebar-shell .account-sidebar-link {
    color: var(--admin-sidebar-text) !important;
}
.account-sidebar-shell .account-sidebar-link:hover {
    background: var(--admin-sidebar-hover) !important;
    color: #fff !important;
}
.account-sidebar-shell .account-sidebar-link.active {
    background: var(--admin-sidebar-active) !important;
    color: #fff !important;
    box-shadow: inset -3px 0 0 var(--admin-sidebar-accent) !important;
}
.account-sidebar-shell .border-slate-200 {
    border-color: color-mix(in srgb, var(--admin-sidebar-border) 40%, transparent) !important;
}
#admin-sidebar .sidebar-link {
    color: var(--admin-sidebar-text) !important;
}
#admin-sidebar .sidebar-link:hover {
    background: var(--admin-sidebar-hover) !important;
    color: #fff !important;
    transform: none !important;
}
#admin-sidebar .sidebar-link.active {
    background: var(--admin-sidebar-active) !important;
    color: #fff !important;
    font-weight: 600 !important;
    box-shadow: inset -3px 0 0 var(--admin-sidebar-accent) !important;
}
#admin-sidebar .sidebar-link svg {
    color: var(--admin-sidebar-muted) !important;
}
#admin-sidebar .sidebar-link:hover svg,
#admin-sidebar .sidebar-link.active svg {
    color: var(--admin-sidebar-accent) !important;
    transform: none !important;
}
#admin-sidebar .sidebar-group-toggle:hover {
    background: var(--admin-sidebar-hover) !important;
}
#admin-sidebar .sidebar-group-label {
    color: var(--admin-sidebar-muted) !important;
    border-bottom-color: var(--admin-sidebar-border) !important;
}
#admin-sidebar .sidebar-group-toggle[aria-expanded="true"] .sidebar-group-label {
    color: var(--admin-sidebar-accent) !important;
    border-bottom-color: color-mix(in srgb, var(--admin-sidebar-accent) 35%, transparent) !important;
}
#admin-sidebar .sidebar-group-caret {
    color: var(--admin-sidebar-muted) !important;
}
#admin-sidebar .sidebar-group-caret.is-open {
    color: var(--admin-sidebar-accent) !important;
}
#admin-sidebar .admin-sidebar-footer {
    border-top-color: var(--admin-sidebar-border) !important;
}
</style>

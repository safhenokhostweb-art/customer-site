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

/* ═══ رنگ‌بندی گروه‌های منو — هر گروه رنگ خاص خودش ═══ */
#admin-sidebar .admin-sidebar-group { --grp-color: var(--admin-sidebar-accent); }

/* ۱ اصلی → indigo */
#admin-sidebar .admin-sidebar-group:nth-child(1) { --grp-color: #6366f1; }
/* ۲ محتوا و ساختار → sky */
#admin-sidebar .admin-sidebar-group:nth-child(2) { --grp-color: #0284c7; }
/* ۳ فروشگاه → emerald */
#admin-sidebar .admin-sidebar-group:nth-child(3) { --grp-color: #059669; }
/* ۴ تیکت و پشتیبانی → rose */
#admin-sidebar .admin-sidebar-group:nth-child(4) { --grp-color: #e11d48; }
/* ۵ فرم‌ها و ارتباط → amber */
#admin-sidebar .admin-sidebar-group:nth-child(5) { --grp-color: #d97706; }
/* ۶ اشتراک و آموزش → violet */
#admin-sidebar .admin-sidebar-group:nth-child(6) { --grp-color: #7c3aed; }
/* ۷ کسب‌وکار و نمایش → teal */
#admin-sidebar .admin-sidebar-group:nth-child(7) { --grp-color: #0d9488; }
/* ۸ ظاهر، امنیت و تنظیمات → slate */
#admin-sidebar .admin-sidebar-group:nth-child(8) { --grp-color: #64748b; }
/* ۹ لایسنس، ماژول‌ها → orange */
#admin-sidebar .admin-sidebar-group:nth-child(9) { --grp-color: #ea580c; }
/* ۱۰ ماژول‌های از پلتفرم → purple */
#admin-sidebar .admin-sidebar-group:nth-child(10) { --grp-color: #9333ea; }

#admin-sidebar .admin-sidebar-group .sidebar-group-label {
    color: var(--grp-color) !important;
    border-bottom-color: color-mix(in srgb, var(--grp-color) 30%, transparent) !important;
}
#admin-sidebar .admin-sidebar-group .sidebar-group-caret {
    color: var(--grp-color) !important;
    opacity: 0.7;
}
#admin-sidebar .admin-sidebar-group .sidebar-link:hover {
    background: color-mix(in srgb, var(--grp-color) 18%, transparent) !important;
    color: #fff !important;
}
#admin-sidebar .admin-sidebar-group .sidebar-link.active {
    background: color-mix(in srgb, var(--grp-color) 28%, transparent) !important;
    color: #fff !important;
    box-shadow: inset -3px 0 0 var(--grp-color) !important;
}
#admin-sidebar .admin-sidebar-group .sidebar-link:hover svg,
#admin-sidebar .admin-sidebar-group .sidebar-link.active svg {
    color: var(--grp-color) !important;
}

/* ═══ رنگ‌بندی گروه‌های منو — پنل کاربر (account-sidebar) ═══ */
#account-sidebar .mb-4 { --grp-color: var(--admin-sidebar-accent); }
/* ۱ اصلی */
#account-sidebar .mb-4:nth-child(1) { --grp-color: #6366f1; }
/* ۲ خرید و اشتراک */
#account-sidebar .mb-4:nth-child(2) { --grp-color: #059669; }
/* ۳ تعامل */
#account-sidebar .mb-4:nth-child(3) { --grp-color: #0284c7; }
/* ۴ حساب کاربری */
#account-sidebar .mb-4:nth-child(4) { --grp-color: #7c3aed; }

#account-sidebar .mb-4 > div.px-3.py-2.text-xs {
    color: var(--grp-color) !important;
    border-bottom-color: color-mix(in srgb, var(--grp-color) 30%, transparent) !important;
    font-weight: 700 !important;
}
#account-sidebar .mb-4 .account-sidebar-link:hover {
    background: color-mix(in srgb, var(--grp-color) 18%, transparent) !important;
    color: #fff !important;
}
#account-sidebar .mb-4 .account-sidebar-link.active {
    background: color-mix(in srgb, var(--grp-color) 28%, transparent) !important;
    color: #fff !important;
    box-shadow: inset -3px 0 0 var(--grp-color) !important;
}
</style>

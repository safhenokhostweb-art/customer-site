<style id="customer-admin-sidebar-theme">
#admin-sidebar {
    box-shadow: -4px 0 24px color-mix(in srgb, var(--admin-sidebar-bg2) 50%, transparent) !important;
    border-left-color: color-mix(in srgb, var(--admin-sidebar-border) 55%, transparent) !important;
}
.admin-sidebar-brand {
    background: color-mix(in srgb, var(--admin-sidebar-bg2) 78%, rgba(0,0,0,0.85) 22%) !important;
    border-bottom-color: color-mix(in srgb, var(--admin-sidebar-border) 45%, transparent) !important;
}
.account-sidebar-shell {
    background: linear-gradient(165deg, var(--admin-sidebar-bg1) 0%, var(--admin-sidebar-bg2) 100%) !important;
    border-left: 1px solid color-mix(in srgb, var(--admin-sidebar-border) 55%, transparent) !important;
    box-shadow: -4px 0 20px color-mix(in srgb, var(--admin-sidebar-bg2) 45%, transparent) !important;
}
.account-sidebar-brand {
    background: color-mix(in srgb, var(--admin-sidebar-bg2) 78%, rgba(0,0,0,0.85) 22%) !important;
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

/* ═══ رنگ‌بندی گروه‌های منو — از Design Token Authority ═══ */
#admin-sidebar .admin-sidebar-group { --grp-color: var(--admin-sidebar-accent); }

#admin-sidebar .admin-sidebar-group:nth-child(1)  { --grp-color: var(--ui-indigo);       }
#admin-sidebar .admin-sidebar-group:nth-child(2)  { --grp-color: var(--ui-info);          }
#admin-sidebar .admin-sidebar-group:nth-child(3)  { --grp-color: var(--ui-success);       }
#admin-sidebar .admin-sidebar-group:nth-child(4)  { --grp-color: var(--ui-danger);        }
#admin-sidebar .admin-sidebar-group:nth-child(5)  { --grp-color: var(--ui-warning);       }
#admin-sidebar .admin-sidebar-group:nth-child(6)  { --grp-color: var(--ui-purple);        }
#admin-sidebar .admin-sidebar-group:nth-child(7)  { --grp-color: var(--ui-info-hover);    }
#admin-sidebar .admin-sidebar-group:nth-child(8)  { --grp-color: var(--ui-neutral);       }
#admin-sidebar .admin-sidebar-group:nth-child(9)  { --grp-color: var(--ui-warning-hover); }
#admin-sidebar .admin-sidebar-group:nth-child(10) { --grp-color: var(--ui-purple-hover);  }

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
    color: var(--admin-sidebar-text) !important;
}
#admin-sidebar .admin-sidebar-group .sidebar-link.active {
    background: color-mix(in srgb, var(--grp-color) 28%, transparent) !important;
    color: var(--admin-sidebar-text) !important;
    box-shadow: inset -3px 0 0 var(--grp-color) !important;
}
#admin-sidebar .admin-sidebar-group .sidebar-link:hover svg,
#admin-sidebar .admin-sidebar-group .sidebar-link.active svg {
    color: var(--grp-color) !important;
}

/* ═══ رنگ‌بندی گروه‌های پنل کاربر (account-sidebar) ═══ */
#account-sidebar .mb-4 { --grp-color: var(--admin-sidebar-accent); }
#account-sidebar .mb-4:nth-child(1) { --grp-color: var(--ui-indigo);   }
#account-sidebar .mb-4:nth-child(2) { --grp-color: var(--ui-success);  }
#account-sidebar .mb-4:nth-child(3) { --grp-color: var(--ui-info);     }
#account-sidebar .mb-4:nth-child(4) { --grp-color: var(--ui-purple);   }

#account-sidebar .mb-4 > div.px-3.py-2.text-xs {
    color: var(--grp-color) !important;
    border-bottom-color: color-mix(in srgb, var(--grp-color) 30%, transparent) !important;
    font-weight: 700 !important;
}
#account-sidebar .mb-4 .account-sidebar-link:hover {
    background: color-mix(in srgb, var(--grp-color) 18%, transparent) !important;
    color: var(--admin-sidebar-text) !important;
}
#account-sidebar .mb-4 .account-sidebar-link.active {
    background: color-mix(in srgb, var(--grp-color) 28%, transparent) !important;
    color: var(--admin-sidebar-text) !important;
    box-shadow: inset -3px 0 0 var(--grp-color) !important;
}

/* ═══════════════════════════════════════════════════════
   هم‌ترازی کامل با منوی کناری پلتفرم اصلی
   ═══════════════════════════════════════════════════════ */

/* گروه‌ها: بدون فاصله زیرین اضافی */
#admin-sidebar .admin-sidebar-group {
    margin-bottom: 0 !important;
}

/* دکمه گروه — طراحی مشابه پلتفرم: border-right رنگی + تینت */
#admin-sidebar .sidebar-group-toggle {
    border: none !important;
    border-right: 3px solid color-mix(in srgb, var(--grp-color) 60%, transparent) !important;
    background: color-mix(in srgb, var(--grp-color) 8%, transparent) !important;
    padding: 0.35rem 0.75rem 0.35rem 0.625rem !important;
    margin: 0.3rem 0 0.08rem 0 !important;
    border-radius: 0 0.5rem 0.5rem 0 !important;
    transition: background 0.18s ease, border-color 0.18s ease !important;
}
#admin-sidebar .sidebar-group-toggle:hover {
    background: color-mix(in srgb, var(--grp-color) 16%, transparent) !important;
    border-right-color: var(--grp-color) !important;
}
#admin-sidebar .sidebar-group-toggle[aria-expanded="true"] {
    background: color-mix(in srgb, var(--grp-color) 12%, transparent) !important;
    border-right-color: var(--grp-color) !important;
}

/* لیبل گروه — 0.82rem، بدون uppercase، رنگ grp-color */
#admin-sidebar .sidebar-group-label {
    font-size: 0.82rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.01em !important;
    text-transform: none !important;
    border-bottom: none !important;
    padding: 0 !important;
    margin: 0 !important;
    line-height: 1.4 !important;
}

/* فلش گروه — 0.9rem مشابه پلتفرم */
#admin-sidebar .sidebar-group-caret {
    width: 0.9rem !important;
    height: 0.9rem !important;
    min-width: 0.9rem !important;
    color: color-mix(in srgb, var(--grp-color) 70%, transparent) !important;
}
#admin-sidebar .sidebar-group-caret.is-open {
    color: var(--grp-color) !important;
}

/* لیست آیتم‌ها — gap و padding مشابه پلتفرم */
#admin-sidebar .admin-sidebar-item-list {
    gap: 1px !important;
    padding: 0.15rem 0 0.3rem 0 !important;
    padding-right: 0 !important;
}

/* لینک‌های منو — padding و border-radius مشابه پلتفرم */
#admin-sidebar a.sidebar-link,
#admin-sidebar .sidebar-link {
    padding: 0.42rem 0.875rem !important;
    gap: 0.6rem !important;
    border-radius: 0.5rem !important;
    margin-bottom: 0 !important;
    transform: none !important;
    transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease !important;
}
#admin-sidebar a.sidebar-link:hover,
#admin-sidebar .sidebar-link:hover {
    transform: none !important;
}

/* آیکن SVG لینک‌ها — 1.1rem مشابه پلتفرم */
#admin-sidebar .sidebar-link svg {
    width: 1.1rem !important;
    height: 1.1rem !important;
    min-width: 1.1rem !important;
    transform: none !important;
    transition: color 0.15s ease !important;
}
#admin-sidebar .sidebar-link:hover svg {
    transform: none !important;
}

/* آیکن گروه‌های منو */
#admin-sidebar .sidebar-group-icon {
    width: 1rem !important;
    height: 1rem !important;
    min-width: 1rem !important;
    flex-shrink: 0 !important;
    color: var(--grp-color) !important;
    opacity: 0.85;
    margin-left: 0.125rem;
}
#admin-sidebar .sidebar-group-toggle[aria-expanded="true"] .sidebar-group-icon {
    opacity: 1;
}

/* margin اضافه روی آیکن آیتم‌ها حذف می‌شود (gap در flexbox کافی است) */
#admin-sidebar .sidebar-link > svg.inline-block,
#admin-sidebar .sidebar-link > .inline-block.shrink-0 {
    margin: 0 !important;
}

/* ══════════════════════════════════════
   حالت جمع‌شده (collapsed sidebar)
   ══════════════════════════════════════ */

/* گروه toggle در حالت collapse: فقط آیکن، مرکزچین */
#admin-sidebar[data-collapsed="true"] .sidebar-group-toggle {
    justify-content: center !important;
    padding: 0.45rem !important;
    border-right: none !important;
    background: transparent !important;
    border-radius: 0.5rem !important;
    margin: 0.15rem 0 !important;
}
#admin-sidebar[data-collapsed="true"] .sidebar-group-toggle:hover {
    background: color-mix(in srgb, var(--grp-color) 18%, transparent) !important;
    border-right: none !important;
}
#admin-sidebar[data-collapsed="true"] .sidebar-group-icon {
    width: 1.2rem !important;
    height: 1.2rem !important;
    min-width: 1.2rem !important;
    opacity: 1 !important;
    color: var(--grp-color) !important;
}
/* آیتم‌های داخل گروه باز — icon-only */
#admin-sidebar[data-collapsed="true"] .sidebar-group-items:not(.is-collapsed) {
    display: flex !important;
    flex-direction: column !important;
    gap: 2px !important;
    padding: 0.1rem 0 0.15rem !important;
}
#admin-sidebar[data-collapsed="true"] a.sidebar-link,
#admin-sidebar[data-collapsed="true"] .sidebar-link {
    justify-content: center !important;
    padding: 0.45rem !important;
    gap: 0 !important;
}
#admin-sidebar[data-collapsed="true"] .sidebar-link svg {
    width: 1.2rem !important;
    height: 1.2rem !important;
    min-width: 1.2rem !important;
    margin: 0 !important;
}
</style>

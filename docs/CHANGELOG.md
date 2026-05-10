# Changelog

تمام ورودی‌ها به‌صورت معکوس زمانی (جدیدترین بالا). فقط هنگام تغییر واقعی روی ماژول‌ها/قابلیت‌ها به‌روز شود.

## [Unreleased]

- `global-rules.mdc`: بخش‌های ۱۴–۱۷ و §۱۸ Laravel (هم‌محتوا با saas-platform).
- قانون سراسری Cursor (`global-rules.mdc`): توسعهٔ امن، لایسنس، entitlement، انتشار قابلیت، migration و تست (هم‌محتوا با saas-platform).
- همگام‌سازی قوانین Cursor: مسیر پلتفرم قابل پیکربندی (`CURSOR_RULES_SAAS_PLATFORM_ROOT`، `cursor-rules-sync.local.json`، نمونه `cursor-rules-sync.local.example.json`)؛ به‌روزرسانی مستند همگام‌سازی.
- همگام‌سازی قوانین Cursor: wrapper `scripts/sync-cursor-rules.ps1`، Composer (`sync-cursor-rules` / `sync-cursor-rules:reverse`)، `.githooks/post-commit` و `docs/CURSOR-RULES-SYNC.md`.
- همسان‌سازی محتوای `.cursor/rules/*.mdc` بین **saas-platform** و **customer-site** (شامل `persian-date-only`, `project-guide-update`, `site-modules-from-platform`, `saas-platform-main-admin`, `saas-module-guide-update`, `content-modules-jalali-editor`, `global-rules`).
- قانون Cursor «Billing and Payment»: `globs` به مسیرهای Laravel (سرویس پرداخت، مدل‌های فاکتور/درگاه) هم‌راستا شد.
- راه‌اندازی مستندات ماژول‌ها (`docs/modules/`) و پرامپت استاندارد (`docs/PROMPT_FOR_CURSOR.md`).

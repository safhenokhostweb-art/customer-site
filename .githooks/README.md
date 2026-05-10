# Git hooks (همگام‌سازی قوانین Cursor)

یک‌بار در این مخزن اجرا کنید:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/setup-cursor-rules-hook.ps1
```

یا دستی:

```bash
git config core.hooksPath .githooks
```

پس از آن، اگر در **همین** مخزن قوانین `.cursor/rules/*.mdc` را commit کنید، آن‌ها به **saas-platform** کپی می‌شوند (`-Reverse`). برای گرفتن آخرین قوانین از پلتفرم بدون commit: `composer sync-cursor-rules`.

# همگام‌سازی قوانین Cursor بین پلتفرم و سایت مشتری

سایت مشتری ممکن است روی **هاست یا ماشین دیگر** باشد؛ مخزن‌ها لزوماً کنار هم نیستند. مسیر مخزن پلتفرم را با متغیر محیطی یا `cursor-rules-sync.local.json` تعیین کنید.

## ترتیب تشخیص مسیر saas-platform (برای اجرای اسکریپت اصلی)

1. **`CURSOR_RULES_SAAS_PLATFORM_ROOT`**
2. **`cursor-rules-sync.local.json`** در ریشهٔ این مخزن، کلید `saasPlatformRoot`
3. پیش‌فرض: **`../saas-platform`**

نمونه: `cursor-rules-sync.local.example.json` → کپی به `cursor-rules-sync.local.json` (gitignored).

## گرفتن قوانین از پلتفرم

```powershell
composer sync-cursor-rules
```

## فرستادن قوانین این مخزن به پلتفرم

```powershell
composer sync-cursor-rules:reverse
```

## Git hook

```powershell
powershell -ExecutionPolicy Bypass -File scripts/setup-cursor-rules-hook.ps1
```

پس از commit روی `.cursor/rules/*.mdc`، hook تغییر را به مخزن پلتفرم می‌فرستد (اگر مسیر saas حل‌شده باشد).

## دو سرور جدا

اگر دو مسیر روی یک دیسک در دسترس نیستند، قوانین را در pipeline یا با ابزار deploy بین repoها/سرورها کپی کنید؛ جزئیات در همان فایل در مخزن **saas-platform** (`docs/CURSOR-RULES-SYNC.md`) آمده است.

## مستندات مرتبط

- `docs/PROMPT_FOR_CURSOR.md`
- `docs/CHANGELOG.md`

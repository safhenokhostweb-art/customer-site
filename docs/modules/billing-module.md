# Billing & Payment (customer-site)

## Current Features

- مدل‌های `Invoice` / `InvoiceItem` (در صورت استفاده در این سایت)
- فوروارد callback پرداخت به پلتفرم در صورت نیاز (`PaymentCallbackController`)

## Architecture

- بیشتر منطق پرداخت و فاکتور روی **saas-platform** است؛ این مخزن مصرف‌کننده/پاس‌ترو است.

## Last Changes

- ۱۴۰۵/۰۲/۱۹: هم‌راستایی با قانون Cursor `billing-module.mdc`.

## Technical Debt

- پوشهٔ `app/Services/Payment/**/*` در این مخزن ممکن است خالی باشد؛ قانون برای همسان‌سازی با پلتفرم نگه داشته شده است.

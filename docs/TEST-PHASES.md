# تست‌های مراحل ۱، ۲ و ۳

برای اجرای تست‌ها پس از اعمال مایگریشن‌ها:

```bash
# customer-site
cd c:\laragon\www\customer-site
php artisan migrate --force
php artisan test tests/Feature/PhaseOneTwoThreeTest.php

# saas-platform
cd c:\laragon\www\saas-platform
php artisan migrate --force
php artisan test tests/Feature/PhaseOneTwoThreeSaasTest.php
```

## تست‌های customer-site (PhaseOneTwoThreeTest)

| تست | توضیح |
|-----|-------|
| test_shop_index_with_category_filter | فیلتر دسته‌بندی در فروشگاه |
| test_shop_search | جستجو در محصولات |
| test_blog_search | جستجو در وبلاگ |
| test_about_us_block_shows_when_enabled | نمایش بلوک درباره ما |
| test_about_us_block_hidden_when_disabled | مخفی بودن بلوک وقتی غیرفعال است |
| test_theme_class_applied_to_body | کلاس تم روی body |
| test_manifest_exists | وجود manifest.json برای PWA |
| test_home_has_loading_script_on_forms | اسکریپت loading روی فرم‌ها |

## تست‌های saas-platform (PhaseOneTwoThreeSaasTest)

| تست | توضیح |
|-----|-------|
| test_support_plan_crud | CRUD پلن‌های پشتیبانی |
| test_payout_request_index_accessible | دسترسی به لیست درخواست‌های تسویه |
| test_support_tickets_index_accessible | دسترسی به لیست تیکت‌های پشتیبانی |

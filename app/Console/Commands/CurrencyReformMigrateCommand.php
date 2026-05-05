<?php

namespace App\Console\Commands;

use App\Helpers\CurrencyHelper;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Console\Command;

/**
 * تبدیل قیمت‌های محصولات به واحد جدید (حذف ۴ صفر).
 * فقط زمانی اجرا شود که ماژول currency_reform فعال است و هنوز مایگریشن انجام نشده.
 */
class CurrencyReformMigrateCommand extends Command
{
    protected $signature = 'currency:reform-migrate {--force : اجرا حتی اگر قبلاً انجام شده}';

    protected $description = 'تبدیل قیمت‌های محصولات به واحد جدید (تقسیم بر ۱۰٬۰۰۰)';

    public function handle(): int
    {
        if (!CurrencyHelper::isReformEnabled()) {
            $this->error('ماژول حذف ۴ صفر پول ملی فعال نیست. ابتدا آن را در پنل ادمین پلتفرم برای این سایت فعال کنید.');
            return 1;
        }

        if (CurrencyHelper::isMigrated() && !$this->option('force')) {
            $this->warn('تبدیل قیمت‌ها قبلاً انجام شده است. برای اجرای مجدد از گزینه --force استفاده کنید.');
            return 0;
        }

        $count = Product::count();
        if ($count === 0) {
            $this->info('محصولی برای تبدیل وجود ندارد.');
            Setting::set('currency_reform_migrated', '1', 'currency');
            return 0;
        }

        if (!$this->option('force') && !$this->confirm("{$count} محصول تبدیل خواهد شد. ادامه می‌دهید؟", true)) {
            return 0;
        }

        $updated = 0;
        Product::chunk(100, function ($products) use (&$updated) {
            foreach ($products as $p) {
                $changed = false;
                if ($p->price > 0) {
                    $p->price = round($p->price / CurrencyHelper::DIVISOR, 2);
                    $changed = true;
                }
                if ($p->sale_price !== null && $p->sale_price > 0) {
                    $p->sale_price = round($p->sale_price / CurrencyHelper::DIVISOR, 2);
                    $changed = true;
                }
                if ($changed) {
                    $p->saveQuietly();
                    $updated++;
                }
            }
        });

        Setting::set('currency_reform_migrated', '1', 'currency');
        \Illuminate\Support\Facades\Cache::forget('settings');

        $this->info("تبدیل انجام شد. {$updated} محصول به‌روزرسانی شد.");
        return 0;
    }
}

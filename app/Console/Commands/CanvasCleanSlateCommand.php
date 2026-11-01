<?php

namespace App\Console\Commands;

use App\Services\ThemeCanvas\CanvasCleanSlate;
use App\Support\DataPurge\TablePurger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Removes the site's Canvas theme state, builder pages, templates and menus —
 * after writing a verified snapshot that --restore can put back — then
 * installs the empty canvas baseline. See CanvasCleanSlate for the scope.
 *
 *   php artisan canvas:clean-slate --dry-run
 *   php artisan canvas:clean-slate --force
 *   php artisan canvas:clean-slate --restore=storage/app/clean-slate/<dir> --force
 */
class CanvasCleanSlateCommand extends Command
{
    protected $signature = 'canvas:clean-slate
                            {--dry-run : List the rows that would be removed, change nothing}
                            {--force : Run without the confirmation prompt (required when not interactive)}
                            {--confirm-database= : Required in production: the name of the database being changed}
                            {--no-baseline : Leave the site with no active theme instead of the empty canvas}
                            {--restore= : Put back a snapshot directory written by an earlier run}';

    protected $description = 'Snapshot, then remove all Canvas theme data, builder pages, templates and menus, and install the empty canvas';

    public function handle(CanvasCleanSlate $cleanSlate): int
    {
        $purger = CanvasCleanSlate::purger();
        $restoreDir = $this->option('restore');

        $plan = $purger->plan();
        $this->table(['جدول', 'ردیف‌های فعلی'], collect($plan)->map(fn ($n, $t) => [$t, $n])->values()->all());

        if ($this->option('dry-run')) {
            $this->info('[dry-run] هیچ تغییری اعمال نشد.');

            return self::SUCCESS;
        }

        if (!$this->authorized($restoreDir ? 'بازگردانی snapshot' : 'حذف همهٔ داده‌های قالب، صفحات و منوها')) {
            return self::FAILURE;
        }

        try {
            return $restoreDir ? $this->restore($purger, $restoreDir) : $this->purge($purger, $cleanSlate);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            if ($restoreDir) {
                $this->line('اگر بوم خالی نصب است، ابتدا php artisan canvas:clean-slate --no-baseline --force را اجرا کنید.');
            }

            return self::FAILURE;
        } finally {
            CanvasCleanSlate::forgetCaches();
        }
    }

    private function purge(TablePurger $purger, CanvasCleanSlate $cleanSlate): int
    {
        $dir = storage_path('app/clean-slate/'.CanvasCleanSlate::KIND.'-'.now()->format('Ymd\THis'));
        $manifest = $purger->snapshot($dir);
        $this->info("snapshot ثبت و بازخوانی شد: {$dir} (".array_sum(array_column($manifest['entries'], 'rows')).' ردیف)');

        $result = $purger->purge($dir);
        CanvasCleanSlate::forgetCaches();
        $this->table(['جدول', 'حذف شد'], collect($result['removed'])->map(fn ($n, $t) => [$t, $n])->values()->all());

        if ($result['ids_reset'] !== []) {
            $this->line('شمارندهٔ شناسه بازنشانی شد: '.implode(', ', $result['ids_reset']));
        }
        if ($result['ids_reset_skipped']) {
            $this->warn('بازنشانی شناسه‌ها رد شد: فرمان داخل یک تراکنش باز اجرا شد.');
        }

        if (!$this->option('no-baseline')) {
            $revision = $cleanSlate->installBaseline();
            $this->info('بوم خالی نصب شد'.($revision ? " (نسخهٔ #{$revision->id})." : '.'));
        }

        $this->line("برای بازگردانی: php artisan canvas:clean-slate --no-baseline --force و سپس --restore={$dir} --force");

        return self::SUCCESS;
    }

    private function restore(TablePurger $purger, string $dir): int
    {
        $dir = is_dir($dir) ? $dir : base_path($dir);
        $restored = $purger->restore($dir);
        $this->table(['جدول', 'بازگردانده شد'], collect($restored)->map(fn ($n, $t) => [$t, $n])->values()->all());

        return self::SUCCESS;
    }

    private function authorized(string $action): bool
    {
        $database = DB::connection()->getDatabaseName();

        if (app()->environment('production') && $this->option('confirm-database') !== $database) {
            $this->error("در production باید نام پایگاه داده را تأیید کنید: --confirm-database={$database}");

            return false;
        }

        if ($this->option('force')) {
            return true;
        }

        if (!$this->input->isInteractive()) {
            $this->error('اجرای غیرتعاملی بدون --force مجاز نیست.');

            return false;
        }

        if (!$this->confirm("{$action} روی پایگاه داده «{$database}» انجام شود؟ (ابتدا --dry-run را اجرا کنید)")) {
            $this->line('لغو شد.');

            return false;
        }

        return true;
    }
}

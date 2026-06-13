<?php

namespace App\Console\Commands;

use App\Services\ContentTranslationService;
use Illuminate\Console\Command;

class TranslateQueuedContentCommand extends Command
{
    protected $signature   = 'translations:process {--batch=20 : تعداد آیتم در هر اجرا}';
    protected $description = 'ترجمه محتواهای در صف با هوش مصنوعی';

    public function handle(ContentTranslationService $service): int
    {
        $batch = (int) $this->option('batch');
        $this->info("ترجمه {$batch} آیتم از صف...");

        $stats = $service->processQueue($batch);

        $this->table(
            ['پردازش‌شده', 'ناموفق', 'رد‌شده'],
            [[$stats['processed'], $stats['failed'], $stats['skipped']]]
        );

        return self::SUCCESS;
    }
}

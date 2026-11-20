<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackupDatabaseCommand extends Command
{
    /**
     * Local copies are a short safety net on the site's own disk; the
     * durable, offsite copies are pulled by the platform (db:backup-sites)
     * into its private bucket. The site never holds a backup-bucket key.
     */
    public const MAX_LOCAL_RETENTION = 2;

    protected $signature = 'db:backup
                            {--retention=2 : تعداد نسخه‌های محلی (حداکثر ۲)}
                            {--no-compress : بدون فشرده‌سازی gzip}
                            {--dry-run : نمایش مسیر بدون اجرا}';

    protected $description = 'پشتیبان‌گیری خودکار MySQL — dump + gzip + retention + integrity check';

    public function handle(): int
    {
        $dryRun    = $this->option('dry-run');
        // Capped whatever the schedule or DB_BACKUP_RETENTION asks for.
        $retention = min(self::MAX_LOCAL_RETENTION, max(1, (int) ($this->option('retention') ?: config('deployment.database_backup_retention', self::MAX_LOCAL_RETENTION))));
        $compress  = ! $this->option('no-compress');

        $backupDir = storage_path('backups');
        $this->ensureDir($backupDir);

        $timestamp = now()->format('Y-m-d_H-i-s');
        $extension = $compress ? 'sql.gz' : 'sql';
        $filename  = "backup_{$timestamp}.{$extension}";
        $filepath  = $backupDir . DIRECTORY_SEPARATOR . $filename;

        if ($dryRun) {
            $this->info("[dry-run] مسیر فایل: {$filepath}");
            $this->info("[dry-run] retention: {$retention} نسخه");
            return self::SUCCESS;
        }

        $this->info("شروع پشتیبان‌گیری: {$filename}");

        // ── integrity snapshot (قبل از backup) ──────────────────────────────
        $rowsBefore = $this->totalRowCount();

        // ── اجرای mysqldump ──────────────────────────────────────────────────
        $exitCode = $this->runDump($filepath, $compress);

        if ($exitCode !== 0) {
            $this->error("mysqldump با خطا متوقف شد (exit: {$exitCode}).");
            Log::error('db:backup failed', ['exit' => $exitCode, 'file' => $filepath]);
            return self::FAILURE;
        }

        if (! file_exists($filepath) || filesize($filepath) < 100) {
            $this->error('فایل backup خالی یا ناقص است.');
            Log::error('db:backup: empty file', ['file' => $filepath]);
            @unlink($filepath);
            return self::FAILURE;
        }

        $size = $this->humanSize(filesize($filepath));
        $this->info("فایل ایجاد شد: {$size}");

        // ── integrity check (بعد از backup) ─────────────────────────────────
        $rowsAfter = $this->totalRowCount();

        if ($rowsBefore !== $rowsAfter) {
            $this->warn("هشدار: تعداد سطرها حین backup تغییر کرد ({$rowsBefore} → {$rowsAfter}).");
            Log::warning('db:backup: row count changed during backup', [
                'before' => $rowsBefore,
                'after'  => $rowsAfter,
                'file'   => $filename,
            ]);
        } else {
            $this->info("Integrity OK — {$rowsBefore} سطر.");
        }

        // ── retention: حذف نسخه‌های قدیمی ───────────────────────────────────
        $deleted = $this->applyRetention($backupDir, $retention);
        if ($deleted > 0) {
            $this->info("{$deleted} فایل قدیمی حذف شد.");
        }

        Log::info('db:backup completed', [
            'file'        => $filename,
            'size'        => $size,
            'rows'        => $rowsAfter,
            'retention'   => $retention,
            'pruned'      => $deleted,
        ]);

        $this->info('پشتیبان‌گیری با موفقیت انجام شد.');

        // No offsite upload from here (Phase 2): the platform pulls a dump
        // over SSH into its private bucket, so this site needs no bucket key.

        return self::SUCCESS;
    }

    // ── private ──────────────────────────────────────────────────────────────

    private function runDump(string $filepath, bool $compress): int
    {
        $mysqldump = $this->mysqldumpBin();
        $host      = escapeshellarg(config('database.connections.mysql.host', '127.0.0.1'));
        $port      = (int) config('database.connections.mysql.port', 3306);
        $user      = escapeshellarg(config('database.connections.mysql.username'));
        $pass      = config('database.connections.mysql.password', '');
        $db        = escapeshellarg(config('database.connections.mysql.database'));

        $passArg = $pass !== '' ? '-p' . escapeshellarg($pass) : '';
        $dumpFile = escapeshellarg($filepath);

        if ($compress) {
            // piped through gzip
            $cmd = "\"{$mysqldump}\" --host={$host} --port={$port} --user={$user} {$passArg}"
                 . " --single-transaction --quick --lock-tables=false"
                 . " --routines --triggers"
                 . " {$db} | gzip > {$dumpFile}";
        } else {
            $cmd = "\"{$mysqldump}\" --host={$host} --port={$port} --user={$user} {$passArg}"
                 . " --single-transaction --quick --lock-tables=false"
                 . " --routines --triggers"
                 . " {$db} > {$dumpFile}";
        }

        exec($cmd . ' 2>&1', $output, $exitCode);

        if ($exitCode !== 0 && ! empty($output)) {
            Log::warning('db:backup mysqldump output', ['lines' => array_slice($output, 0, 5)]);
        }

        return $exitCode;
    }

    private function mysqldumpBin(): string
    {
        $fromEnv = config('deployment.mysqldump_path');
        if ($fromEnv && file_exists($fromEnv)) {
            return $fromEnv;
        }

        // تلاش برای یافتن mysqldump در PATH
        $which = PHP_OS_FAMILY === 'Windows' ? 'where mysqldump' : 'which mysqldump';
        exec($which . ' 2>&1', $out, $code);

        if ($code === 0 && ! empty($out[0])) {
            return trim($out[0]);
        }

        // مسیر پیش‌فرض Laragon روی Windows
        $laragon = 'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe';
        if (file_exists($laragon)) {
            return $laragon;
        }

        // Fallback: امیدوار باشیم PATH درست باشد
        return 'mysqldump';
    }

    private function totalRowCount(): int
    {
        try {
            $rows = DB::select(
                "SELECT IFNULL(SUM(TABLE_ROWS), 0) AS total
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = ?
                   AND TABLE_TYPE = 'BASE TABLE'",
                [config('database.connections.mysql.database')]
            );
            return (int) ($rows[0]->total ?? 0);
        } catch (\Throwable) {
            return -1;
        }
    }

    private function applyRetention(string $dir, int $keep): int
    {
        $files = glob($dir . DIRECTORY_SEPARATOR . 'backup_*.sql*');
        if (! $files) {
            return 0;
        }

        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));

        $toDelete = array_slice($files, $keep);
        foreach ($toDelete as $f) {
            @unlink($f);
        }

        return count($toDelete);
    }

    private function ensureDir(string $dir): void
    {
        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}

<?php

namespace Tests\Feature;

use App\Helpers\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Tests\TestCase;

/**
 * 8.3b — BackupDatabaseCommand: retention, dry-run, directory creation.
 * هیچ mysqldump واقعی اجرا نمی‌شود — رفتار command پوشش داده می‌شود.
 */
class BackupDatabaseCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');

        $ref  = new ReflectionClass(Module::class);
        $prop = $ref->getProperty('resolved');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $this->backupDir = storage_path('backups');
    }

    protected function tearDown(): void
    {
        // پاک‌سازی فایل‌های تست
        if (is_dir($this->backupDir)) {
            foreach (glob($this->backupDir . '/backup_test_*.sql*') as $f) {
                @unlink($f);
            }
        }
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    /** @test */
    public function dry_run_does_not_create_backup_file(): void
    {
        $before = $this->backupFileCount();

        $this->artisan('db:backup', ['--dry-run' => true])
             ->assertSuccessful();

        $this->assertEquals($before, $this->backupFileCount());
    }

    /** @test */
    public function dry_run_outputs_file_path(): void
    {
        $this->artisan('db:backup', ['--dry-run' => true])
             ->expectsOutputToContain('[dry-run]')
             ->assertSuccessful();
    }

    /** @test */
    public function backup_dir_is_created_if_missing(): void
    {
        // حذف موقت دایرکتوری backup برای تست
        if (is_dir($this->backupDir)) {
            $files = glob($this->backupDir . '/*');
            foreach ($files as $f) { @unlink($f); }
            @rmdir($this->backupDir);
        }

        $this->assertDirectoryDoesNotExist($this->backupDir);

        // dry-run تضمین می‌کند که دایرکتوری ساخته می‌شود
        $this->artisan('db:backup', ['--dry-run' => true])->assertSuccessful();

        $this->assertDirectoryExists($this->backupDir);
    }

    /** @test */
    public function retention_option_is_accepted_up_to_the_local_cap(): void
    {
        $this->artisan('db:backup', ['--dry-run' => true, '--retention' => 1])
             ->expectsOutputToContain('retention: 1')
             ->assertSuccessful();
    }

    /** @test */
    public function local_retention_is_capped_at_two_whatever_is_requested(): void
    {
        // The schedule still passes DB_BACKUP_RETENTION (7): only 2 stay on disk.
        $this->artisan('db:backup', ['--dry-run' => true, '--retention' => 7])
             ->expectsOutputToContain('retention: 2')
             ->assertSuccessful();
        $this->assertSame(2, \App\Console\Commands\BackupDatabaseCommand::MAX_LOCAL_RETENTION);
    }

    /** @test */
    public function the_site_never_uploads_backups_to_object_storage_itself(): void
    {
        // Phase 2: the platform pulls dumps over SSH; no bucket key on the site.
        $source = file_get_contents((new \ReflectionClass(\App\Console\Commands\BackupDatabaseCommand::class))->getFileName());
        $this->assertStringNotContainsString('arvan_backup', $source);
        $this->assertStringNotContainsString('Storage::disk', $source);
    }

    /** @test */
    public function retention_enforces_minimum_of_one(): void
    {
        // retention=0 باید به 1 تبدیل شود (نه حذف همه)
        $this->artisan('db:backup', ['--dry-run' => true, '--retention' => 0])
             ->assertSuccessful();
    }

    /** @test */
    public function apply_retention_removes_oldest_files(): void
    {
        $this->ensureDir($this->backupDir);

        // ساخت ۵ فایل تست با timestamps مختلف
        for ($i = 1; $i <= 5; $i++) {
            $path = $this->backupDir . "/backup_test_{$i}.sql";
            file_put_contents($path, str_repeat('x', 100 * $i));
            touch($path, time() - (6 - $i) * 3600); // قدیمی‌ترها ابتدا
        }

        $this->assertEquals(5, count(glob($this->backupDir . '/backup_test_*.sql')));

        // فراخوانی مستقیم applyRetention با retention=3
        $cmd = new \App\Console\Commands\BackupDatabaseCommand();
        $ref = new \ReflectionClass($cmd);
        $method = $ref->getMethod('applyRetention');
        $method->setAccessible(true);

        $deleted = $method->invoke($cmd, $this->backupDir, 3);

        // باید ۲ فایل حذف شوند (5 - 3 = 2)
        $remaining = glob($this->backupDir . '/backup_test_*.sql');
        $this->assertEquals(2, $deleted);
        $this->assertCount(3, $remaining);
    }

    /** @test */
    public function human_size_formats_bytes_correctly(): void
    {
        $cmd = new \App\Console\Commands\BackupDatabaseCommand();
        $ref = new \ReflectionClass($cmd);
        $method = $ref->getMethod('humanSize');
        $method->setAccessible(true);

        $this->assertStringContainsString('B',  $method->invoke($cmd, 500));
        $this->assertStringContainsString('KB', $method->invoke($cmd, 2048));
        $this->assertStringContainsString('MB', $method->invoke($cmd, 2 * 1024 * 1024));
    }

    /** @test */
    public function command_is_registered_in_artisan(): void
    {
        $this->artisan('db:backup --help')
             ->assertSuccessful();
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function backupFileCount(): int
    {
        if (! is_dir($this->backupDir)) {
            return 0;
        }
        return count(glob($this->backupDir . '/backup_*.sql*') ?: []);
    }

    private function ensureDir(string $dir): void
    {
        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\CustomerMedia;
use App\Services\MediaStorage;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Phase 4 — move this site's customer media from the local disks to the
 * object-storage media disk (MEDIA_DISK, e.g. "arvan").
 *
 * Per file: copy under the same storage_key, verify the remote copy by size
 * AND SHA-256 (read back from the bucket), switch the row's `disk`, and only
 * then delete the local copy. A file that fails any step keeps its local
 * copy and its row untouched. Canvas content references media by UUID
 * (CustomerMediaResolver), so URLs follow the row's disk automatically.
 *
 * Dry run unless --execute is given.
 */
class MigrateMediaToRemoteCommand extends Command
{
    private const LOCAL_DISKS = ['uploads', 'public', 'local'];

    protected $signature = 'media:migrate-to-remote
                            {--site= : PLATFORM_SITE_ID of this site (required; guards against running on the wrong site)}
                            {--execute : Copy, verify, switch and delete the local copy}
                            {--keep-local : With --execute, keep the local copy after a verified switch}
                            {--limit=0 : Stop after this many files (0 = all)}';

    protected $description = 'Move this site\'s customer media from local disk to object storage, verified by size and SHA-256';

    public function handle(MediaStorage $storage): int
    {
        $siteId = (int) config('site.platform_site_id', 0);
        $requested = (int) $this->option('site');
        if ($requested <= 0 || $requested !== $siteId) {
            $this->error("--site must be this site's PLATFORM_SITE_ID ({$siteId}).");

            return self::FAILURE;
        }

        $target = $storage->diskNameFor('uploads');
        if (in_array($target, self::LOCAL_DISKS, true) || (config("filesystems.disks.{$target}.driver") === 'local')) {
            $this->error("MEDIA_DISK resolves to the local disk \"{$target}\"; set MEDIA_DISK=arvan (and its ARVAN_* variables) first.");

            return self::FAILURE;
        }

        $execute = (bool) $this->option('execute');
        $keepLocal = (bool) $this->option('keep-local');
        $limit = max(0, (int) $this->option('limit'));
        $this->info(($execute ? 'EXECUTE' : 'DRY RUN') . " — site {$siteId}: local disks → {$target}");

        $query = CustomerMedia::query()->where('site_id', $siteId)->whereIn('disk', self::LOCAL_DISKS)->orderBy('id');
        $stats = ['candidates' => 0, 'bytes' => 0, 'moved' => 0, 'missing' => 0, 'failed' => 0, 'local_kept' => 0];
        $remote = Storage::disk($target);

        foreach ($query->lazyById(200) as $media) {
            if ($limit > 0 && $stats['candidates'] >= $limit) {
                break;
            }
            $stats['candidates']++;
            $stats['bytes'] += (int) $media->size_bytes;

            if (! $execute) {
                $this->line("  would move #{$media->id} {$media->disk}:{$media->storage_key} (" . number_format((int) $media->size_bytes) . ' B)');
                continue;
            }

            $outcome = $this->migrate($media, Storage::disk($media->disk), $remote, $target, $keepLocal);
            $stats[$outcome]++;
            if ($outcome === 'moved' && $keepLocal) {
                $stats['local_kept']++;
            }
        }

        $this->table(array_keys($stats), [array_values($stats)]);
        if (! $execute) {
            $this->info('Nothing was changed; re-run with --execute.');
        } elseif ($stats['moved'] > 0) {
            $this->info('Run `php artisan cache:clear` so cached pages pick up the new media URLs.');
        }

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** @return 'moved'|'missing'|'failed' */
    private function migrate(CustomerMedia $media, Filesystem $local, Filesystem $remote, string $target, bool $keepLocal): string
    {
        $key = $media->storage_key;

        try {
            if (! $local->exists($key)) {
                $this->warn("  missing locally: #{$media->id} {$key} (row left as is)");

                return 'missing';
            }

            $localSize = (int) $local->size($key);
            $localHash = $this->sha256($local, $key);
            $alreadyThere = $remote->exists($key) && (int) $remote->size($key) === $localSize && $this->sha256($remote, $key) === $localHash;

            if (! $alreadyThere) {
                $stream = $local->readStream($key);
                try {
                    if (! is_resource($stream) || ! $remote->put($key, $stream, ['visibility' => 'public'])) {
                        throw new \RuntimeException('upload failed');
                    }
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
            }

            // Verify what the bucket actually holds before trusting it.
            if ((int) $remote->size($key) !== $localSize || $this->sha256($remote, $key) !== $localHash) {
                if (! $alreadyThere) {
                    $remote->delete($key);
                }
                throw new \RuntimeException('remote copy failed size/SHA-256 verification');
            }

            $media->forceFill(['disk' => $target])->save();

            if (! $keepLocal && ! $local->delete($key)) {
                $this->warn("  moved #{$media->id}, but the local copy could not be deleted: {$key}");
            }

            return 'moved';
        } catch (\Throwable $e) {
            Log::warning('media:migrate-to-remote failed', ['media_id' => $media->id, 'key' => $key, 'error' => $e->getMessage()]);
            $this->error("  failed #{$media->id} {$key}: {$e->getMessage()} (local copy kept)");

            return 'failed';
        }
    }

    private function sha256(Filesystem $disk, string $key): string
    {
        $stream = $disk->readStream($key);
        if (! is_resource($stream)) {
            throw new \RuntimeException("cannot read {$key}");
        }

        try {
            $ctx = hash_init('sha256');
            hash_update_stream($ctx, $stream);

            return hash_final($ctx);
        } finally {
            fclose($stream);
        }
    }
}

<?php

namespace App\Services;

use App\Models\CustomerMedia;
use App\Models\CustomerMediaUsage;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The only write authority for customer-owned public media.
 * Platform/theme-package assets, Motion assets, and support attachments are
 * intentionally outside this domain.
 */
class CustomerMediaService
{
    private const POLICY = [
        'image' => ['mimes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], 'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'max' => 10_485_760],
        'video' => ['mimes' => ['video/mp4', 'video/webm'], 'extensions' => ['mp4', 'webm'], 'max' => 104_857_600],
        'audio' => ['mimes' => ['audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/x-wav'], 'extensions' => ['mp3', 'ogg', 'wav'], 'max' => 52_428_800],
        'document' => ['mimes' => ['application/pdf'], 'extensions' => ['pdf'], 'max' => 26_214_400],
    ];

    public function __construct(private readonly MediaStorage $storage)
    {
    }

    public function siteId(): int
    {
        $siteId = (int) config('site.platform_site_id', 0);
        if ($siteId <= 0) {
            throw new \LogicException('Customer media requires PLATFORM_SITE_ID.');
        }
        return $siteId;
    }

    public function upload(UploadedFile $file, array $metadata = []): CustomerMedia
    {
        $siteId = $this->siteId();
        [$category, $extension, $mime] = $this->validate($file);
        $uuid = (string) Str::uuid();
        $disk = $this->storage->diskNameFor('uploads');
        $key = $this->remoteImportStorageKey($siteId, $uuid, $extension, $metadata);

        if (! Storage::disk($disk)->putFileAs(dirname($key), $file, basename($key), ['visibility' => 'public'])) {
            throw new \RuntimeException('Customer media storage write failed.');
        }

        try {
            return CustomerMedia::create([
                'site_id' => $siteId, 'uuid' => $uuid, 'disk' => $disk, 'storage_key' => $key,
                'original_filename' => $file->getClientOriginalName(), 'extension' => $extension,
                'category' => $category, 'mime_type' => $mime, 'size_bytes' => $file->getSize(),
                'title' => $metadata['title'] ?? null, 'alt_text' => $metadata['alt_text'] ?? null,
                'caption' => $metadata['caption'] ?? null,
                'width' => $category === 'image' ? ($this->imageDimensions($file)[0] ?? null) : null,
                'height' => $category === 'image' ? ($this->imageDimensions($file)[1] ?? null) : null,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($key);
            throw $exception;
        }
    }

    public function findForCurrentSite(string $uuid): CustomerMedia
    {
        return CustomerMedia::forSite($this->siteId())->available()->where('uuid', $uuid)->firstOrFail();
    }

    private const REMOTE_IMAGE_MIME_TO_EXTENSION = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp',
    ];

    private const REMOTE_VIDEO_MIME_TO_EXTENSION = [
        'video/webm' => 'webm', 'video/mp4' => 'mp4',
    ];

    /**
     * TASK 3/3 Phase 6.5 (BLD-21) — MASTER_THEME_MEDIA_LOCALIZATION_GAP fix.
     * The write half of ThemeMediaLocalizationService's canonical import:
     * takes bytes the caller has ALREADY fetched from an allowlisted,
     * platform-operator-controlled origin (this method performs no network
     * I/O itself and trusts no URL — that boundary lives entirely in
     * ThemeMediaLocalizationService, keeping this class's own invariant
     * ("the only write authority for customer-owned public media") intact:
     * every write still goes through this one method, exactly like upload().
     *
     * Real validation on the bytes themselves (never on a declared
     * Content-Type alone): size-capped at the same policy as a real image
     * upload, and getimagesizefromstring() must both succeed and report a
     * MIME this method itself allows.
     */
    public function importFromRemoteBytes(string $bytes, string $filename, string $declaredMime, array $metadata = []): CustomerMedia
    {
        if (strlen($bytes) > self::POLICY['image']['max']) {
            throw ValidationException::withMessages(['file' => 'Remote media exceeds the allowed size.']);
        }

        $probe = @getimagesizefromstring($bytes);
        if (!is_array($probe) || !isset(self::REMOTE_IMAGE_MIME_TO_EXTENSION[$probe['mime'] ?? ''])) {
            throw ValidationException::withMessages(['file' => 'Remote media is not a supported, verifiable image.']);
        }
        $mime = $probe['mime'];
        $extension = self::REMOTE_IMAGE_MIME_TO_EXTENSION[$mime];

        $siteId = $this->siteId();
        $uuid = (string) Str::uuid();
        $disk = $this->storage->diskNameFor('uploads');
        // Real, proven inconsistency (khab-shimana-tablet media-isolation
        // certification, 2026-09-21): this was the only one of the three
        // storage-key call sites (upload(), importVideoFromRemoteBytes(),
        // this method) still building its own flat
        // customer-media/{site}/{yyyy}/{mm}/{uuid}.ext path inline instead
        // of calling remoteImportStorageKey() — despite that method's own
        // docblock already claiming "Theme imports are physically
        // namespaced by site, theme and release" for exactly this path.
        // Since importFromRemoteBytes() is the one ThemeMediaLocalizationService
        // actually calls for every image (the overwhelming majority of real
        // theme media — video is the rare exception), every theme-sourced
        // image was silently exempt from the namespacing the other two
        // call sites already had. No behavior change for a plain customer
        // upload or an already-namespaced video import.
        $key = $this->remoteImportStorageKey($siteId, $uuid, $extension, $metadata);

        if (!Storage::disk($disk)->put($key, $bytes, ['visibility' => 'public'])) {
            throw new \RuntimeException('Customer media storage write failed.');
        }

        try {
            return CustomerMedia::create([
                'site_id' => $siteId, 'uuid' => $uuid, 'disk' => $disk, 'storage_key' => $key,
                'original_filename' => $filename !== '' ? $filename : ($uuid . '.' . $extension),
                'extension' => $extension, 'category' => 'image', 'mime_type' => $mime, 'size_bytes' => strlen($bytes),
                'title' => $metadata['title'] ?? null, 'alt_text' => $metadata['alt_text'] ?? null,
                'width' => (int) $probe[0], 'height' => (int) $probe[1],
                'metadata' => array_filter([
                    'source_url' => $metadata['source_url'] ?? null,
                    'theme_identifier' => $metadata['theme_identifier'] ?? null,
                    'release_namespace' => $metadata['release_namespace'] ?? null,
                ], static fn ($value) => $value !== null && $value !== ''),
            ]);
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($key);
            throw $exception;
        }
    }

    /**
     * Commercial Productization fase-4 — a real, confirmed gap found via
     * an actual local end-to-end theme install (not a mocked test): the
     * `video` block's `url` prop was already listed in
     * CustomerMediaResolver::MEDIA_PROPS as needing localization, and
     * `POLICY['video']` above already exists for the manual upload() path
     * — but the remote-import path (this class's ONLY entry point for
     * ThemeMediaLocalizationService) was image-only, so any theme with a
     * real local demo video (this theme's own hero-intro.webm) could never
     * actually be installed. Same real-bytes-validation posture as
     * importFromRemoteBytes(): getimagesizefromstring() doesn't apply to
     * video, so finfo_buffer() sniffs the actual byte content — a declared
     * Content-Type header is still never trusted alone.
     */
    public function importVideoFromRemoteBytes(string $bytes, string $filename, string $declaredMime, array $metadata = []): CustomerMedia
    {
        if (strlen($bytes) > self::POLICY['video']['max']) {
            throw ValidationException::withMessages(['file' => 'Remote media exceeds the allowed size.']);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $sniffed = $finfo->buffer($bytes) ?: '';
        if (!isset(self::REMOTE_VIDEO_MIME_TO_EXTENSION[$sniffed])) {
            throw ValidationException::withMessages(['file' => 'Remote media is not a supported, verifiable video.']);
        }
        $mime = $sniffed;
        $extension = self::REMOTE_VIDEO_MIME_TO_EXTENSION[$mime];

        $siteId = $this->siteId();
        $uuid = (string) Str::uuid();
        $disk = $this->storage->diskNameFor('uploads');
        $key = $this->remoteImportStorageKey($siteId, $uuid, $extension, $metadata);

        if (!Storage::disk($disk)->put($key, $bytes, ['visibility' => 'public'])) {
            throw new \RuntimeException('Customer media storage write failed.');
        }

        try {
            return CustomerMedia::create([
                'site_id' => $siteId, 'uuid' => $uuid, 'disk' => $disk, 'storage_key' => $key,
                'original_filename' => $filename !== '' ? $filename : ($uuid . '.' . $extension),
                'extension' => $extension, 'category' => 'video', 'mime_type' => $mime, 'size_bytes' => strlen($bytes),
                'title' => $metadata['title'] ?? null, 'alt_text' => $metadata['alt_text'] ?? null,
                'width' => null, 'height' => null,
                'metadata' => array_filter([
                    'source_url' => $metadata['source_url'] ?? null,
                    'theme_identifier' => $metadata['theme_identifier'] ?? null,
                    'release_namespace' => $metadata['release_namespace'] ?? null,
                ], static fn ($value) => $value !== null && $value !== ''),
            ]);
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($key);
            throw $exception;
        }
    }

    /**
     * Dedup lookup for importFromRemoteBytes() — a theme re-install or
     * upgrade must never re-import/duplicate the same master-theme image
     * for the same site. Bounded: a handful of theme images per site, not
     * an unbounded scan (indexed by site_id via forSite(), JSON path match
     * only runs across this one site's own rows).
     */
    public function findBySourceUrl(string $url, array $scope = []): ?CustomerMedia
    {
        $query = CustomerMedia::forSite($this->siteId())->available()
            ->whereJsonContains('metadata->source_url', $url);

        if (($scope['theme_identifier'] ?? '') !== '') {
            $query->whereJsonContains('metadata->theme_identifier', $scope['theme_identifier']);
        }
        if (($scope['release_namespace'] ?? '') !== '') {
            $query->whereJsonContains('metadata->release_namespace', $scope['release_namespace']);
        }

        return $query->first();
    }

    /**
     * Theme imports are physically namespaced by site, theme and release.
     * Ordinary customer uploads deliberately retain their existing path.
     */
    private function remoteImportStorageKey(int $siteId, string $uuid, string $extension, array $metadata): string
    {
        $theme = trim((string) ($metadata['theme_identifier'] ?? ''));
        if ($theme === '') {
            return sprintf('customer-media/%d/%s/%s.%s', $siteId, now()->format('Y/m'), $uuid, $extension);
        }

        $safeTheme = trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $theme), '-');
        if ($safeTheme === '') {
            $safeTheme = 'theme-' . substr(hash('sha256', $theme), 0, 16);
        }
        $release = trim((string) ($metadata['release_namespace'] ?? 'unversioned'));
        $safeRelease = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $release), '-');
        $safeRelease = $safeRelease !== '' ? $safeRelease : 'unversioned';

        return sprintf(
            'customer-media/%d/themes/%s/release-%s/%s.%s',
            $siteId,
            $safeTheme,
            $safeRelease,
            $uuid,
            $extension
        );
    }

    /**
     * TASK 3/3 Phase 6.5C (BLD-25) — site logo/favicon convergence onto
     * this canonical media authority. `site_logo`/`site_favicon` Settings
     * now store a CustomerMedia UUID going forward (upload() below), but
     * any value uploaded before this phase is still a raw 'public'-disk
     * storage path — resolved here too so existing dev/test fixtures and
     * already-live sites never lose their configured logo/favicon. Returns
     * null (never throws) for a missing/deleted/foreign value — every
     * caller already renders a null URL as "no logo/favicon set".
     */
    public function resolveSiteAssetUrl(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            try {
                return $this->publicUrl($this->findForCurrentSite($value));
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
                return null;
            }
        }

        return Storage::disk('public')->exists($value) ? Storage::disk('public')->url($value) : null;
    }

    private const SORTS = ['newest', 'oldest', 'name', 'size'];

    /**
     * Read access is scoped here too: controllers must never turn a UUID,
     * query parameter, or client-supplied site id into an ownership decision.
     *
     * usage/sort are applied in the SAME bounded query — withCount('usages')
     * is a single aggregated subquery join, never a per-row follow-up query,
     * so filtering/sorting on it (having/orderBy) stays N+1-free.
     */
    public function paginateForCurrentSite(
        ?string $search = null,
        ?string $category = null,
        ?string $usage = null,
        string $sort = 'newest',
        int $perPage = 24
    ): LengthAwarePaginator {
        $query = CustomerMedia::forSite($this->siteId())->available()->withCount('usages');

        if ($category !== null) {
            $query->where('category', $category);
        }

        if ($search !== null && $search !== '') {
            $escaped = addcslashes($search, '\\%_');
            $query->where(function ($media) use ($escaped) {
                $media->where('title', 'like', "%{$escaped}%")
                    ->orWhere('original_filename', 'like', "%{$escaped}%")
                    ->orWhere('alt_text', 'like', "%{$escaped}%")
                    ->orWhere('caption', 'like', "%{$escaped}%");
            });
        }

        if ($usage === 'used') {
            $query->has('usages');
        } elseif ($usage === 'unused') {
            $query->doesntHave('usages');
        }

        $sort = in_array($sort, self::SORTS, true) ? $sort : 'newest';
        match ($sort) {
            'oldest' => $query->oldest('id'),
            'name' => $query->orderByRaw('COALESCE(title, original_filename) asc'),
            'size' => $query->orderByDesc('size_bytes'),
            default => $query->latest('id'),
        };

        return $query->paginate(min($perPage, 60))->withQueryString();
    }

    public function updateMetadata(CustomerMedia $media, array $metadata): CustomerMedia
    {
        $this->assertOwned($media);
        $media->fill([
            'title' => $metadata['title'] ?? null,
            'alt_text' => $metadata['alt_text'] ?? null,
            'caption' => $metadata['caption'] ?? null,
            'description' => $metadata['description'] ?? null,
        ]);
        $media->save();

        return $media->fresh();
    }

    public function publicUrl(CustomerMedia $media): string
    {
        $this->assertOwned($media);
        return Storage::disk($media->disk)->url($media->storage_key);
    }

    public function registerUsage(CustomerMedia $media, string $consumerType, int $consumerId, string $path): CustomerMediaUsage
    {
        $this->assertOwned($media);
        if ($media->deleted_at) {
            throw new \LogicException('Deleted media cannot be referenced.');
        }
        if ($consumerType === '' || mb_strlen($consumerType) > 64 || $path === '' || mb_strlen($path) > 512) {
            throw ValidationException::withMessages(['usage' => 'Media usage identity exceeds canonical limits.']);
        }
        return CustomerMediaUsage::firstOrCreate([
            'customer_media_id' => $media->id, 'consumer_type' => $consumerType,
            'consumer_id' => $consumerId, 'reference_path' => $path,
        ]);
    }

    public function isUsed(CustomerMedia $media): bool
    {
        $this->assertOwned($media);
        return $media->usages()->exists();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, CustomerMediaUsage> */
    public function usages(CustomerMedia $media): \Illuminate\Database\Eloquent\Collection
    {
        $this->assertOwned($media);
        return $media->usages()->orderBy('id')->get();
    }

    /** Register every future canonical `{ media_id: UUID }` reference in a revision. */
    public function registerThemeRevisionUsages(int $revisionId, array $themeData): void
    {
        foreach ($this->mediaIds($themeData) as [$uuid, $path]) {
            $this->registerUsage($this->findForCurrentSite($uuid), 'theme_instance_revision', $revisionId, $path);
        }
    }

    public function deleteUnused(CustomerMedia $media): void
    {
        $this->assertOwned($media);
        DB::transaction(function () use ($media) {
            $locked = CustomerMedia::whereKey($media->id)->lockForUpdate()->firstOrFail();
            if ($locked->deleted_at) return;
            if ($this->isUsed($locked)) {
                throw new \LogicException('Media is in use and cannot be deleted.');
            }
            $locked->deleted_at = now();
            $locked->save();
        });
        $this->purge($media->fresh());
    }

    /** Failed physical deletes leave a soft-deleted record for safe retry. */
    public function purge(CustomerMedia $media): void
    {
        $this->assertOwned($media);
        if (! $media->deleted_at || $media->purged_at) return;
        if (! Storage::disk($media->disk)->delete($media->storage_key)) {
            throw new \RuntimeException('Customer media physical deletion failed; retry is safe.');
        }
        $media->purged_at = now();
        $media->save();
    }

    /**
     * Replace the BINARY content of an existing media row without changing
     * its canonical identity (uuid, id) — every existing `{media_id}`
     * reference (Builder blocks, theme revisions, usage ledger rows) keeps
     * resolving through the same uuid and automatically sees the new file,
     * because publicUrl()/the resolver always derive the URL from the
     * row's *current* storage_key at read time. Only same-category
     * (image→image, video→video, ...) replacement is allowed — every
     * existing consumer of this media assumes its family (an <img> tag
     * cannot suddenly resolve to a PDF).
     *
     * Safety ordering: the new file is written first; the DB row is
     * updated only after that succeeds; the OLD physical file is deleted
     * last, and only after the DB commit — so a failure at any point
     * leaves either the untouched original (new upload failed) or a
     * correctly-updated record (old-file cleanup failed, orphaning at
     * worst one now-unreferenced file, never data loss).
     */
    public function replace(CustomerMedia $media, UploadedFile $file): CustomerMedia
    {
        $this->assertOwned($media);
        [$category, $extension, $mime] = $this->validate($file);

        if ($category !== $media->category) {
            throw ValidationException::withMessages([
                'file' => 'نوع فایل جایگزین باید با نوع رسانه اصلی («' . $media->category . '») سازگار باشد.',
            ]);
        }

        $oldDisk = $media->disk;
        $oldKey = $media->storage_key;
        // A random suffix guarantees the new key always differs from the
        // old one, even for a same-extension replacement within the same
        // month — otherwise the storage_key (and therefore the public URL)
        // would be byte-identical to before, and any browser/CDN cache
        // holding that URL would keep serving the OLD binary indefinitely.
        $newKey = sprintf('customer-media/%d/%s/%s-%s.%s', $media->site_id, now()->format('Y/m'), $media->uuid, Str::random(8), $extension);

        if (! Storage::disk($oldDisk)->putFileAs(dirname($newKey), $file, basename($newKey), ['visibility' => 'public'])) {
            throw new \RuntimeException('Customer media storage write failed.');
        }

        try {
            [$width, $height] = $category === 'image' ? $this->imageDimensions($file) + [null, null] : [null, null];
            $media->fill([
                'storage_key' => $newKey, 'extension' => $extension, 'mime_type' => $mime,
                'original_filename' => $file->getClientOriginalName(), 'size_bytes' => $file->getSize(),
                'width' => $width, 'height' => $height,
            ]);
            $media->save();
        } catch (\Throwable $exception) {
            Storage::disk($oldDisk)->delete($newKey);
            throw $exception;
        }

        if ($newKey !== $oldKey) {
            Storage::disk($oldDisk)->delete($oldKey);
        }

        return $media->fresh();
    }

    private const MAX_IMAGE_PIXELS = 40_000_000; // ~ 8000x5000; bounds decode-time memory regardless of declared file size.
    private const IMAGE_OPERATIONS = ['rotate', 'crop', 'resize'];

    /**
     * Safe, bounded in-place image editing (rotate/crop/resize). Operates
     * only on category==='image' rows and re-encodes to the SAME mime/
     * extension — canonical identity (uuid/row id) is always preserved,
     * exactly like replace(). The storage_key is still regenerated (not
     * kept byte-identical) for the same reason replace() does that: a
     * browser/CDN cache holding the old URL must not keep serving the
     * pre-edit image indefinitely.
     *
     * Decompression-bomb guard: pixel count is checked via getimagesize()
     * BEFORE any imagecreatefromXXX() call, which is where GD would
     * otherwise allocate a full truecolor buffer for whatever dimensions
     * the file declares.
     */
    public function editImage(CustomerMedia $media, string $operation, array $params): CustomerMedia
    {
        $this->assertOwned($media);
        if ($media->category !== 'image') {
            throw ValidationException::withMessages(['operation' => 'ویرایش فقط برای تصاویر ممکن است.']);
        }
        if (! in_array($operation, self::IMAGE_OPERATIONS, true)) {
            throw ValidationException::withMessages(['operation' => 'عملیات ویرایش نامعتبر است.']);
        }

        $disk = Storage::disk($media->disk);
        $realPath = $disk->path($media->storage_key);
        $size = @getimagesize($realPath);
        if (! is_array($size)) {
            throw new \RuntimeException('Image could not be read for editing.');
        }
        [$sourceWidth, $sourceHeight] = $size;
        if ($sourceWidth * $sourceHeight > self::MAX_IMAGE_PIXELS) {
            throw ValidationException::withMessages(['file' => 'ابعاد تصویر بیش از حد مجاز برای ویرایش است.']);
        }

        $source = $this->loadGdImage($realPath, $media->mime_type);
        if ($source === null) {
            throw new \RuntimeException('Image could not be decoded for editing.');
        }

        try {
            $edited = match ($operation) {
                'rotate' => $this->gdRotate($source, (int) ($params['degrees'] ?? 0)),
                'crop' => $this->gdCrop($source, $params, $sourceWidth, $sourceHeight),
                'resize' => $this->gdResize($source, $params, $sourceWidth, $sourceHeight),
            };
        } finally {
            imagedestroy($source);
        }

        if ($edited === null) {
            throw ValidationException::withMessages(['params' => 'پارامترهای ویرایش تصویر نامعتبر است.']);
        }

        $oldKey = $media->storage_key;
        $newKey = sprintf('customer-media/%d/%s/%s-%s.%s', $media->site_id, now()->format('Y/m'), $media->uuid, Str::random(8), $media->extension);

        try {
            $encoded = $this->encodeGdImage($edited, $media->mime_type);
            // Write the NEW key first; the old one is never touched until
            // the new binary and the DB row both succeed, so a failure at
            // any point leaves the existing image fully intact.
            if (! $disk->put($newKey, $encoded, ['visibility' => 'public'])) {
                throw new \RuntimeException('Edited image write failed; original preserved.');
            }

            $media->storage_key = $newKey;
            $media->width = imagesx($edited);
            $media->height = imagesy($edited);
            $media->size_bytes = strlen($encoded);
            $media->save();
        } catch (\Throwable $exception) {
            $disk->delete($newKey);
            throw $exception;
        } finally {
            imagedestroy($edited);
        }

        $disk->delete($oldKey);

        return $media->fresh();
    }

    private function loadGdImage(string $path, string $mime): ?\GdImage
    {
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };
        return $image instanceof \GdImage ? $image : null;
    }

    private function encodeGdImage(\GdImage $image, string $mime): string
    {
        ob_start();
        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($image, null, 90),
            'image/png' => imagepng($image, null, 6),
            'image/gif' => imagegif($image),
            'image/webp' => imagewebp($image, null, 90),
            default => false,
        };
        $data = ob_get_clean();
        if (! $ok || $data === false) {
            throw new \RuntimeException('Image encoding failed.');
        }
        return $data;
    }

    private function gdRotate(\GdImage $source, int $degrees): ?\GdImage
    {
        if (! in_array($degrees, [90, 180, 270, -90, -180, -270], true)) {
            return null;
        }
        // imagerotate() rotates counter-clockwise for a positive angle;
        // negate so "rotate(90)" reads as a clockwise turn to the caller.
        $rotated = imagerotate($source, -$degrees, 0);
        if ($rotated === false) {
            return null;
        }
        imagesavealpha($rotated, true);
        return $rotated;
    }

    private function gdCrop(\GdImage $source, array $params, int $sourceWidth, int $sourceHeight): ?\GdImage
    {
        $x = (int) ($params['x'] ?? -1);
        $y = (int) ($params['y'] ?? -1);
        $width = (int) ($params['width'] ?? 0);
        $height = (int) ($params['height'] ?? 0);
        if ($x < 0 || $y < 0 || $width <= 0 || $height <= 0 || $x + $width > $sourceWidth || $y + $height > $sourceHeight) {
            return null;
        }
        $cropped = imagecrop($source, ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height]);
        return $cropped === false ? null : $cropped;
    }

    private function gdResize(\GdImage $source, array $params, int $sourceWidth, int $sourceHeight): ?\GdImage
    {
        $maxWidth = (int) ($params['max_width'] ?? 0);
        $maxHeight = (int) ($params['max_height'] ?? 0);
        if ($maxWidth <= 0 || $maxHeight <= 0) {
            return null;
        }
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);
        $targetWidth = max(1, (int) round($sourceWidth * $ratio));
        $targetHeight = max(1, (int) round($sourceHeight * $ratio));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagesavealpha($target, true);
        imagealphablending($target, false);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));
        imagealphablending($target, true);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        return $target;
    }

    private function validate(UploadedFile $file): array
    {
        $mime = strtolower((string) $file->getMimeType());
        $extension = strtolower((string) $file->getClientOriginalExtension());
        foreach (self::POLICY as $category => $policy) {
            if (in_array($mime, $policy['mimes'], true) && in_array($extension, $policy['extensions'], true)) {
                if ($file->getSize() > $policy['max']) {
                    throw ValidationException::withMessages(['file' => 'File exceeds the allowed size.']);
                }
                return [$category, $extension, $mime];
            }
        }
        // SVG is intentionally excluded in V1: no sanitizer is currently a
        // canonical authority for arbitrary customer SVG uploads.
        throw ValidationException::withMessages(['file' => 'Unsupported or unsafe media type.']);
    }

    private function assertOwned(CustomerMedia $media): void
    {
        if ($media->site_id !== $this->siteId()) abort(404);
    }

    private function imageDimensions(UploadedFile $file): array
    {
        $size = @getimagesize($file->getRealPath());
        return is_array($size) ? [(int) $size[0], (int) $size[1]] : [];
    }

    /** @return array<int,array{0:string,1:string}> */
    private function mediaIds(mixed $value, string $path = ''): array
    {
        if (! is_array($value)) return [];
        $found = [];
        if (isset($value['media_id']) && is_string($value['media_id'])) $found[] = [$value['media_id'], $path . '.media_id'];
        foreach ($value as $key => $child) $found = array_merge($found, $this->mediaIds($child, $path . '.' . $key));
        return $found;
    }
}

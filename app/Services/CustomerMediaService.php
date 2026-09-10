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
        $key = sprintf('customer-media/%d/%s/%s.%s', $siteId, now()->format('Y/m'), $uuid, $extension);

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

    /**
     * Read access is scoped here too: controllers must never turn a UUID,
     * query parameter, or client-supplied site id into an ownership decision.
     */
    public function paginateForCurrentSite(?string $search = null, ?string $category = null, int $perPage = 24): LengthAwarePaginator
    {
        $query = CustomerMedia::forSite($this->siteId())->available()->withCount('usages');

        if ($category !== null) {
            $query->where('category', $category);
        }

        if ($search !== null && $search !== '') {
            $escaped = addcslashes($search, '\\%_');
            $query->where(function ($media) use ($escaped) {
                $media->where('title', 'like', "%{$escaped}%")
                    ->orWhere('original_filename', 'like', "%{$escaped}%")
                    ->orWhere('caption', 'like', "%{$escaped}%");
            });
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function updateMetadata(CustomerMedia $media, array $metadata): CustomerMedia
    {
        $this->assertOwned($media);
        $media->fill([
            'title' => $metadata['title'] ?? null,
            'alt_text' => $metadata['alt_text'] ?? null,
            'caption' => $metadata['caption'] ?? null,
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

<?php

namespace App\Services\TenantSupportRuntime;

use App\Models\SupportMessageAttachment;
use App\Services\MediaStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TenantAttachmentService
{
    private string $disk;
    private string $baseDir;

    private const MAX_FILE_SIZE   = 20 * 1024 * 1024; // 20MB
    private const ALLOWED_MIMES   = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf', 'text/plain',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'video/mp4', 'audio/mpeg', 'audio/ogg',
    ];

    public function __construct(MediaStorage $media)
    {
        $this->disk    = $media->diskNameFor('private');
        $this->baseDir = 'support-attachments/' . config('app.tenant_slug', 'tenant');
    }

    public function store(
        UploadedFile $file,
        string       $messageId,
        string       $conversationId,
        string       $uploaderId,
        string       $uploaderType
    ): SupportMessageAttachment {
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException('File exceeds maximum allowed size of 20MB.');
        }

        if (!in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException('File type not allowed.');
        }

        $hash      = hash_file('sha256', $file->getRealPath());
        $ext       = $file->getClientOriginalExtension();
        $safeName  = Str::uuid()->toString() . ($ext ? ".{$ext}" : '');
        $path      = "{$this->baseDir}/{$conversationId}/{$safeName}";

        Storage::disk($this->disk)->put($path, file_get_contents($file->getRealPath()));

        return SupportMessageAttachment::create([
            'message_id'      => $messageId,
            'conversation_id' => $conversationId,
            'original_name'   => $file->getClientOriginalName(),
            'stored_path'     => $path,
            'disk'            => $this->disk,
            'mime_type'       => $file->getMimeType(),
            'file_size'       => $file->getSize(),
            'file_hash'       => $hash,
            'uploader_id'     => $uploaderId,
            'uploader_type'   => $uploaderType,
        ]);
    }

    public function delete(SupportMessageAttachment $attachment): void
    {
        Storage::disk($attachment->disk)->delete($attachment->stored_path);
        $attachment->update(['is_deleted' => true]);
    }

    public function getDownloadResponse(SupportMessageAttachment $attachment): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (!$attachment->exists()) {
            abort(404, 'Attachment not found on disk.');
        }

        return Storage::disk($attachment->disk)->download(
            $attachment->stored_path,
            $attachment->original_name
        );
    }

    public function isStoredLocally(SupportMessageAttachment $attachment): bool
    {
        return in_array($attachment->disk, ['local', 'private'], true)
            || str_starts_with($attachment->stored_path, 'support-attachments/');
    }
}

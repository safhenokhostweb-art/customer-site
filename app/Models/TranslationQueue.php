<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranslationQueue extends Model
{
    protected $fillable = [
        'translatable_type', 'translatable_id',
        'locale', 'field_name',
        'status', 'attempts', 'last_error',
        'queued_at', 'processed_at',
    ];

    protected $casts = [
        'queued_at'    => 'datetime',
        'processed_at' => 'datetime',
    ];

    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE       = 'done';
    public const STATUS_FAILED     = 'failed';

    public static function enqueue(string $type, int $id, string $locale, string $field): void
    {
        static::updateOrCreate(
            [
                'translatable_type' => $type,
                'translatable_id'   => $id,
                'locale'            => $locale,
                'field_name'        => $field,
            ],
            [
                'status'       => self::STATUS_PENDING,
                'attempts'     => 0,
                'last_error'   => null,
                'queued_at'    => now(),
                'processed_at' => null,
            ]
        );
    }
}

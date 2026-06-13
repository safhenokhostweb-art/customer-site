<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentTranslation extends Model
{
    protected $fillable = [
        'translatable_type', 'translatable_id',
        'locale', 'field_name', 'translated_text',
        'source_hash', 'ai_model', 'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function translatable()
    {
        return $this->morphTo();
    }

    public static function findFor(string $type, int $id, string $locale, string $field): ?self
    {
        return static::where('translatable_type', $type)
            ->where('translatable_id', $id)
            ->where('locale', $locale)
            ->where('field_name', $field)
            ->first();
    }
}

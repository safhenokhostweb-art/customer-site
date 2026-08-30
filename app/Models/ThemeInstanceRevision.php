<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThemeInstanceRevision extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_INSTALL = 'install';
    public const TYPE_CUSTOMER_EDIT = 'customer_edit';
    public const TYPE_PUBLISH = 'publish';
    public const TYPE_UPDATE = 'update';
    public const TYPE_ROLLBACK_SNAPSHOT = 'rollback_snapshot';

    protected $fillable = [
        'theme_identifier',
        'source_theme_id',
        'source_theme_version',
        'source_release_id',
        'revision_number',
        'type',
        'theme_data',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'theme_data' => 'array',
        'metadata' => 'array',
        'revision_number' => 'integer',
        'source_theme_id' => 'integer',
        // source_theme_version is the platform's semver release string (e.g.
        // "1.2.3") — a plain string, never an int (Phase 1h; see the
        // migration that fixed its column type). Never fabricated: null
        // when the release version isn't known.
        'source_release_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

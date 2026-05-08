<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleChangeLog extends Model
{
    protected $fillable = [
        'module_slug',
        'version',
        'release_type',
        'title',
        'changelog',
        'released_at',
        'synced_at',
    ];

    protected $casts = [
        'released_at' => 'datetime',
        'synced_at' => 'datetime',
    ];
}

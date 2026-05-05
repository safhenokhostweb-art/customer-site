<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageTemplate extends Model
{
    protected $fillable = ['name', 'type', 'content'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }

    public const TYPE_SECTION = 'section';
    public const TYPE_PAGE = 'page';
}

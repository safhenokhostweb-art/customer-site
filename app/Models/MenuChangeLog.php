<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuChangeLog extends Model
{
    protected $fillable = ['user_id', 'menu_id', 'action', 'diff_json'];

    protected function casts(): array
    {
        return [
            'diff_json' => 'array',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

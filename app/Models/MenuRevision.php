<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuRevision extends Model
{
    protected $fillable = ['menu_id', 'user_id', 'snapshot_json', 'note'];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function decodeSnapshot(): array
    {
        $data = json_decode($this->snapshot_json, true);

        return is_array($data) ? $data : [];
    }
}

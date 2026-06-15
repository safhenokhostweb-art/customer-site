<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageRevision extends Model
{
    protected $fillable = ['page_id', 'blocks_snapshot', 'saved_by', 'label'];

    protected function casts(): array
    {
        return ['blocks_snapshot' => 'array'];
    }

    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}

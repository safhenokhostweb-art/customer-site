<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostSeries extends Model
{
    protected $table = 'post_series';

    protected $fillable = ['title', 'slug', 'description'];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'series_id')->orderByRaw('part_number IS NULL, part_number ASC, published_at ASC');
    }

    public function publishedPosts(): HasMany
    {
        return $this->posts()->published();
    }
}

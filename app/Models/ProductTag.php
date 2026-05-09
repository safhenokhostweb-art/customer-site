<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductTag extends Model
{
    protected $fillable = ['name', 'slug', 'tag_type'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_product_tag')->withTimestamps();
    }
}

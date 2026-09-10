<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerMediaUsage extends Model
{
    protected $table = 'customer_media_usages';

    protected $fillable = ['customer_media_id', 'consumer_type', 'consumer_id', 'reference_path'];

    public function media(): BelongsTo
    {
        return $this->belongsTo(CustomerMedia::class, 'customer_media_id');
    }
}

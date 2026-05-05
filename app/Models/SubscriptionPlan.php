<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'duration',
        'duration_count',
        'is_active',
        'order',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}

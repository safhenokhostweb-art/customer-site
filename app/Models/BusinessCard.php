<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessCard extends Model
{
    protected $fillable = [
        'name',
        'title',
        'company',
        'phone',
        'email',
        'website',
        'address',
        'avatar',
        'slug',
        'is_active',
        'order',
    ];

    protected $casts = ['is_active' => 'boolean'];
}

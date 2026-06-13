<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsPageView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'path', 'page_title', 'referrer', 'session_id',
        'customer_id', 'country', 'device', 'browser', 'visited_at',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
    ];
}

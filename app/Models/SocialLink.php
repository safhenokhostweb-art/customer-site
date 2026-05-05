<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    protected $fillable = ['platform', 'url', 'order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function platforms(): array
    {
        return [
            'instagram' => 'اینستاگرام',
            'twitter' => 'توییتر',
            'telegram' => 'تلگرام',
            'linkedin' => 'لینکدین',
            'youtube' => 'یوتیوب',
            'rubika' => 'روبیکا',
            'bale' => 'بله',
            'eitaa' => 'ایتا',
            'soroush' => 'سروش',
            'gap' => 'گپ',
        ];
    }
}

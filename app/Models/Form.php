<?php

namespace App\Models;

use App\Helpers\SlugHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Form extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'title',
        'slug',
        'is_active',
        'enable_simple_captcha',
        'enable_hcaptcha',
        'meta_title',
        'meta_description',
        'canonical_url',
        'meta_robots',
        'intro_text',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'enable_simple_captcha' => 'boolean',
        'enable_hcaptcha' => 'boolean',
    ];

    public function fields()
    {
        return $this->hasMany(FormField::class)->orderBy('position');
    }

    public function submissions()
    {
        return $this->hasMany(FormSubmission::class);
    }

    public static function boot()
    {
        parent::boot();
        static::creating(function ($form) {
            if (empty($form->slug)) {
                $form->slug = SlugHelper::unique(
                    SlugHelper::make((string) $form->title),
                    fn (string $s) => static::query()->where('slug', $s)->exists()
                );
            }
        });
    }
}

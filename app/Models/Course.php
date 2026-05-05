<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'image',
        'price',
        'is_active',
        'order',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function classes(): HasMany
    {
        return $this->hasMany(CourseClass::class, 'course_id')->orderBy('order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class, 'course_id');
    }

    public function enrolledUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_enrollments', 'course_id', 'user_id')
            ->withPivot('status', 'enrolled_at')
            ->withTimestamps();
    }
}

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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isFree(): bool
    {
        return (float) $this->price === 0.0;
    }

    public function classes(): HasMany
    {
        return $this->hasMany(CourseClass::class, 'course_id')->orderBy('order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class, 'course_id');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(CourseClassCompletion::class, 'course_id');
    }

    public function enrolledUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_enrollments', 'course_id', 'user_id')
            ->withPivot('status', 'enrolled_at')
            ->withTimestamps();
    }

    public function progressForUser(int $userId): int
    {
        $total = $this->classes()->count();
        if ($total === 0) {
            return 0;
        }
        $done = CourseClassCompletion::where('course_id', $this->id)
            ->where('user_id', $userId)
            ->count();
        return (int) round($done / $total * 100);
    }
}

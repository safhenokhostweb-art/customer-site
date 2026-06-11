<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseClass extends Model
{
    protected $fillable = ['course_id', 'title', 'content', 'order'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function completions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CourseClassCompletion::class);
    }
}

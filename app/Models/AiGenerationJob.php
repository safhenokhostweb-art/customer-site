<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGenerationJob extends Model
{
    protected $fillable = [
        'type', 'mode', 'prompt', 'extra_context', 'step_count',
        'status', 'result', 'error', 'provider', 'picked_at',
    ];

    protected $casts = [
        'picked_at' => 'datetime',
    ];

    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isDone(): bool     { return $this->status === 'done'; }
    public function isFailed(): bool   { return $this->status === 'failed'; }

    public function resultData(): mixed
    {
        return $this->result ? json_decode($this->result, true) : null;
    }
}

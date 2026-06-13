<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessList extends Model
{
    protected $fillable = ['name', 'description', 'match_type', 'is_active', 'entry_count'];

    protected $casts = ['is_active' => 'boolean'];

    public const MATCH_NATIONAL_ID = 'national_id';
    public const MATCH_LAST_NAME   = 'last_name';
    public const MATCH_BOTH        = 'both';

    public function entries()
    {
        return $this->hasMany(AccessListEntry::class);
    }

    public function accessGates()
    {
        return $this->hasMany(AccessGate::class);
    }

    public function verify(string $nationalId = null, string $lastName = null): ?AccessListEntry
    {
        $q = $this->entries();

        if ($this->match_type === self::MATCH_NATIONAL_ID) {
            return $q->where('national_id', $nationalId)->first();
        }

        if ($this->match_type === self::MATCH_LAST_NAME) {
            return $q->where('last_name', mb_strtolower(trim($lastName)))->first();
        }

        // both
        return $q->where('national_id', $nationalId)
            ->where('last_name', mb_strtolower(trim($lastName)))
            ->first();
    }
}

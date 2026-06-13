<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessGate extends Model
{
    protected $fillable = [
        'access_list_id', 'name', 'slug', 'description',
        'redirect_url', 'session_duration', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function accessList()
    {
        return $this->belongsTo(AccessList::class, 'access_list_id');
    }

    public function verifications()
    {
        return $this->hasMany(AccessVerification::class);
    }

    public function sessionKey(): string
    {
        return "access_gate_{$this->id}_verified";
    }
}

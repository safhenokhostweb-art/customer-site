<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessVerification extends Model
{
    protected $fillable = [
        'access_gate_id', 'access_list_entry_id',
        'ip_address', 'user_agent', 'success',
        'input_national_id', 'input_last_name',
    ];

    protected $casts = ['success' => 'boolean'];

    public function gate()
    {
        return $this->belongsTo(AccessGate::class, 'access_gate_id');
    }

    public function entry()
    {
        return $this->belongsTo(AccessListEntry::class, 'access_list_entry_id');
    }
}

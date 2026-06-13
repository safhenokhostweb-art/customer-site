<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessListEntry extends Model
{
    protected $fillable = ['access_list_id', 'national_id', 'last_name', 'notes'];

    public function list()
    {
        return $this->belongsTo(AccessList::class, 'access_list_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'user_id',
        'customer_name',
        'customer_mobile',
        'customer_phone',
        'subtotal',
        'discount',
        'total',
        'status',
        'payment_link',
        'paid_at',
        'notes',
    ];

    protected $casts = ['paid_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}

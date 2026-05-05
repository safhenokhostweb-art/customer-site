<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'number', 'user_id', 'customer_name', 'customer_email', 'customer_phone', 'customer_address',
        'note', 'subtotal', 'total', 'status', 'payment_method', 'coupon_id', 'discount_amount',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function generateNumber(): string
    {
        $prefix = 'ORD-' . date('Ymd');
        $last = self::where('number', 'like', $prefix . '%')->orderByDesc('id')->first();
        $seq = $last ? (int) substr($last->number, -4) + 1 : 1;
        return $prefix . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}

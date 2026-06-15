<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'inventory_item_id', 'warehouse_id', 'product_id', 'type',
        'reference_type', 'reference_id', 'quantity_change',
        'quantity_before', 'quantity_after', 'admin_id', 'notes', 'moved_at',
    ];

    protected $casts = [
        'moved_at' => 'datetime',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function typeLabel(string $type): string
    {
        return match($type) {
            'in'         => 'ورود کالا',
            'out'        => 'خروج کالا',
            'adjustment' => 'تعدیل موجودی',
            'transfer'   => 'انتقال بین انبار',
            'return'     => 'مرجوعی',
            default      => $type,
        };
    }
}

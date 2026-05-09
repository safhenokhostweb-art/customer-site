<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductAuditLog;

class ProductObserver
{
    public function updated(Product $product): void
    {
        $watch = ['price', 'sale_price', 'stock', 'status'];
        $changes = [];
        foreach ($watch as $field) {
            if ($product->wasChanged($field)) {
                $changes[$field] = [
                    'old' => $product->getOriginal($field),
                    'new' => $product->getAttribute($field),
                ];
            }
        }
        if ($changes === []) {
            return;
        }
        ProductAuditLog::query()->create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'changes' => $changes,
        ]);
    }
}

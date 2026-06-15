<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function dashboardStats(): array
    {
        $totalWarehouses  = Warehouse::where('is_active', true)->count();
        $totalItems       = InventoryItem::count();
        $lowStockItems    = InventoryItem::whereRaw('quantity <= low_stock_threshold')->count();
        $outOfStockItems  = InventoryItem::where('quantity', 0)->count();

        $totalStockValue = DB::table('inventory_items')
            ->join('products', 'inventory_items.product_id', '=', 'products.id')
            ->sum(DB::raw('inventory_items.quantity * products.price'));

        $recentMovements = StockMovement::with('product', 'warehouse')
            ->latest('moved_at')->take(10)->get();

        $lowStockList = InventoryItem::with(['product', 'warehouse'])
            ->whereRaw('quantity <= low_stock_threshold')
            ->orderBy('quantity')
            ->take(10)
            ->get();

        return compact(
            'totalWarehouses', 'totalItems', 'lowStockItems',
            'outOfStockItems', 'totalStockValue', 'recentMovements', 'lowStockList'
        );
    }

    public function adjustStock(InventoryItem $item, int $change, string $type, string $notes = '', ?int $adminId = null): StockMovement
    {
        return DB::transaction(function () use ($item, $change, $type, $notes, $adminId) {
            $before = $item->quantity;
            $after  = max(0, $before + $change);

            $item->update([
                'quantity' => $after,
                'last_restocked_at' => $change > 0 ? now() : $item->last_restocked_at,
            ]);

            return StockMovement::create([
                'inventory_item_id' => $item->id,
                'warehouse_id'      => $item->warehouse_id,
                'product_id'        => $item->product_id,
                'type'              => $type,
                'quantity_change'   => $change,
                'quantity_before'   => $before,
                'quantity_after'    => $after,
                'admin_id'          => $adminId,
                'notes'             => $notes,
                'moved_at'          => now(),
            ]);
        });
    }

    public function addProductToWarehouse(Product $product, Warehouse $warehouse, int $quantity = 0): InventoryItem
    {
        $item = InventoryItem::firstOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
            ['quantity' => 0, 'low_stock_threshold' => 5, 'reorder_quantity' => 10]
        );

        if ($quantity > 0) {
            $this->adjustStock($item, $quantity, 'in', 'اضافه شدن به انبار');
        }

        return $item->fresh();
    }

    public function getLowStockAlerts(): \Illuminate\Database\Eloquent\Collection
    {
        return InventoryItem::with(['product', 'warehouse'])
            ->whereRaw('quantity <= low_stock_threshold')
            ->orderBy('quantity')
            ->get();
    }
}

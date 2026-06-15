<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    // ─── Dashboard ─────────────────────────────────────────────────────────

    public function index(): View
    {
        $stats = $this->inventory->dashboardStats();
        return view('admin.inventory.dashboard', $stats);
    }

    // ─── Warehouses ────────────────────────────────────────────────────────

    public function warehouses(): View
    {
        $warehouses = Warehouse::withCount('inventoryItems')->get();
        return view('admin.inventory.warehouses.index', compact('warehouses'));
    }

    public function createWarehouse(): View
    {
        return view('admin.inventory.warehouses.form', ['warehouse' => null]);
    }

    public function storeWarehouse(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'            => 'required|string|max:191',
            'code'            => 'required|string|max:30|unique:warehouses,code',
            'address'         => 'nullable|string|max:500',
            'city'            => 'nullable|string|max:100',
            'manager_name'    => 'nullable|string|max:100',
            'manager_mobile'  => 'nullable|string|max:20',
            'is_default'      => 'boolean',
            'is_active'       => 'boolean',
            'notes'           => 'nullable|string',
        ]);

        $data['is_default'] = $request->boolean('is_default');
        $data['is_active']  = $request->boolean('is_active', true);

        if ($data['is_default']) {
            Warehouse::where('is_default', true)->update(['is_default' => false]);
        }

        Warehouse::create($data);

        return redirect()->route('admin.inventory.warehouses.index')
            ->with('success', 'انبار ایجاد شد.');
    }

    public function editWarehouse(Warehouse $warehouse): View
    {
        return view('admin.inventory.warehouses.form', compact('warehouse'));
    }

    public function updateWarehouse(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:191',
            'code'           => 'required|string|max:30|unique:warehouses,code,' . $warehouse->id,
            'address'        => 'nullable|string|max:500',
            'city'           => 'nullable|string|max:100',
            'manager_name'   => 'nullable|string|max:100',
            'manager_mobile' => 'nullable|string|max:20',
            'is_default'     => 'boolean',
            'is_active'      => 'boolean',
            'notes'          => 'nullable|string',
        ]);

        $data['is_default'] = $request->boolean('is_default');
        $data['is_active']  = $request->boolean('is_active', true);

        if ($data['is_default']) {
            Warehouse::where('is_default', true)->where('id', '!=', $warehouse->id)->update(['is_default' => false]);
        }

        $warehouse->update($data);

        return redirect()->route('admin.inventory.warehouses.index')
            ->with('success', 'انبار بروزرسانی شد.');
    }

    public function destroyWarehouse(Warehouse $warehouse): RedirectResponse
    {
        $warehouse->delete();
        return redirect()->route('admin.inventory.warehouses.index')
            ->with('success', 'انبار حذف شد.');
    }

    // ─── Stock Items ───────────────────────────────────────────────────────

    public function items(Request $request): View
    {
        $query = InventoryItem::with(['product', 'warehouse']);

        if ($wh = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $wh);
        }
        if ($request->input('low_stock')) {
            $query->whereRaw('quantity <= low_stock_threshold');
        }
        if ($q = $request->input('q')) {
            $query->whereHas('product', fn ($b) => $b->where('title', 'like', "%{$q}%"));
        }

        $items      = $query->latest()->paginate(25)->withQueryString();
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('admin.inventory.items.index', compact('items', 'warehouses'));
    }

    public function adjustStock(Request $request, InventoryItem $item): RedirectResponse
    {
        $request->validate([
            'quantity_change' => 'required|integer|not_in:0',
            'type'            => 'required|in:in,out,adjustment,return',
            'notes'           => 'nullable|string|max:500',
        ]);

        $this->inventory->adjustStock(
            $item,
            (int) $request->quantity_change,
            $request->type,
            $request->notes ?? '',
            auth()->id()
        );

        return back()->with('success', 'موجودی بروز شد.');
    }

    public function addProductToWarehouse(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity'     => 'nullable|integer|min:0',
        ]);

        $warehouse = Warehouse::findOrFail($request->warehouse_id);
        $this->inventory->addProductToWarehouse($product, $warehouse, (int) ($request->quantity ?? 0));

        return back()->with('success', 'محصول به انبار اضافه شد.');
    }

    // ─── Movements ─────────────────────────────────────────────────────────

    public function movements(Request $request): View
    {
        $query = StockMovement::with(['product', 'warehouse']);

        if ($wh = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $wh);
        }
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $movements  = $query->latest('moved_at')->paginate(30)->withQueryString();
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('admin.inventory.movements.index', compact('movements', 'warehouses'));
    }

    // ─── Alerts ────────────────────────────────────────────────────────────

    public function alerts(): View
    {
        $alerts = $this->inventory->getLowStockAlerts();
        return view('admin.inventory.alerts', compact('alerts'));
    }
}

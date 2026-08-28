<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('erp/purchase-orders/Index', [
            'orders' => PurchaseOrder::query()
                ->with(['supplier:id,name', 'warehouse:id,name', 'lines'])
                ->latest('date')
                ->get()
                ->map(fn (PurchaseOrder $o) => [
                    'id' => $o->id,
                    'number' => $o->number,
                    'supplier' => $o->supplier->name,
                    'warehouse' => $o->warehouse->name,
                    'date' => $o->date->toDateString(),
                    'status' => $o->status,
                    'total' => (float) $o->lines->sum(fn ($l) => $l->quantity * $l->unit_price),
                ]),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(['id', 'sku', 'name', 'price']),
        ]);
    }

    public function store(Request $request, PurchaseOrderService $service): RedirectResponse
    {
        abort_unless($request->user()->can('purchase.create'), 403);

        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $service->create(
            Supplier::findOrFail($data['supplier_id']),
            Warehouse::findOrFail($data['warehouse_id']),
            $data['date'],
            $data['lines'],
            $data['notes'] ?? null,
            $request->user(),
        );

        return back()->with('success', 'Purchase order created.');
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderService $service): RedirectResponse
    {
        abort_unless($request->user()->can('purchase.approve'), 403);

        $service->receive($purchaseOrder, $request->user());

        return back()->with('success', 'Purchase order received into stock.');
    }
}

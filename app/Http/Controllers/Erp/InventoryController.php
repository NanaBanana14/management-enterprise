<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('erp/inventory/Index', [
            'stocks' => ProductStock::query()
                ->with(['product:id,sku,name,unit', 'warehouse:id,name'])
                ->where('quantity', '>', 0)
                ->orderBy('product_id')
                ->get()
                ->map(fn (ProductStock $s) => [
                    'id' => $s->id,
                    'product' => $s->product->name,
                    'sku' => $s->product->sku,
                    'unit' => $s->product->unit,
                    'warehouse' => $s->warehouse->name,
                    'quantity' => (float) $s->quantity,
                ]),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(['id', 'sku', 'name']),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function adjust(Request $request, InventoryService $service): RedirectResponse
    {
        abort_unless($request->user()->can('inventory.adjust'), 403);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'type' => ['required', 'in:in,out'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $service->adjust(
            Product::findOrFail($data['product_id']),
            Warehouse::findOrFail($data['warehouse_id']),
            $data['type'],
            (float) $data['quantity'],
            $data['note'] ?? null,
            $request->user(),
        );

        return back()->with('success', 'Stock adjusted.');
    }

    public function transfer(Request $request, InventoryService $service): RedirectResponse
    {
        abort_unless($request->user()->can('inventory.transfer'), 403);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $service->transfer(
            Product::findOrFail($data['product_id']),
            Warehouse::findOrFail($data['from_warehouse_id']),
            Warehouse::findOrFail($data['to_warehouse_id']),
            (float) $data['quantity'],
            $data['note'] ?? null,
            $request->user(),
        );

        return back()->with('success', 'Stock transferred.');
    }
}

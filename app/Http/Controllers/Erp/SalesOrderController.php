<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use App\Services\SalesOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesOrderController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('erp/sales-orders/Index', [
            'orders' => SalesOrder::query()
                ->with(['customer:id,name', 'warehouse:id,name', 'lines'])
                ->latest('date')
                ->get()
                ->map(fn (SalesOrder $o) => [
                    'id' => $o->id,
                    'number' => $o->number,
                    'customer' => $o->customer->name,
                    'warehouse' => $o->warehouse->name,
                    'date' => $o->date->toDateString(),
                    'status' => $o->status,
                    'total' => (float) $o->lines->sum(fn ($l) => $l->quantity * $l->unit_price),
                ]),
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(['id', 'sku', 'name', 'price']),
        ]);
    }

    public function store(Request $request, SalesOrderService $service): RedirectResponse
    {
        abort_unless($request->user()->can('sales.create'), 403);

        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $service->create(
            Customer::findOrFail($data['customer_id']),
            Warehouse::findOrFail($data['warehouse_id']),
            $data['date'],
            $data['lines'],
            $data['notes'] ?? null,
            $request->user(),
        );

        return back()->with('success', 'Sales order created.');
    }

    public function fulfill(Request $request, SalesOrder $salesOrder, SalesOrderService $service): RedirectResponse
    {
        abort_unless($request->user()->can('sales.approve'), 403);

        $service->fulfill($salesOrder, $request->user());

        return back()->with('success', 'Sales order fulfilled.');
    }
}

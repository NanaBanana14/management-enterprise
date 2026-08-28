<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('erp/warehouses/Index', [
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'code', 'name', 'address', 'is_active']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('warehouse.manage'), 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:warehouses,code'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        Warehouse::create($data);

        return back()->with('success', 'Warehouse created.');
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        abort_unless($request->user()->can('warehouse.manage'), 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:warehouses,code,'.$warehouse->id],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $warehouse->update($data);

        return back()->with('success', 'Warehouse updated.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\AssetCategory;
use App\Models\Employee;
use App\Models\FixedAsset;
use App\Models\Warehouse;
use App\Services\FixedAssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    public function __construct(private readonly FixedAssetService $assets) {}

    public function index(): Response
    {
        return Inertia::render('assets/Index', [
            'assets' => FixedAsset::query()
                ->with(['warehouse:id,name', 'employee:id,name'])
                ->latest('created_at')
                ->get()
                ->map(fn (FixedAsset $a) => [
                    'id' => $a->id,
                    'code' => $a->code,
                    'name' => $a->name,
                    'category' => $a->category->value,
                    'custody' => $a->employee->name ?? $a->warehouse->name ?? 'Unassigned',
                    'acquisition_cost' => (float) $a->acquisition_cost,
                    'book_value' => $a->bookValue(),
                    'status' => $a->status,
                ]),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'employees' => Employee::query()->where('employment_status', 'active')->orderBy('name')->get(['id', 'name']),
            'categories' => array_map(fn (AssetCategory $c) => ['value' => $c->value, 'label' => $c->label()], AssetCategory::cases()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('asset.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(AssetCategory::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'acquisition_date' => ['required', 'date'],
            'acquisition_cost' => ['required', 'numeric', 'min:0.01'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life_months' => ['required', 'integer', 'min:1'],
        ]);

        $this->assets->register(
            $data['name'],
            AssetCategory::from($data['category']),
            $data['description'] ?? null,
            isset($data['warehouse_id']) ? Warehouse::find($data['warehouse_id']) : null,
            isset($data['employee_id']) ? Employee::find($data['employee_id']) : null,
            $data['acquisition_date'],
            (float) $data['acquisition_cost'],
            (float) ($data['salvage_value'] ?? 0),
            (int) $data['useful_life_months'],
            $request->user(),
        );

        return back()->with('success', 'Asset registered.');
    }

    public function show(FixedAsset $asset): Response
    {
        $asset->load(['warehouse:id,name', 'employee:id,name', 'creator:id,name', 'depreciationEntries' => fn ($q) => $q->orderByDesc('period')]);

        return Inertia::render('assets/Show', [
            'asset' => [
                'id' => $asset->id,
                'code' => $asset->code,
                'name' => $asset->name,
                'category' => $asset->category->value,
                'description' => $asset->description,
                'status' => $asset->status,
                'warehouse' => $asset->warehouse?->only('id', 'name'),
                'employee' => $asset->employee?->only('id', 'name'),
                'acquisition_date' => $asset->acquisition_date->toDateString(),
                'acquisition_cost' => (float) $asset->acquisition_cost,
                'salvage_value' => (float) $asset->salvage_value,
                'useful_life_months' => $asset->useful_life_months,
                'accumulated_depreciation' => (float) $asset->accumulated_depreciation,
                'book_value' => $asset->bookValue(),
                'disposal_date' => $asset->disposal_date?->toDateString(),
                'disposal_value' => $asset->disposal_value !== null ? (float) $asset->disposal_value : null,
                'depreciation_entries' => $asset->depreciationEntries->map(fn ($e) => [
                    'id' => $e->id,
                    'period' => $e->period->format('F Y'),
                    'amount' => (float) $e->amount,
                ]),
            ],
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'employees' => Employee::query()->where('employment_status', 'active')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function reassign(Request $request, FixedAsset $asset): RedirectResponse
    {
        abort_unless($request->user()->can('asset.create'), 403);

        $data = $request->validate([
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
        ]);

        $this->assets->reassign(
            $asset,
            isset($data['warehouse_id']) ? Warehouse::find($data['warehouse_id']) : null,
            isset($data['employee_id']) ? Employee::find($data['employee_id']) : null,
        );

        return back()->with('success', 'Asset custody updated.');
    }

    public function runDepreciation(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('asset.manage'), 403);

        $data = $request->validate(['period' => ['required', 'date']]);

        $result = $this->assets->runDepreciation($data['period'], $request->user());

        if ($result['count'] === 0) {
            return back()->with('info', 'No eligible assets to depreciate for that period.');
        }

        return back()->with('success', "Depreciation posted for {$result['count']} asset(s).");
    }

    public function dispose(Request $request, FixedAsset $asset): RedirectResponse
    {
        abort_unless($request->user()->can('asset.manage'), 403);

        $data = $request->validate([
            'disposal_date' => ['required', 'date'],
            'disposal_value' => ['required', 'numeric', 'min:0'],
        ]);

        $this->assets->dispose($asset, $data['disposal_date'], (float) $data['disposal_value'], $request->user());

        return back()->with('success', 'Asset disposed.');
    }
}

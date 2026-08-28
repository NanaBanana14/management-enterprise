<?php

namespace App\Http\Controllers\Hris;

use App\Http\Controllers\Controller;
use App\Models\Kpi;
use App\Models\KpiCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KpiController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('hris/kpis/Index', [
            'categories' => KpiCategory::query()
                ->with('kpis')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('kpi.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        KpiCategory::create($data);

        return back()->with('success', 'KPI category added.');
    }

    public function storeKpi(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('kpi.manage'), 403);

        $data = $request->validate([
            'kpi_category_id' => ['required', 'exists:kpi_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'weight' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        Kpi::create($data);

        return back()->with('success', 'KPI added.');
    }

    public function destroyKpi(Request $request, Kpi $kpi): RedirectResponse
    {
        abort_unless($request->user()->can('kpi.manage'), 403);

        $kpi->delete();

        return back()->with('success', 'KPI removed.');
    }
}

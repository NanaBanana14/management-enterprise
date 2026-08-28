<?php

namespace App\Http\Controllers\Hris;

use App\Http\Controllers\Controller;
use App\Models\PerformancePeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PerformancePeriodController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('hris/performance/Periods', [
            'periods' => PerformancePeriod::query()
                ->withCount('reviews')
                ->orderByDesc('start_date')
                ->get()
                ->map(fn (PerformancePeriod $period) => [
                    'id' => $period->id,
                    'name' => $period->name,
                    'start_date' => $period->start_date->format('Y-m-d'),
                    'end_date' => $period->end_date->format('Y-m-d'),
                    'status' => $period->status,
                    'reviews_count' => $period->reviews_count,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('performance.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        PerformancePeriod::create($data);

        return back()->with('success', 'Performance period created.');
    }
}

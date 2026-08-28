<?php

namespace App\Http\Controllers\Hris;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hris\StorePositionRequest;
use App\Http\Requests\Hris\UpdatePositionRequest;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PositionController extends Controller
{
    public function index(Request $request): Response
    {
        $positions = Position::query()
            ->with('department:id,name')
            ->withCount('employees')
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            })
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('hris/positions/Index', [
            'positions' => $positions,
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('search', 'department_id'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('hris/positions/Create', [
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StorePositionRequest $request): RedirectResponse
    {
        $position = Position::create($request->validated());

        return to_route('hris.positions.index')->with('success', "Position \"{$position->name}\" created.");
    }

    public function edit(Position $position): Response
    {
        return Inertia::render('hris/positions/Edit', [
            'position' => $position->only('id', 'department_id', 'name', 'code', 'description', 'salary_min', 'salary_max'),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdatePositionRequest $request, Position $position): RedirectResponse
    {
        $position->update($request->validated());

        return to_route('hris.positions.index')->with('success', "Position \"{$position->name}\" updated.");
    }

    public function destroy(Request $request, Position $position): RedirectResponse
    {
        abort_unless($request->user()->can('position.manage'), 403);

        if ($position->employees()->exists()) {
            return back()->with('error', "Position \"{$position->name}\" still has employees assigned.");
        }

        $position->delete();

        return to_route('hris.positions.index')->with('success', "Position \"{$position->name}\" deleted.");
    }
}

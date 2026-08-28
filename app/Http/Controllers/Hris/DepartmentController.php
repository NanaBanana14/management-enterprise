<?php

namespace App\Http\Controllers\Hris;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hris\StoreDepartmentRequest;
use App\Http\Requests\Hris\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(Request $request): Response
    {
        $departments = Department::query()
            ->with('manager:id,name')
            ->withCount(['employees', 'positions'])
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('hris/departments/Index', [
            'departments' => $departments,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('hris/departments/Create', [
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $department = Department::create($request->validated());

        return to_route('hris.departments.index')->with('success', "Department \"{$department->name}\" created.");
    }

    public function edit(Department $department): Response
    {
        return Inertia::render('hris/departments/Edit', [
            'department' => $department->only('id', 'name', 'code', 'description', 'manager_id'),
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        return to_route('hris.departments.index')->with('success', "Department \"{$department->name}\" updated.");
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        abort_unless($request->user()->can('department.manage'), 403);

        if ($department->employees()->exists()) {
            return back()->with('error', "Department \"{$department->name}\" still has employees assigned.");
        }

        $department->delete();

        return to_route('hris.departments.index')->with('success', "Department \"{$department->name}\" deleted.");
    }
}

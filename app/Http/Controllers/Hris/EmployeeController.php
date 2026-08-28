<?php

namespace App\Http\Controllers\Hris;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hris\StoreEmployeeRequest;
use App\Http\Requests\Hris\UpdateEmployeeRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $employees = Employee::query()
            ->with(['department:id,name', 'position:id,name'])
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('employment_status', $request->string('status')->toString()))
            ->when($request->boolean('archived'), fn ($query) => $query->onlyTrashed())
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('hris/employees/Index', [
            'employees' => $employees,
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => array_map(fn (EmploymentStatus $s) => ['value' => $s->value, 'label' => $s->label()], EmploymentStatus::cases()),
            'filters' => $request->only('search', 'department_id', 'status', 'archived'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('hris/employees/Create', $this->formOptions());
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = DB::transaction(function () use ($request) {
            $data = $request->safe()->except('photo');

            $employee = Employee::create($data);

            if ($request->hasFile('photo')) {
                $employee->update(['photo_path' => $request->file('photo')->store('employees/photos', 'public')]);
            }

            return $employee;
        });

        return to_route('hris.employees.show', $employee)->with('success', "Employee \"{$employee->name}\" created.");
    }

    public function show(Employee $employee): Response
    {
        $employee->load(['department:id,name', 'position:id,name', 'manager:id,name', 'subordinates:id,name,manager_id']);

        $recentAttendance = $employee->attendances()
            ->orderByDesc('date')
            ->limit(10)
            ->get(['date', 'check_in_at', 'check_out_at', 'status'])
            ->map(fn ($record) => [
                'date' => $record->date->format('Y-m-d'),
                'check_in_at' => $record->check_in_at?->format('Y-m-d\TH:i:s'),
                'check_out_at' => $record->check_out_at?->format('Y-m-d\TH:i:s'),
                'status' => $record->status->value,
            ]);

        $monthlyCounts = $employee->attendances()
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return Inertia::render('hris/employees/Show', [
            'employee' => [
                ...$employee->toArray(),
                'photo_url' => $employee->photoUrl(),
            ],
            'recentAttendance' => $recentAttendance,
            'monthlyAttendance' => $monthlyCounts,
        ]);
    }

    public function edit(Employee $employee): Response
    {
        return Inertia::render('hris/employees/Edit', [
            'employee' => [
                ...$employee->only([
                    'id', 'employee_number', 'name', 'email', 'phone', 'department_id', 'position_id',
                    'manager_id', 'employment_type', 'employment_status', 'basic_salary', 'address',
                    'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship', 'user_id',
                ]),
                'employment_type' => $employee->employment_type->value,
                'employment_status' => $employee->employment_status->value,
                'join_date' => $employee->join_date->format('Y-m-d'),
                'photo_url' => $employee->photoUrl(),
            ],
            ...$this->formOptions($employee),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        DB::transaction(function () use ($request, $employee) {
            $employee->update($request->safe()->except('photo'));

            if ($request->hasFile('photo')) {
                if ($employee->photo_path) {
                    Storage::disk('public')->delete($employee->photo_path);
                }

                $employee->update(['photo_path' => $request->file('photo')->store('employees/photos', 'public')]);
            }
        });

        return to_route('hris.employees.show', $employee)->with('success', "Employee \"{$employee->name}\" updated.");
    }

    public function destroy(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->can('employee.delete'), 403);

        $employee->delete();

        return to_route('hris.employees.index')->with('success', "Employee \"{$employee->name}\" archived.");
    }

    private function formOptions(?Employee $editing = null): array
    {
        return [
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'positions' => Position::query()->orderBy('name')->get(['id', 'name', 'department_id']),
            'managers' => Employee::query()
                ->when($editing, fn ($query) => $query->whereKeyNot($editing->id))
                ->orderBy('name')
                ->get(['id', 'name']),
            'employmentTypes' => array_map(fn (EmploymentType $t) => ['value' => $t->value, 'label' => $t->label()], EmploymentType::cases()),
            'employmentStatuses' => array_map(fn (EmploymentStatus $s) => ['value' => $s->value, 'label' => $s->label()], EmploymentStatus::cases()),
        ];
    }
}

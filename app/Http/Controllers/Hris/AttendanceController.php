<?php

namespace App\Http\Controllers\Hris;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendance) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $canManage = $user->can('attendance.manage');

        $records = Attendance::query()
            ->with('employee:id,name,employee_number,department_id')
            ->when(! $canManage, fn ($query) => $query->where('employee_id', $user->employee?->id ?? 0))
            ->when($canManage && $request->filled('employee_id'), fn ($query) => $query->where('employee_id', $request->integer('employee_id')))
            ->when($canManage && $request->filled('department_id'), fn ($query) => $query->whereHas('employee', fn ($q) => $q->where('department_id', $request->integer('department_id'))))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('date', '>=', $request->string('from')->toString()))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('date', '<=', $request->string('to')->toString()))
            ->orderByDesc('date')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Attendance $record) => [
                'id' => $record->id,
                'date' => $record->date->format('Y-m-d'),
                'check_in_at' => $record->check_in_at?->format('Y-m-d\TH:i:s'),
                'check_out_at' => $record->check_out_at?->format('Y-m-d\TH:i:s'),
                'status' => $record->status->value,
                'employee' => $record->employee->only('id', 'name', 'employee_number'),
            ]);

        $today = $user->employee
            ? Attendance::query()->where('employee_id', $user->employee->id)->whereDate('date', now())->first()
            : null;

        return Inertia::render('hris/attendance/Index', [
            'records' => $records,
            'departments' => $canManage ? Department::query()->orderBy('name')->get(['id', 'name']) : [],
            'statuses' => array_map(fn (AttendanceStatus $s) => ['value' => $s->value, 'label' => $s->label()], AttendanceStatus::cases()),
            'filters' => $request->only('employee_id', 'department_id', 'status', 'from', 'to'),
            'canManage' => $canManage,
            'hasEmployeeProfile' => $user->employee !== null,
            'today' => $today ? [
                'checked_in' => (bool) $today->check_in_at,
                'checked_out' => (bool) $today->check_out_at,
                'check_in_at' => $today->check_in_at?->format('H:i'),
                'check_out_at' => $today->check_out_at?->format('H:i'),
                'status' => $today->status->value,
            ] : null,
        ]);
    }

    public function checkIn(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 403, 'No employee profile is linked to your account.');

        $this->attendance->checkIn($employee, $request->ip());

        return back()->with('success', 'Checked in.');
    }

    public function checkOut(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 403, 'No employee profile is linked to your account.');

        $this->attendance->checkOut($employee, $request->ip());

        return back()->with('success', 'Checked out.');
    }
}

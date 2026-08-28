<?php

namespace App\Http\Controllers\Hris;

use App\Enums\LeaveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hris\StoreLeaveRequest;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveController extends Controller
{
    public function __construct(private readonly LeaveService $leave) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $canApprove = $user->can('leave.approve');

        $requests = LeaveRequest::query()
            ->with(['employee:id,name,employee_number', 'leaveType:id,name', 'approver:id,name'])
            ->when(! $canApprove, fn ($query) => $query->where('employee_id', $user->employee?->id ?? 0))
            ->when($canApprove && $request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (LeaveRequest $leaveRequest) => [
                'id' => $leaveRequest->id,
                'start_date' => $leaveRequest->start_date->format('Y-m-d'),
                'end_date' => $leaveRequest->end_date->format('Y-m-d'),
                'days' => (float) $leaveRequest->days,
                'reason' => $leaveRequest->reason,
                'status' => $leaveRequest->status->value,
                'rejection_reason' => $leaveRequest->rejection_reason,
                'employee' => $leaveRequest->employee->only('id', 'name', 'employee_number'),
                'leave_type' => $leaveRequest->leaveType->only('id', 'name'),
                'approver' => $leaveRequest->approver?->only('id', 'name'),
            ]);

        $balances = $user->employee
            ? LeaveBalance::query()
                ->where('employee_id', $user->employee->id)
                ->where('year', now()->year)
                ->with('leaveType:id,name')
                ->get()
                ->map(fn (LeaveBalance $balance) => [
                    'leave_type' => $balance->leaveType->name,
                    'allocated' => (float) $balance->allocated_days,
                    'used' => (float) $balance->used_days,
                    'remaining' => $balance->remainingDays(),
                ])
            : [];

        return Inertia::render('hris/leave/Index', [
            'requests' => $requests,
            'leaveTypes' => LeaveType::query()->orderBy('name')->get(['id', 'name']),
            'balances' => $balances,
            'statuses' => array_map(fn (LeaveStatus $s) => ['value' => $s->value, 'label' => $s->label()], LeaveStatus::cases()),
            'filters' => $request->only('status'),
            'canApprove' => $canApprove,
            'hasEmployeeProfile' => $user->employee !== null,
        ]);
    }

    public function store(StoreLeaveRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $employee = $request->user()->employee;
        $leaveType = LeaveType::findOrFail($data['leave_type_id']);

        $this->leave->request($employee, $leaveType, $data['start_date'], $data['end_date'], $data['reason'] ?? null);

        return back()->with('success', 'Leave request submitted.');
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($request->user()->can('leave.approve'), 403);

        $this->leave->approve($leaveRequest, $request->user());

        return back()->with('success', 'Leave request approved.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($request->user()->can('leave.approve'), 403);

        $request->validate(['rejection_reason' => ['nullable', 'string', 'max:1000']]);

        $this->leave->reject($leaveRequest, $request->user(), $request->string('rejection_reason')->toString() ?: null);

        return back()->with('success', 'Leave request rejected.');
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($leaveRequest->employee_id === $request->user()->employee?->id, 403);

        $this->leave->cancel($leaveRequest);

        return back()->with('success', 'Leave request cancelled.');
    }
}

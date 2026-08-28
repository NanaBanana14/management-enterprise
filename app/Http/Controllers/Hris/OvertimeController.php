<?php

namespace App\Http\Controllers\Hris;

use App\Enums\OvertimeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hris\StoreOvertimeRequest;
use App\Models\OvertimeRequest;
use App\Services\OvertimeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OvertimeController extends Controller
{
    public function __construct(private readonly OvertimeService $overtime) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $canApprove = $user->can('overtime.approve');

        $requests = OvertimeRequest::query()
            ->with(['employee:id,name,employee_number', 'approver:id,name'])
            ->when(! $canApprove, fn ($query) => $query->where('employee_id', $user->employee?->id ?? 0))
            ->when($canApprove && $request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (OvertimeRequest $overtimeRequest) => [
                'id' => $overtimeRequest->id,
                'date' => $overtimeRequest->date->format('Y-m-d'),
                'hours' => (float) $overtimeRequest->hours,
                'reason' => $overtimeRequest->reason,
                'status' => $overtimeRequest->status->value,
                'rejection_reason' => $overtimeRequest->rejection_reason,
                'employee' => $overtimeRequest->employee->only('id', 'name', 'employee_number'),
                'approver' => $overtimeRequest->approver?->only('id', 'name'),
            ]);

        return Inertia::render('hris/overtime/Index', [
            'requests' => $requests,
            'statuses' => array_map(fn (OvertimeStatus $s) => ['value' => $s->value, 'label' => $s->label()], OvertimeStatus::cases()),
            'filters' => $request->only('status'),
            'canApprove' => $canApprove,
            'hasEmployeeProfile' => $user->employee !== null,
        ]);
    }

    public function store(StoreOvertimeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $employee = $request->user()->employee;

        $this->overtime->request($employee, $data['date'], (float) $data['hours'], $data['reason'] ?? null);

        return back()->with('success', 'Overtime request submitted.');
    }

    public function approve(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        abort_unless($request->user()->can('overtime.approve'), 403);

        $this->overtime->approve($overtimeRequest, $request->user());

        return back()->with('success', 'Overtime request approved.');
    }

    public function reject(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        abort_unless($request->user()->can('overtime.approve'), 403);

        $request->validate(['rejection_reason' => ['nullable', 'string', 'max:1000']]);

        $this->overtime->reject($overtimeRequest, $request->user(), $request->string('rejection_reason')->toString() ?: null);

        return back()->with('success', 'Overtime request rejected.');
    }

    public function cancel(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        abort_unless($overtimeRequest->employee_id === $request->user()->employee?->id, 403);

        $this->overtime->cancel($overtimeRequest);

        return back()->with('success', 'Overtime request cancelled.');
    }
}

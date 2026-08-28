<?php

namespace App\Services;

use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    public function request(Employee $employee, LeaveType $leaveType, string $startDate, string $endDate, ?string $reason): LeaveRequest
    {
        return DB::transaction(function () use ($employee, $leaveType, $startDate, $endDate, $reason) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->startOfDay();

            if ($end->lt($start)) {
                throw ValidationException::withMessages(['end_date' => 'The end date must be on or after the start date.']);
            }

            $overlapping = LeaveRequest::query()
                ->where('employee_id', $employee->id)
                ->whereIn('status', [LeaveStatus::Pending->value, LeaveStatus::Approved->value])
                ->whereDate('start_date', '<=', $end)
                ->whereDate('end_date', '>=', $start)
                ->exists();

            if ($overlapping) {
                throw ValidationException::withMessages(['start_date' => 'This overlaps an existing leave request.']);
            }

            $days = collect(CarbonPeriod::create($start, $end))->filter->isWeekday()->count();

            if ($days === 0) {
                throw ValidationException::withMessages(['end_date' => 'The selected range has no working days.']);
            }

            $balance = $this->balanceFor($employee, $leaveType, $start->year);

            if ($leaveType->is_paid && $balance->remainingDays() < $days) {
                throw ValidationException::withMessages([
                    'start_date' => "Insufficient balance: {$balance->remainingDays()} day(s) remaining for {$leaveType->name}.",
                ]);
            }

            return LeaveRequest::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $start,
                'end_date' => $end,
                'days' => $days,
                'reason' => $reason,
                'status' => LeaveStatus::Pending->value,
            ]);
        });
    }

    public function approve(LeaveRequest $leaveRequest, User $approver): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $approver) {
            $leaveRequest = LeaveRequest::query()->whereKey($leaveRequest->id)->lockForUpdate()->firstOrFail();

            if ($leaveRequest->status !== LeaveStatus::Pending) {
                throw ValidationException::withMessages(['status' => 'Only pending requests can be approved.']);
            }

            $balance = $this->balanceFor($leaveRequest->employee, $leaveRequest->leaveType, $leaveRequest->start_date->year, lock: true);

            if ($leaveRequest->leaveType->is_paid && $balance->remainingDays() < (float) $leaveRequest->days) {
                throw ValidationException::withMessages(['status' => 'Insufficient balance remains for this leave type.']);
            }

            if ($leaveRequest->leaveType->is_paid) {
                $balance->increment('used_days', (float) $leaveRequest->days);
            }

            $leaveRequest->update([
                'status' => LeaveStatus::Approved->value,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            return $leaveRequest;
        });
    }

    public function reject(LeaveRequest $leaveRequest, User $approver, ?string $reason): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $approver, $reason) {
            $leaveRequest = LeaveRequest::query()->whereKey($leaveRequest->id)->lockForUpdate()->firstOrFail();

            if ($leaveRequest->status !== LeaveStatus::Pending) {
                throw ValidationException::withMessages(['status' => 'Only pending requests can be rejected.']);
            }

            $leaveRequest->update([
                'status' => LeaveStatus::Rejected->value,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $leaveRequest;
        });
    }

    public function cancel(LeaveRequest $leaveRequest): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest) {
            $leaveRequest = LeaveRequest::query()->whereKey($leaveRequest->id)->lockForUpdate()->firstOrFail();

            if ($leaveRequest->status !== LeaveStatus::Pending) {
                throw ValidationException::withMessages(['status' => 'Only pending requests can be cancelled.']);
            }

            $leaveRequest->update(['status' => LeaveStatus::Cancelled->value]);

            return $leaveRequest;
        });
    }

    private function balanceFor(Employee $employee, LeaveType $leaveType, int $year, bool $lock = false): LeaveBalance
    {
        $query = LeaveBalance::query()
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first() ?? LeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => $year,
            'allocated_days' => $leaveType->default_days_per_year,
            'used_days' => 0,
        ]);
    }
}

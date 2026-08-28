<?php

namespace App\Services;

use App\Enums\OvertimeStatus;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OvertimeService
{
    private const MAX_HOURS_PER_DAY = 12;

    public function request(Employee $employee, string $date, float $hours, ?string $reason): OvertimeRequest
    {
        return DB::transaction(function () use ($employee, $date, $hours, $reason) {
            if ($hours <= 0 || $hours > self::MAX_HOURS_PER_DAY) {
                throw ValidationException::withMessages([
                    'hours' => 'Hours must be between 0 and '.self::MAX_HOURS_PER_DAY.'.',
                ]);
            }

            $duplicate = OvertimeRequest::query()
                ->where('employee_id', $employee->id)
                ->whereDate('date', $date)
                ->whereIn('status', [OvertimeStatus::Pending->value, OvertimeStatus::Approved->value])
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'date' => 'An overtime request already exists for this date.',
                ]);
            }

            return OvertimeRequest::create([
                'employee_id' => $employee->id,
                'date' => $date,
                'hours' => $hours,
                'reason' => $reason,
                'status' => OvertimeStatus::Pending->value,
            ]);
        });
    }

    public function approve(OvertimeRequest $overtimeRequest, User $approver): OvertimeRequest
    {
        return DB::transaction(function () use ($overtimeRequest, $approver) {
            $overtimeRequest = OvertimeRequest::query()->whereKey($overtimeRequest->id)->lockForUpdate()->firstOrFail();

            if ($overtimeRequest->status !== OvertimeStatus::Pending) {
                throw ValidationException::withMessages(['status' => 'Only pending requests can be approved.']);
            }

            $overtimeRequest->update([
                'status' => OvertimeStatus::Approved->value,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            return $overtimeRequest;
        });
    }

    public function reject(OvertimeRequest $overtimeRequest, User $approver, ?string $reason): OvertimeRequest
    {
        return DB::transaction(function () use ($overtimeRequest, $approver, $reason) {
            $overtimeRequest = OvertimeRequest::query()->whereKey($overtimeRequest->id)->lockForUpdate()->firstOrFail();

            if ($overtimeRequest->status !== OvertimeStatus::Pending) {
                throw ValidationException::withMessages(['status' => 'Only pending requests can be rejected.']);
            }

            $overtimeRequest->update([
                'status' => OvertimeStatus::Rejected->value,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $overtimeRequest;
        });
    }

    public function cancel(OvertimeRequest $overtimeRequest): OvertimeRequest
    {
        return DB::transaction(function () use ($overtimeRequest) {
            $overtimeRequest = OvertimeRequest::query()->whereKey($overtimeRequest->id)->lockForUpdate()->firstOrFail();

            if ($overtimeRequest->status !== OvertimeStatus::Pending) {
                throw ValidationException::withMessages(['status' => 'Only pending requests can be cancelled.']);
            }

            $overtimeRequest->update(['status' => OvertimeStatus::Cancelled->value]);

            return $overtimeRequest;
        });
    }
}

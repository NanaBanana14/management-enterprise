<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function checkIn(Employee $employee, string $ip): Attendance
    {
        return DB::transaction(function () use ($employee, $ip) {
            $today = Carbon::today();

            $existing = Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('date', $today)
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->check_in_at) {
                throw ValidationException::withMessages([
                    'check_in' => 'Already checked in today.',
                ]);
            }

            $now = Carbon::now();
            $scheduledStart = $today->copy()->setTimeFromTimeString(config('attendance.work_start'));
            $graceCutoff = $scheduledStart->copy()->addMinutes((int) config('attendance.grace_minutes'));

            $status = $now->gt($graceCutoff) ? AttendanceStatus::Late : AttendanceStatus::Present;

            return Attendance::updateOrCreate(
                ['employee_id' => $employee->id, 'date' => $today],
                ['check_in_at' => $now, 'check_in_ip' => $ip, 'status' => $status->value],
            );
        });
    }

    public function checkOut(Employee $employee, string $ip): Attendance
    {
        return DB::transaction(function () use ($employee, $ip) {
            $attendance = Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('date', Carbon::today())
                ->lockForUpdate()
                ->first();

            if (! $attendance || ! $attendance->check_in_at) {
                throw ValidationException::withMessages([
                    'check_out' => 'You must check in before checking out.',
                ]);
            }

            if ($attendance->check_out_at) {
                throw ValidationException::withMessages([
                    'check_out' => 'Already checked out today.',
                ]);
            }

            $attendance->update([
                'check_out_at' => Carbon::now(),
                'check_out_ip' => $ip,
            ]);

            return $attendance;
        });
    }
}

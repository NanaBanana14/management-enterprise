<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Models\Employee;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $workdays = collect(CarbonPeriod::create(now()->subWeekdays(20), now()))
            ->filter(fn ($day) => $day->isWeekday() && $day->lte(now()));

        Employee::query()->where('employment_status', 'active')->each(function (Employee $employee) use ($workdays) {
            $rows = $workdays->map(function ($day) {
                $roll = fake()->numberBetween(1, 100);

                if ($roll <= 3) {
                    return [
                        'date' => $day->toDateString(),
                        'status' => AttendanceStatus::Absent->value,
                        'check_in_at' => null,
                        'check_out_at' => null,
                    ];
                }

                $late = $roll <= 15;
                $checkIn = $day->copy()->setTime(9, $late ? fake()->numberBetween(16, 45) : fake()->numberBetween(0, 10));
                $checkOut = $day->copy()->setTime(17, fake()->numberBetween(0, 40));

                return [
                    'date' => $day->toDateString(),
                    'status' => ($late ? AttendanceStatus::Late : AttendanceStatus::Present)->value,
                    'check_in_at' => $checkIn,
                    'check_out_at' => $checkOut,
                ];
            });

            $employee->attendances()->insert(
                $rows->map(fn ($row) => [
                    'employee_id' => $employee->id,
                    ...$row,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all()
            );
        });
    }
}

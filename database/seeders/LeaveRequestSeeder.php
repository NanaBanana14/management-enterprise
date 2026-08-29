<?php

namespace Database\Seeders;

use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveService;
use Illuminate\Database\Seeder;

class LeaveRequestSeeder extends Seeder
{
    public function run(): void
    {
        if (LeaveRequest::exists()) {
            return;
        }

        $approver = User::where('email', 'admin@nexa.test')->first();
        $leaveTypes = LeaveType::all();

        if (! $approver || $leaveTypes->isEmpty()) {
            return;
        }

        $service = app(LeaveService::class);
        $employees = Employee::where('employment_status', 'active')->inRandomOrder()->limit(45)->get();

        foreach ($employees as $employee) {
            $monthsAgo = fake()->numberBetween(0, 4);
            $start = now()->subMonths($monthsAgo)->subDays(fake()->numberBetween(0, 20))->startOfWeek()->addDays(fake()->numberBetween(0, 4));
            $length = fake()->numberBetween(1, 4);
            $end = $start->copy()->addDays($length - 1);
            $leaveType = $leaveTypes->random();

            try {
                $request = $service->request($employee, $leaveType, $start->toDateString(), $end->toDateString(), fake()->randomElement([
                    'Family matter', 'Medical appointment', 'Personal errand', 'Rest and recovery', null,
                ]));
            } catch (\Throwable) {
                continue;
            }

            $roll = fake()->numberBetween(1, 100);

            if ($roll <= 55) {
                try {
                    $service->approve($request, $approver);
                } catch (\Throwable) {
                }
            } elseif ($roll <= 75) {
                // leave pending
            } elseif ($roll <= 90) {
                $service->reject($request, $approver, fake()->randomElement(['Insufficient coverage', 'Peak period', null]));
            } else {
                $service->cancel($request);
            }
        }

        // Guarantee a visible pending queue for reviewers regardless of random rolls above.
        $pendingPool = Employee::where('employment_status', 'active')
            ->whereDoesntHave('leaveRequests', fn ($q) => $q->whereIn('status', [LeaveStatus::Pending->value, LeaveStatus::Approved->value]))
            ->inRandomOrder()
            ->limit(5)
            ->get();

        foreach ($pendingPool as $employee) {
            $start = now()->addDays(fake()->numberBetween(3, 20));
            $end = $start->copy()->addDays(fake()->numberBetween(0, 2));

            try {
                $service->request($employee, $leaveTypes->random(), $start->toDateString(), $end->toDateString(), 'Planned time off');
            } catch (\Throwable) {
            }
        }
    }
}

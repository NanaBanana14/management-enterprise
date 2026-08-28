<?php

namespace Tests\Feature\Hris;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Services\OvertimeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OvertimeTest extends TestCase
{
    use RefreshDatabase;

    private function employee(): Employee
    {
        $department = Department::factory()->create();
        $position = Position::factory()->for($department)->create();

        return Employee::factory()->create(['department_id' => $department->id, 'position_id' => $position->id]);
    }

    public function test_a_valid_overtime_request_is_created_as_pending()
    {
        $employee = $this->employee();

        $request = app(OvertimeService::class)->request($employee, '2026-09-01', 3, 'Release deployment');

        $this->assertSame('pending', $request->status->value);
        $this->assertSame(3.0, (float) $request->hours);
    }

    public function test_hours_over_the_daily_maximum_are_rejected()
    {
        $employee = $this->employee();

        $this->expectException(ValidationException::class);

        app(OvertimeService::class)->request($employee, '2026-09-01', 13, null);
    }

    public function test_duplicate_request_for_the_same_date_is_rejected()
    {
        $employee = $this->employee();
        $service = app(OvertimeService::class);

        $service->request($employee, '2026-09-01', 2, null);

        $this->expectException(ValidationException::class);
        $service->request($employee, '2026-09-01', 3, null);
    }

    public function test_approving_marks_the_request_approved()
    {
        $employee = $this->employee();
        $service = app(OvertimeService::class);
        $approver = User::factory()->create();

        $request = $service->request($employee, '2026-09-01', 2, null);
        $service->approve($request, $approver);

        $this->assertSame('approved', $request->fresh()->status->value);
    }

    public function test_cannot_approve_a_non_pending_request()
    {
        $employee = $this->employee();
        $service = app(OvertimeService::class);
        $approver = User::factory()->create();

        $request = $service->request($employee, '2026-09-01', 2, null);
        $service->reject($request, $approver, 'Not needed');

        $this->expectException(ValidationException::class);
        $service->approve($request, $approver);
    }

    public function test_cancelling_frees_the_date_for_a_new_request()
    {
        $employee = $this->employee();
        $service = app(OvertimeService::class);

        $first = $service->request($employee, '2026-09-01', 2, null);
        $service->cancel($first);

        $second = $service->request($employee, '2026-09-01', 4, null);

        $this->assertSame('pending', $second->status->value);
    }
}

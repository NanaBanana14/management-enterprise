<?php

namespace Tests\Feature\Hris;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use App\Services\LeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LeaveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['leave.view', 'leave.create', 'leave.approve'] as $permission) {
            Permission::findOrCreate($permission);
        }
    }

    private function employee(): Employee
    {
        $department = Department::factory()->create();
        $position = Position::factory()->for($department)->create();

        return Employee::factory()->create(['department_id' => $department->id, 'position_id' => $position->id]);
    }

    public function test_requesting_leave_within_balance_creates_a_pending_request()
    {
        $employee = $this->employee();
        $type = LeaveType::create(['name' => 'Annual', 'code' => 'ANNUAL', 'default_days_per_year' => 12, 'is_paid' => true]);

        $leaveRequest = app(LeaveService::class)->request($employee, $type, '2026-09-01', '2026-09-03', 'Vacation');

        $this->assertSame('pending', $leaveRequest->status->value);
        $this->assertSame(3.0, (float) $leaveRequest->days); // Tue-Thu, all weekdays
    }

    public function test_requesting_more_days_than_balance_fails()
    {
        $employee = $this->employee();
        $type = LeaveType::create(['name' => 'Annual', 'code' => 'ANNUAL', 'default_days_per_year' => 2, 'is_paid' => true]);

        $this->expectException(ValidationException::class);

        app(LeaveService::class)->request($employee, $type, '2026-09-01', '2026-09-10', null);
    }

    public function test_overlapping_leave_requests_are_rejected()
    {
        $employee = $this->employee();
        $type = LeaveType::create(['name' => 'Annual', 'code' => 'ANNUAL', 'default_days_per_year' => 20, 'is_paid' => true]);
        $service = app(LeaveService::class);

        $service->request($employee, $type, '2026-09-01', '2026-09-05', null);

        $this->expectException(ValidationException::class);
        $service->request($employee, $type, '2026-09-04', '2026-09-06', null);
    }

    public function test_end_date_before_start_date_is_rejected()
    {
        $employee = $this->employee();
        $type = LeaveType::create(['name' => 'Annual', 'code' => 'ANNUAL', 'default_days_per_year' => 20, 'is_paid' => true]);

        $this->expectException(ValidationException::class);

        app(LeaveService::class)->request($employee, $type, '2026-09-10', '2026-09-01', null);
    }

    public function test_approving_a_request_deducts_the_balance()
    {
        $employee = $this->employee();
        $type = LeaveType::create(['name' => 'Annual', 'code' => 'ANNUAL', 'default_days_per_year' => 12, 'is_paid' => true]);
        $service = app(LeaveService::class);
        $approver = User::factory()->create();

        $leaveRequest = $service->request($employee, $type, '2026-09-01', '2026-09-03', null);
        $service->approve($leaveRequest, $approver);

        $balance = $employee->leaveBalances()->where('leave_type_id', $type->id)->first();

        $this->assertSame('approved', $leaveRequest->fresh()->status->value);
        $this->assertSame(3.0, (float) $balance->used_days);
    }

    public function test_approving_an_already_approved_request_fails()
    {
        $employee = $this->employee();
        $type = LeaveType::create(['name' => 'Annual', 'code' => 'ANNUAL', 'default_days_per_year' => 12, 'is_paid' => true]);
        $service = app(LeaveService::class);
        $approver = User::factory()->create();

        $leaveRequest = $service->request($employee, $type, '2026-09-01', '2026-09-03', null);
        $service->approve($leaveRequest, $approver);

        $this->expectException(ValidationException::class);
        $service->approve($leaveRequest, $approver);
    }

    public function test_cancelling_a_pending_request_does_not_touch_balance()
    {
        $employee = $this->employee();
        $type = LeaveType::create(['name' => 'Annual', 'code' => 'ANNUAL', 'default_days_per_year' => 12, 'is_paid' => true]);
        $service = app(LeaveService::class);

        $leaveRequest = $service->request($employee, $type, '2026-09-01', '2026-09-03', null);
        $service->cancel($leaveRequest);

        $this->assertSame('cancelled', $leaveRequest->fresh()->status->value);
        $this->assertDatabaseMissing('leave_balances', ['employee_id' => $employee->id, 'used_days' => 3]);
    }

    public function test_employee_without_approve_permission_cannot_approve_via_http()
    {
        $employee = $this->employee();
        $type = LeaveType::create(['name' => 'Annual', 'code' => 'ANNUAL', 'default_days_per_year' => 12, 'is_paid' => true]);
        $requester = User::factory()->create();
        $requester->givePermissionTo('leave.view');

        $leaveRequest = app(LeaveService::class)->request($employee, $type, '2026-09-01', '2026-09-03', null);

        $response = $this->actingAs($requester)->post("/hris/leave/{$leaveRequest->id}/approve");

        $response->assertForbidden();
    }
}

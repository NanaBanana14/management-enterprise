<?php

namespace Tests\Feature\Hris;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['attendance.view', 'attendance.manage'] as $permission) {
            Permission::findOrCreate($permission);
        }
    }

    private function employeeUser(): array
    {
        $user = User::factory()->create();
        $user->givePermissionTo('attendance.view');

        $department = Department::factory()->create();
        $position = Position::factory()->for($department)->create();
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        return [$user, $employee];
    }

    public function test_checking_in_before_grace_period_marks_present()
    {
        [$user] = $this->employeeUser();

        Carbon::setTestNow(Carbon::today()->setTime(8, 55));

        $this->actingAs($user)->post('/hris/attendance/check-in')->assertRedirect();

        $this->assertDatabaseHas('attendances', ['employee_id' => $user->employee->id, 'status' => 'present']);

        Carbon::setTestNow();
    }

    public function test_checking_in_after_grace_period_marks_late()
    {
        [$user] = $this->employeeUser();

        Carbon::setTestNow(Carbon::today()->setTime(9, 30));

        $this->actingAs($user)->post('/hris/attendance/check-in')->assertRedirect();

        $this->assertDatabaseHas('attendances', ['employee_id' => $user->employee->id, 'status' => 'late']);

        Carbon::setTestNow();
    }

    public function test_cannot_check_in_twice_in_one_day()
    {
        [$user] = $this->employeeUser();

        $this->actingAs($user)->post('/hris/attendance/check-in')->assertRedirect();
        $response = $this->actingAs($user)->post('/hris/attendance/check-in');

        $response->assertSessionHasErrors('check_in');
    }

    public function test_cannot_check_out_without_checking_in()
    {
        [$user] = $this->employeeUser();

        $response = $this->actingAs($user)->post('/hris/attendance/check-out');

        $response->assertSessionHasErrors('check_out');
    }

    public function test_self_service_user_only_sees_their_own_records()
    {
        [$user, $employee] = $this->employeeUser();
        [, $otherEmployee] = $this->employeeUser();

        $employee->attendances()->create(['date' => today(), 'status' => 'present']);
        $otherEmployee->attendances()->create(['date' => today(), 'status' => 'present']);

        $response = $this->actingAs($user)->get('/hris/attendance');

        $response->assertInertia(fn ($page) => $page
            ->where('canManage', false)
            ->has('records.data', 1));
    }

    public function test_manager_sees_all_attendance_records()
    {
        [, $employee] = $this->employeeUser();
        [, $otherEmployee] = $this->employeeUser();

        $employee->attendances()->create(['date' => today(), 'status' => 'present']);
        $otherEmployee->attendances()->create(['date' => today(), 'status' => 'late']);

        $manager = User::factory()->create();
        $manager->givePermissionTo(['attendance.view', 'attendance.manage']);

        $response = $this->actingAs($manager)->get('/hris/attendance');

        $response->assertInertia(fn ($page) => $page
            ->where('canManage', true)
            ->has('records.data', 2));
    }
}

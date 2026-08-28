<?php

namespace Tests\Feature\Hris;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['employee.view', 'employee.create', 'employee.update', 'employee.delete'] as $permission) {
            Permission::findOrCreate($permission);
        }
    }

    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    public function test_guests_are_redirected_to_login()
    {
        $this->get('/hris/employees')->assertRedirect('/login');
    }

    public function test_users_without_permission_are_forbidden()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/hris/employees')->assertForbidden();
    }

    public function test_employee_numbers_are_sequential()
    {
        $department = Department::factory()->create();
        $position = Position::factory()->for($department)->create();

        $first = Employee::factory()->create(['department_id' => $department->id, 'position_id' => $position->id]);
        $second = Employee::factory()->create(['department_id' => $department->id, 'position_id' => $position->id]);

        $this->assertSame('EMP-0001', $first->employee_number);
        $this->assertSame('EMP-0002', $second->employee_number);
    }

    public function test_employee_can_be_created_via_the_form()
    {
        $user = $this->userWithPermissions(['employee.create']);
        $department = Department::factory()->create();
        $position = Position::factory()->for($department)->create();

        $response = $this->actingAs($user)->post('/hris/employees', [
            'name' => 'Sari Wulandari',
            'email' => 'sari.wulandari@nexa.test',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'employment_type' => 'full_time',
            'employment_status' => 'active',
            'join_date' => now()->toDateString(),
            'basic_salary' => 8_000_000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('employees', ['email' => 'sari.wulandari@nexa.test', 'employee_number' => 'EMP-0001']);
    }

    public function test_status_filter_only_returns_matching_employees()
    {
        $user = $this->userWithPermissions(['employee.view']);
        $department = Department::factory()->create();
        $position = Position::factory()->for($department)->create();

        Employee::factory()->create(['department_id' => $department->id, 'position_id' => $position->id, 'employment_status' => 'active']);
        Employee::factory()->create(['department_id' => $department->id, 'position_id' => $position->id, 'employment_status' => 'terminated']);

        $response = $this->actingAs($user)->get('/hris/employees?status=terminated');

        $response->assertInertia(fn ($page) => $page
            ->component('hris/employees/Index')
            ->has('employees.data', 1)
            ->where('employees.data.0.employment_status', 'terminated'));
    }

    public function test_archiving_soft_deletes_and_hides_from_default_index()
    {
        $user = $this->userWithPermissions(['employee.view', 'employee.delete']);
        $department = Department::factory()->create();
        $position = Position::factory()->for($department)->create();
        $employee = Employee::factory()->create(['department_id' => $department->id, 'position_id' => $position->id]);

        $this->actingAs($user)->delete("/hris/employees/{$employee->id}")->assertRedirect('/hris/employees');

        $this->assertSoftDeleted($employee);

        $default = $this->actingAs($user)->get('/hris/employees');
        $default->assertInertia(fn ($page) => $page->has('employees.data', 0));

        $archived = $this->actingAs($user)->get('/hris/employees?archived=true');
        $archived->assertInertia(fn ($page) => $page->has('employees.data', 1));
    }
}

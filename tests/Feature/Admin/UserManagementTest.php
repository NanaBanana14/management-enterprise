<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('users.view');
    }

    public function test_role_filter_excludes_users_without_that_role()
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('users.view');

        $manager = Role::findOrCreate('HR Manager');
        $staff = Role::findOrCreate('HR Staff');

        $managerUser = User::factory()->create(['name' => 'Manager One']);
        $managerUser->assignRole($manager);

        $staffUser = User::factory()->create(['name' => 'Staff One']);
        $staffUser->assignRole($staff);

        $response = $this->actingAs($admin)->get('/admin/users?role=HR Manager');

        $response->assertInertia(fn ($page) => $page
            ->component('admin/users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Manager One'));
    }

    public function test_status_filter_separates_active_and_inactive_users()
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('users.view');

        User::factory()->create(['name' => 'Active Person', 'is_active' => true]);
        User::factory()->create(['name' => 'Inactive Person', 'is_active' => false]);

        $response = $this->actingAs($admin)->get('/admin/users?status=inactive');

        $response->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Inactive Person'));
    }
}

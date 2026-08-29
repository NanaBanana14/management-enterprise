<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['employee.view', 'users.view'] as $permission) {
            Permission::findOrCreate($permission);
        }
    }

    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_a_user_without_permissions_does_not_receive_hris_finance_erp_or_platform_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->where('hris', null)
            ->where('finance', null)
            ->where('erp', null)
            ->where('platform', null));
    }

    public function test_platform_wide_user_and_role_counts_are_only_sent_to_users_who_can_view_users()
    {
        $withoutAccess = $this->userWithPermissions(['employee.view']);
        $withAccess = $this->userWithPermissions(['users.view']);

        $this->actingAs($withoutAccess)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page->where('platform', null));

        $this->actingAs($withAccess)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page->has('platform.stats.totalUsers')->has('platform.usersByRole'));
    }
}

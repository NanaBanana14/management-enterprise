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

        foreach (['employee.view', 'users.view', 'product.view', 'purchase.view', 'sales.view', 'recruitment.view', 'performance.view'] as $permission) {
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

    public function test_sales_only_users_do_not_see_purchase_order_data_and_vice_versa()
    {
        $salesOnly = $this->userWithPermissions(['product.view', 'sales.view']);
        $purchasingOnly = $this->userWithPermissions(['product.view', 'purchase.view']);

        $this->actingAs($salesOnly)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('erp.draftSalesOrders', 0)
                ->where('erp.draftPurchaseOrders', null));

        $this->actingAs($purchasingOnly)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('erp.draftPurchaseOrders', 0)
                ->where('erp.draftSalesOrders', null));
    }

    public function test_hris_sub_widgets_are_only_sent_to_users_with_their_specific_permission()
    {
        $hrOnly = $this->userWithPermissions(['employee.view']);
        $hrWithRecruitmentAndPerformance = $this->userWithPermissions(['employee.view', 'recruitment.view', 'performance.view']);

        $this->actingAs($hrOnly)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('hris.recruitmentSummary', null)
                ->where('hris.performanceSummary', null));

        $this->actingAs($hrWithRecruitmentAndPerformance)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page
                ->has('hris.recruitmentSummary.openVacancies')
                ->has('hris.performanceSummary.submitted'));
    }
}

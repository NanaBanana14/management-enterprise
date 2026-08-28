<?php

namespace Tests\Feature\Hris;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Kpi;
use App\Models\KpiCategory;
use App\Models\PerformancePeriod;
use App\Models\Position;
use App\Models\User;
use App\Services\PerformanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    private function employee(): Employee
    {
        $department = Department::factory()->create();
        $position = Position::factory()->for($department)->create();

        return Employee::factory()->create(['department_id' => $department->id, 'position_id' => $position->id]);
    }

    private function seedKpis(): void
    {
        $category = KpiCategory::create(['name' => 'Productivity']);
        Kpi::create(['kpi_category_id' => $category->id, 'name' => 'Output', 'weight' => 60]);
        Kpi::create(['kpi_category_id' => $category->id, 'name' => 'Timeliness', 'weight' => 40]);
    }

    private function period(): PerformancePeriod
    {
        return PerformancePeriod::create(['name' => 'Q1', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31']);
    }

    public function test_creating_a_review_seeds_one_item_per_kpi()
    {
        $this->seedKpis();
        $employee = $this->employee();
        $reviewer = User::factory()->create();

        $review = app(PerformanceService::class)->createReview($this->period(), $employee, $reviewer);

        $this->assertSame(2, $review->items()->count());
        $this->assertSame('draft', $review->status->value);
    }

    public function test_cannot_create_a_second_review_for_the_same_employee_and_period()
    {
        $this->seedKpis();
        $employee = $this->employee();
        $reviewer = User::factory()->create();
        $period = $this->period();
        $service = app(PerformanceService::class);

        $service->createReview($period, $employee, $reviewer);

        $this->expectException(ValidationException::class);
        $service->createReview($period, $employee, $reviewer);
    }

    public function test_scoring_recalculates_the_weighted_overall_score()
    {
        $this->seedKpis();
        $employee = $this->employee();
        $reviewer = User::factory()->create();
        $service = app(PerformanceService::class);

        $review = $service->createReview($this->period(), $employee, $reviewer);
        $items = $review->items()->with('kpi')->get();

        $output = $items->firstWhere('kpi.name', 'Output');
        $timeliness = $items->firstWhere('kpi.name', 'Timeliness');

        $service->scoreItem($review, $output->id, 90, null);
        $review = $service->scoreItem($review, $timeliness->id, 70, null);

        // (90 * 60 + 70 * 40) / 100 = 82
        $this->assertSame('82.00', (string) $review->overall_score);
    }

    public function test_cannot_submit_until_every_kpi_is_scored()
    {
        $this->seedKpis();
        $employee = $this->employee();
        $reviewer = User::factory()->create();
        $service = app(PerformanceService::class);

        $review = $service->createReview($this->period(), $employee, $reviewer);

        $this->expectException(ValidationException::class);
        $service->submit($review, 'Great quarter');
    }

    public function test_submitting_locks_the_review_from_further_scoring()
    {
        $this->seedKpis();
        $employee = $this->employee();
        $reviewer = User::factory()->create();
        $service = app(PerformanceService::class);

        $review = $service->createReview($this->period(), $employee, $reviewer);
        foreach ($review->items as $item) {
            $review = $service->scoreItem($review, $item->id, 80, null);
        }

        $review = $service->submit($review, 'Solid quarter');
        $this->assertSame('submitted', $review->status->value);

        $item = $review->items()->first();
        $this->expectException(ValidationException::class);
        $service->scoreItem($review, $item->id, 50, null);
    }
}

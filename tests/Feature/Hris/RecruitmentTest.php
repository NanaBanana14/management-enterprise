<?php

namespace Tests\Feature\Hris;

use App\Enums\ApplicantStage;
use App\Models\Applicant;
use App\Models\Department;
use App\Models\Position;
use App\Models\Vacancy;
use App\Services\RecruitmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecruitmentTest extends TestCase
{
    use RefreshDatabase;

    private function applicant(): Applicant
    {
        $department = Department::factory()->create();
        $position = Position::factory()->for($department)->create();
        $vacancy = Vacancy::create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'title' => 'Backend Engineer',
        ]);

        return $vacancy->applicants()->create([
            'name' => 'Test Applicant',
            'email' => 'applicant@example.com',
            'stage' => ApplicantStage::Applied->value,
            'applied_at' => now(),
        ]);
    }

    public function test_applicant_can_move_through_stages()
    {
        $applicant = $this->applicant();

        $applicant = app(RecruitmentService::class)->moveStage($applicant, ApplicantStage::Interview);

        $this->assertSame('interview', $applicant->stage->value);
    }

    public function test_a_terminal_stage_cannot_be_moved_further()
    {
        $applicant = $this->applicant();
        $service = app(RecruitmentService::class);

        $applicant = $service->moveStage($applicant, ApplicantStage::Hired);

        $this->expectException(ValidationException::class);
        $service->moveStage($applicant, ApplicantStage::Applied);
    }
}

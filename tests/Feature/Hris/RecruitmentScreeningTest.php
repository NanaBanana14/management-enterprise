<?php

namespace Tests\Feature\Hris;

use App\Enums\ApplicantStage;
use App\Enums\ScreeningResult;
use App\Enums\TrainingAudience;
use App\Models\Applicant;
use App\Models\Department;
use App\Models\Position;
use App\Models\TrainingCategory;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\RecruitmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecruitmentScreeningTest extends TestCase
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
            'stage' => ApplicantStage::Assessment->value,
            'applied_at' => now(),
        ]);
    }

    private function screeningProgram(): TrainingProgram
    {
        $category = TrainingCategory::create(['name' => 'Recruitment']);

        return TrainingProgram::create([
            'training_category_id' => $category->id,
            'name' => 'New Hire Screening Test',
            'audience' => TrainingAudience::Recruitment->value,
        ]);
    }

    public function test_assigning_a_screening_program_creates_a_pending_result()
    {
        $applicant = $this->applicant();
        $program = $this->screeningProgram();

        $result = app(RecruitmentService::class)->assignScreening($applicant, $program);

        $this->assertSame('pending', $result->result->value);
    }

    public function test_moving_to_hired_is_blocked_while_screening_is_pending()
    {
        $applicant = $this->applicant();
        $program = $this->screeningProgram();
        $service = app(RecruitmentService::class);

        $service->assignScreening($applicant, $program);

        $this->expectException(ValidationException::class);
        $service->moveStage($applicant, ApplicantStage::Hired);
    }

    public function test_moving_to_hired_is_blocked_while_screening_has_failed()
    {
        $applicant = $this->applicant();
        $program = $this->screeningProgram();
        $service = app(RecruitmentService::class);
        $user = User::factory()->create();

        $result = $service->assignScreening($applicant, $program);
        $service->recordScreeningResult($result, ScreeningResult::Failed, null, $user);

        $this->expectException(ValidationException::class);
        $service->moveStage($applicant, ApplicantStage::Hired);
    }

    public function test_moving_to_hired_succeeds_once_screening_has_passed()
    {
        $applicant = $this->applicant();
        $program = $this->screeningProgram();
        $service = app(RecruitmentService::class);
        $user = User::factory()->create();

        $result = $service->assignScreening($applicant, $program);
        $service->recordScreeningResult($result, ScreeningResult::Passed, null, $user);

        $applicant = $service->moveStage($applicant, ApplicantStage::Hired);

        $this->assertSame('hired', $applicant->stage->value);
    }

    public function test_an_applicant_with_no_screening_assigned_can_still_be_hired()
    {
        $applicant = $this->applicant();

        $applicant = app(RecruitmentService::class)->moveStage($applicant, ApplicantStage::Hired);

        $this->assertSame('hired', $applicant->stage->value);
    }
}

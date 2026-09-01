<?php

namespace Database\Seeders;

use App\Enums\ApplicantStage;
use App\Enums\ScreeningResult;
use App\Enums\TrainingAudience;
use App\Enums\TrainingMaterialType;
use App\Models\Applicant;
use App\Models\Department;
use App\Models\Employee;
use App\Models\TrainingCategory;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\RecruitmentService;
use App\Services\TrainingService;
use Illuminate\Database\Seeder;

class TrainingSeeder extends Seeder
{
    private const CATEGORIES = [
        'Technical' => [
            ['name' => 'Advanced Excel', 'provider' => 'Internal', 'duration_hours' => 8],
            ['name' => 'Cloud Fundamentals', 'provider' => 'Coursera', 'duration_hours' => 20],
        ],
        'Leadership' => [
            ['name' => 'Managing People', 'provider' => 'Internal', 'duration_hours' => 12, 'department' => true],
        ],
        'Compliance' => [
            ['name' => 'Workplace Safety', 'provider' => 'Internal', 'duration_hours' => 4],
        ],
    ];

    private const MATERIALS = [
        'Advanced Excel' => [
            ['title' => 'Course Overview', 'type' => 'text', 'body' => 'Welcome to Advanced Excel. This course covers pivot tables, macros, and financial modeling techniques used across the company.'],
            ['title' => 'Pivot Tables Walkthrough', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ'],
        ],
        'Workplace Safety' => [
            ['title' => 'Safety Policy', 'type' => 'text', 'body' => 'All employees must complete this module annually. Report hazards to your supervisor immediately.'],
        ],
    ];

    public function run(): void
    {
        $trainingService = app(TrainingService::class);
        $recruitmentService = app(RecruitmentService::class);

        foreach (self::CATEGORIES as $categoryName => $programs) {
            $category = TrainingCategory::query()->firstOrCreate(['name' => $categoryName]);

            foreach ($programs as $program) {
                $department = ($program['department'] ?? false) ? Department::query()->inRandomOrder()->first() : null;

                $trainingProgram = TrainingProgram::query()->firstOrCreate(
                    ['training_category_id' => $category->id, 'name' => $program['name']],
                    [
                        'provider' => $program['provider'],
                        'duration_hours' => $program['duration_hours'],
                        'department_id' => $department?->id,
                        'audience' => TrainingAudience::Staff->value,
                    ],
                );

                Employee::query()->inRandomOrder()->limit(5)->get()->each(
                    fn (Employee $employee) => $trainingProgram->enrollments()->firstOrCreate(
                        ['employee_id' => $employee->id],
                        ['status' => fake()->randomElement(['enrolled', 'in_progress', 'completed']), 'enrolled_at' => now()->subDays(fake()->numberBetween(5, 60))],
                    )
                );

                if ($trainingProgram->materials()->exists()) {
                    continue;
                }

                foreach (self::MATERIALS[$program['name']] ?? [] as $order => $material) {
                    $trainingService->addMaterial(
                        $trainingProgram,
                        $material['title'],
                        TrainingMaterialType::from($material['type']),
                        $material['body'] ?? null,
                        $material['video_url'] ?? null,
                        null,
                        $order,
                    );
                }
            }
        }

        $recruitmentCategory = TrainingCategory::query()->firstOrCreate(['name' => 'Recruitment']);
        $screeningProgram = TrainingProgram::query()->firstOrCreate(
            ['training_category_id' => $recruitmentCategory->id, 'name' => 'New Hire Screening Test'],
            [
                'provider' => 'Internal',
                'duration_hours' => 2,
                'audience' => TrainingAudience::Recruitment->value,
                'description' => 'A short aptitude and culture-fit test every candidate must pass before being hired.',
            ],
        );

        $admin = User::where('email', 'admin@nexa.test')->first();

        if (! $admin) {
            return;
        }

        $applicantsInAssessment = Applicant::where('stage', ApplicantStage::Assessment->value)->limit(2)->get();

        if ($passing = $applicantsInAssessment->get(0)) {
            $result = $recruitmentService->assignScreening($passing, $screeningProgram);
            $recruitmentService->recordScreeningResult($result, ScreeningResult::Passed, 'Strong performance across all sections.', $admin);
            $recruitmentService->moveStage($passing, ApplicantStage::Hired);
        }

        if ($pending = $applicantsInAssessment->get(1)) {
            $recruitmentService->assignScreening($pending, $screeningProgram);
        }
    }
}

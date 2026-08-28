<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\TrainingCategory;
use App\Models\TrainingProgram;
use Illuminate\Database\Seeder;

class TrainingSeeder extends Seeder
{
    private const CATEGORIES = [
        'Technical' => [
            ['name' => 'Advanced Excel', 'provider' => 'Internal', 'duration_hours' => 8],
            ['name' => 'Cloud Fundamentals', 'provider' => 'Coursera', 'duration_hours' => 20],
        ],
        'Leadership' => [
            ['name' => 'Managing People', 'provider' => 'Internal', 'duration_hours' => 12],
        ],
        'Compliance' => [
            ['name' => 'Workplace Safety', 'provider' => 'Internal', 'duration_hours' => 4],
        ],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $categoryName => $programs) {
            $category = TrainingCategory::query()->firstOrCreate(['name' => $categoryName]);

            foreach ($programs as $program) {
                $trainingProgram = TrainingProgram::query()->firstOrCreate(
                    ['training_category_id' => $category->id, 'name' => $program['name']],
                    ['provider' => $program['provider'], 'duration_hours' => $program['duration_hours']],
                );

                Employee::query()->inRandomOrder()->limit(5)->get()->each(
                    fn (Employee $employee) => $trainingProgram->enrollments()->firstOrCreate(
                        ['employee_id' => $employee->id],
                        ['status' => fake()->randomElement(['enrolled', 'in_progress', 'completed']), 'enrolled_at' => now()->subDays(fake()->numberBetween(5, 60))],
                    )
                );
            }
        }
    }
}

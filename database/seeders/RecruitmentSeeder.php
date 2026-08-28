<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Vacancy;
use Illuminate\Database\Seeder;

class RecruitmentSeeder extends Seeder
{
    public function run(): void
    {
        Department::query()->inRandomOrder()->limit(3)->get()->each(function (Department $department) {
            $position = $department->positions()->inRandomOrder()->first();

            if (! $position) {
                return;
            }

            $vacancy = Vacancy::create([
                'department_id' => $department->id,
                'position_id' => $position->id,
                'title' => $position->name,
                'description' => fake()->paragraph(),
            ]);

            collect(range(1, fake()->numberBetween(2, 5)))->each(fn () => $vacancy->applicants()->create([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'phone' => fake()->numerify('08##########'),
                'stage' => fake()->randomElement(['applied', 'screening', 'interview', 'assessment']),
                'applied_at' => now()->subDays(fake()->numberBetween(1, 30)),
            ]));
        });
    }
}

<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->jobTitle();
        $min = fake()->numberBetween(5, 15) * 1_000_000;

        return [
            'department_id' => Department::factory(),
            'name' => $title,
            'code' => strtoupper(fake()->unique()->bothify('POS-###')),
            'description' => fake()->sentence(10),
            'salary_min' => $min,
            'salary_max' => $min + fake()->numberBetween(5, 20) * 1_000_000,
        ];
    }
}

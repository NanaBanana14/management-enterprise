<?php

namespace Database\Factories;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->name();
        $salary = fake()->numberBetween(6, 40) * 1_000_000;

        return [
            'department_id' => Department::factory(),
            'position_id' => Position::factory(),
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('08##########'),
            'employment_type' => fake()->randomElement(EmploymentType::cases()),
            'join_date' => fake()->dateTimeBetween('-6 years', '-1 month'),
            'employment_status' => EmploymentStatus::Active,
            'basic_salary' => $salary,
            'address' => fake()->address(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->numerify('08##########'),
            'emergency_contact_relationship' => fake()->randomElement(['Spouse', 'Parent', 'Sibling', 'Friend']),
        ];
    }
}

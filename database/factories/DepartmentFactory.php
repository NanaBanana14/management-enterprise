<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Engineering', 'Human Resources', 'Finance', 'Sales', 'Marketing',
            'Operations', 'Customer Support', 'Procurement', 'Warehouse & Logistics', 'Legal',
        ]);

        return [
            'name' => $name,
            'code' => strtoupper(substr(str_replace([' ', '&'], '', $name), 0, 4)),
            'description' => fake()->sentence(12),
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Database\Seeder;

class HrisSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::factory(10)->create();

        $departments->each(function (Department $department) {
            $positions = Position::factory(fake()->numberBetween(2, 4))
                ->for($department)
                ->create();

            $headcount = fake()->numberBetween(4, 9);
            $manager = null;

            for ($i = 0; $i < $headcount; $i++) {
                $employee = Employee::factory()->create([
                    'department_id' => $department->id,
                    'position_id' => $positions->random()->id,
                    'manager_id' => $manager?->id,
                ]);

                $manager ??= $employee;
            }

            $department->update(['manager_id' => $manager->id]);
        });
    }
}

<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Demo accounts, one per role. Password for every account is "password" —
     * documented in the README as the demo credential set.
     */
    private const DEMO_USERS = [
        ['name' => 'Raka Wirawan', 'email' => 'admin@nexa.test', 'role' => 'Super Admin'],
        ['name' => 'Dinda Kusuma', 'email' => 'hr.manager@nexa.test', 'role' => 'HR Manager'],
        ['name' => 'Bagas Prasetyo', 'email' => 'hr.staff@nexa.test', 'role' => 'HR Staff'],
        ['name' => 'Salsa Amelia', 'email' => 'finance.manager@nexa.test', 'role' => 'Finance Manager'],
        ['name' => 'Fajar Nugroho', 'email' => 'finance.staff@nexa.test', 'role' => 'Finance Staff'],
        ['name' => 'Teguh Santoso', 'email' => 'warehouse.manager@nexa.test', 'role' => 'Warehouse Manager'],
        ['name' => 'Intan Permata', 'email' => 'purchasing.staff@nexa.test', 'role' => 'Purchasing Staff'],
        ['name' => 'Rizky Maulana', 'email' => 'sales.staff@nexa.test', 'role' => 'Sales Staff'],
        ['name' => 'Wulan Puspita', 'email' => 'employee@nexa.test', 'role' => 'Employee'],
    ];

    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(HrisSeeder::class);
        $this->call(AttendanceSeeder::class);
        $this->call(LeaveTypeSeeder::class);
        $this->call(KpiSeeder::class);
        $this->call(RecruitmentSeeder::class);
        $this->call(TrainingSeeder::class);

        foreach (self::DEMO_USERS as $demo) {
            $user = User::factory()
                ->create([
                    'name' => $demo['name'],
                    'email' => $demo['email'],
                    'email_verified_at' => now(),
                ]);

            $user->assignRole($demo['role']);

            if ($demo['role'] === 'Employee') {
                $department = Department::query()->inRandomOrder()->first();

                Employee::factory()->create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'department_id' => $department->id,
                    'position_id' => $department->positions()->inRandomOrder()->first()->id,
                ]);
            }
        }

        $this->call(LeaveRequestSeeder::class);
        $this->call(ChartOfAccountsSeeder::class);
        $this->call(ErpSeeder::class);
    }
}

<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    private const TYPES = [
        ['name' => 'Annual Leave', 'code' => 'ANNUAL', 'default_days_per_year' => 12, 'is_paid' => true],
        ['name' => 'Sick Leave', 'code' => 'SICK', 'default_days_per_year' => 12, 'is_paid' => true],
        ['name' => 'Unpaid Leave', 'code' => 'UNPAID', 'default_days_per_year' => 0, 'is_paid' => false],
    ];

    public function run(): void
    {
        foreach (self::TYPES as $type) {
            LeaveType::query()->updateOrCreate(['code' => $type['code']], $type);
        }
    }
}

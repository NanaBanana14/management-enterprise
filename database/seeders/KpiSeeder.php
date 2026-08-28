<?php

namespace Database\Seeders;

use App\Models\Kpi;
use App\Models\KpiCategory;
use Illuminate\Database\Seeder;

class KpiSeeder extends Seeder
{
    private const CATEGORIES = [
        'Productivity' => [
            ['name' => 'Task Completion Rate', 'weight' => 25],
            ['name' => 'On-time Delivery', 'weight' => 20],
        ],
        'Quality' => [
            ['name' => 'Error Rate', 'weight' => 15],
            ['name' => 'Peer Review Score', 'weight' => 15],
        ],
        'Collaboration' => [
            ['name' => 'Team Feedback', 'weight' => 15],
            ['name' => 'Cross-team Contribution', 'weight' => 10],
        ],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $categoryName => $kpis) {
            $category = KpiCategory::query()->firstOrCreate(['name' => $categoryName]);

            foreach ($kpis as $kpi) {
                Kpi::query()->firstOrCreate(
                    ['kpi_category_id' => $category->id, 'name' => $kpi['name']],
                    ['weight' => $kpi['weight']],
                );
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Enums\AssetCategory;
use App\Models\Employee;
use App\Models\FixedAsset;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\FixedAssetService;
use Illuminate\Database\Seeder;

class FixedAssetSeeder extends Seeder
{
    /**
     * Registered assets, spread across categories and custody (employees vs warehouse).
     * "employee_index" refers to a position in the fetched employees list; null means
     * the asset stays with the warehouse instead of being checked out to a person.
     */
    private const ASSETS = [
        ['name' => 'Dell Latitude Laptop', 'category' => 'equipment', 'employee_index' => 0, 'months_ago' => 10, 'cost' => 15_000_000, 'salvage' => 1_500_000, 'life' => 36],
        ['name' => 'Toyota Avanza (Fleet Car)', 'category' => 'vehicle', 'employee_index' => null, 'months_ago' => 8, 'cost' => 250_000_000, 'salvage' => 50_000_000, 'life' => 60],
        ['name' => 'Office Desk & Chair Set', 'category' => 'furniture', 'employee_index' => null, 'months_ago' => 14, 'cost' => 8_000_000, 'salvage' => 500_000, 'life' => 60],
        ['name' => 'Server Rack Unit', 'category' => 'equipment', 'employee_index' => null, 'months_ago' => 5, 'cost' => 45_000_000, 'salvage' => 5_000_000, 'life' => 48],
        ['name' => 'HP LaserJet Printer', 'category' => 'equipment', 'employee_index' => 1, 'months_ago' => 20, 'cost' => 6_000_000, 'salvage' => 500_000, 'life' => 24],
        ['name' => 'MacBook Pro (to be sold)', 'category' => 'equipment', 'employee_index' => 2, 'months_ago' => 10, 'cost' => 10_000_000, 'salvage' => 1_000_000, 'life' => 24],
    ];

    public function run(): void
    {
        if (FixedAsset::exists()) {
            return;
        }

        $admin = User::where('email', 'admin@nexa.test')->first();
        $warehouse = Warehouse::where('code', 'WH-01')->first();
        $employees = Employee::where('employment_status', 'active')->orderBy('id')->take(3)->get();

        if (! $admin || ! $warehouse) {
            return;
        }

        $service = app(FixedAssetService::class);
        $created = [];

        foreach (self::ASSETS as $plan) {
            $employee = $plan['employee_index'] !== null ? $employees->get($plan['employee_index']) : null;

            $asset = $service->register(
                $plan['name'],
                AssetCategory::from($plan['category']),
                null,
                $employee ? null : $warehouse,
                $employee,
                now()->subMonths($plan['months_ago'])->toDateString(),
                $plan['cost'],
                $plan['salvage'],
                $plan['life'],
                $admin,
            );

            $created[$plan['name']] = $asset;
        }

        $service->runDepreciation(now()->subMonths(2)->startOfMonth()->toDateString(), $admin);
        $service->runDepreciation(now()->subMonth()->startOfMonth()->toDateString(), $admin);

        $service->dispose($created['MacBook Pro (to be sold)'], now()->toDateString(), 5_000_000, $admin);
    }
}

<?php

namespace Database\Seeders;

use App\Enums\OpportunityStage;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\OpportunityService;
use Illuminate\Database\Seeder;

class OpportunitySeeder extends Seeder
{
    private const OPPORTUNITIES = [
        ['title' => 'Office refresh — chairs & desks', 'customer_index' => 0, 'source' => 'Referral', 'stage' => 'prospecting', 'lines' => [['sku' => 'SKU-001', 'qty' => 10], ['sku' => 'SKU-002', 'qty' => 4]]],
        ['title' => 'Branch office peripherals', 'customer_index' => 1, 'source' => 'Website', 'stage' => 'qualified', 'lines' => [['sku' => 'SKU-004', 'qty' => 15], ['sku' => 'SKU-006', 'qty' => 15]]],
        ['title' => 'Annual stationery supply', 'customer_index' => 0, 'source' => 'Cold outreach', 'stage' => 'proposal', 'lines' => [['sku' => 'SKU-003', 'qty' => 50]]],
        ['title' => 'Filing cabinet upgrade', 'customer_index' => 1, 'source' => 'Referral', 'stage' => 'negotiation', 'lines' => [['sku' => 'SKU-008', 'qty' => 3]]],
        ['title' => 'Ergonomic accessories bundle', 'customer_index' => 0, 'source' => 'Website', 'stage' => 'won', 'lines' => [['sku' => 'SKU-005', 'qty' => 2], ['sku' => 'SKU-007', 'qty' => 2]]],
        ['title' => 'Second branch full setup', 'customer_index' => 1, 'source' => 'Cold outreach', 'stage' => 'lost', 'lines' => [['sku' => 'SKU-001', 'qty' => 20], ['sku' => 'SKU-002', 'qty' => 10]]],
    ];

    public function run(): void
    {
        if (Opportunity::exists()) {
            return;
        }

        $admin = User::where('email', 'admin@nexa.test')->first();
        $salesStaff = User::where('email', 'sales.staff@nexa.test')->first();
        $warehouse = Warehouse::where('code', 'WH-01')->first();
        $customers = Customer::orderBy('id')->get();

        if (! $admin || ! $warehouse || $customers->isEmpty()) {
            return;
        }

        $service = app(OpportunityService::class);

        foreach (self::OPPORTUNITIES as $plan) {
            $customer = $customers->get($plan['customer_index']);

            if (! $customer) {
                continue;
            }

            $opportunity = $service->create(
                $customer,
                $warehouse,
                $plan['title'],
                $this->resolveLines($plan['lines']),
                $plan['source'],
                now()->addDays(30)->toDateString(),
                $salesStaff?->id,
                $admin,
            );

            match ($plan['stage']) {
                'qualified', 'proposal', 'negotiation' => $service->moveStage($opportunity, OpportunityStage::from($plan['stage'])),
                'won' => $service->markWon($opportunity, $admin),
                'lost' => $service->moveStage($opportunity, OpportunityStage::Lost),
                default => null,
            };

            $service->addNote($opportunity, $admin, 'Initial contact made, following up on requirements.');
        }
    }

    private function resolveLines(array $lines): array
    {
        return collect($lines)
            ->map(function ($line) {
                $product = Product::where('sku', $line['sku'])->first();

                if (! $product) {
                    return null;
                }

                return [
                    'product_id' => $product->id,
                    'quantity' => $line['qty'],
                    'unit_price' => (float) $product->price,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}

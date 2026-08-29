<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SalesOrderService;
use Illuminate\Database\Seeder;

class SalesOrderSeeder extends Seeder
{
    /**
     * Past orders, fulfilled immediately so each produces a real Invoice + journal entry.
     */
    private const FULFILLED = [
        ['days_ago' => 21, 'customer_index' => 0, 'lines' => [['sku' => 'SKU-001', 'qty' => 3], ['sku' => 'SKU-004', 'qty' => 5]]],
        ['days_ago' => 14, 'customer_index' => 1, 'lines' => [['sku' => 'SKU-006', 'qty' => 4]]],
        ['days_ago' => 6, 'customer_index' => 0, 'lines' => [['sku' => 'SKU-002', 'qty' => 2], ['sku' => 'SKU-008', 'qty' => 1]]],
    ];

    /**
     * Left as drafts, so a live "fulfill" can be demoed against real seeded data.
     */
    private const DRAFT = [
        ['customer_index' => 1, 'lines' => [['sku' => 'SKU-003', 'qty' => 10], ['sku' => 'SKU-005', 'qty' => 3]]],
    ];

    public function run(): void
    {
        if (SalesOrder::exists()) {
            return;
        }

        $admin = User::where('email', 'admin@nexa.test')->first();
        $warehouse = Warehouse::where('code', 'WH-01')->first();
        $customers = Customer::orderBy('id')->get();

        if (! $admin || ! $warehouse || $customers->isEmpty()) {
            return;
        }

        $service = app(SalesOrderService::class);

        foreach (self::FULFILLED as $plan) {
            $customer = $customers->get($plan['customer_index']);

            if (! $customer) {
                continue;
            }

            $order = $service->create(
                $customer,
                $warehouse,
                now()->subDays($plan['days_ago'])->toDateString(),
                $this->resolveLines($plan['lines']),
                'Seeded demo order',
                $admin,
            );

            $service->fulfill($order, $admin);
        }

        foreach (self::DRAFT as $plan) {
            $customer = $customers->get($plan['customer_index']);

            if (! $customer) {
                continue;
            }

            $service->create(
                $customer,
                $warehouse,
                now()->toDateString(),
                $this->resolveLines($plan['lines']),
                'Awaiting fulfillment',
                $admin,
            );
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

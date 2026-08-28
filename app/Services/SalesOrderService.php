<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesOrderService
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * @param  array<int, array{product_id: int, quantity: float, unit_price: float}>  $lines
     */
    public function create(Customer $customer, Warehouse $warehouse, string $date, array $lines, ?string $notes, User $user): SalesOrder
    {
        $lines = array_values(array_filter($lines, fn ($line) => (float) $line['quantity'] > 0));

        if (count($lines) === 0) {
            throw ValidationException::withMessages(['lines' => 'A sales order needs at least one line.']);
        }

        return DB::transaction(function () use ($customer, $warehouse, $date, $lines, $notes, $user) {
            $number = 'SO-'.now()->format('Ym').'-'.str_pad((string) (SalesOrder::count() + 1), 4, '0', STR_PAD_LEFT);

            $order = SalesOrder::create([
                'number' => $number,
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'date' => $date,
                'status' => 'draft',
                'notes' => $notes,
                'created_by' => $user->id,
            ]);

            foreach ($lines as $line) {
                $order->lines()->create($line);
            }

            return $order->load('lines');
        });
    }

    public function fulfill(SalesOrder $order, User $user): SalesOrder
    {
        return DB::transaction(function () use ($order, $user) {
            $order = SalesOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== 'draft') {
                throw ValidationException::withMessages(['status' => 'Only draft sales orders can be fulfilled.']);
            }

            foreach ($order->lines as $line) {
                $this->inventory->adjust(
                    Product::findOrFail($line->product_id),
                    $order->warehouse,
                    'out',
                    (float) $line->quantity,
                    "Fulfilled {$order->number}",
                    $user,
                );
            }

            $order->update(['status' => 'fulfilled']);

            return $order;
        });
    }
}

<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    public function __construct(private InventoryService $inventory, private PayableService $payables) {}

    /**
     * @param  array<int, array{product_id: int, quantity: float, unit_price: float}>  $lines
     */
    public function create(Supplier $supplier, Warehouse $warehouse, string $date, array $lines, ?string $notes, User $user): PurchaseOrder
    {
        $lines = array_values(array_filter($lines, fn ($line) => (float) $line['quantity'] > 0));

        if (count($lines) === 0) {
            throw ValidationException::withMessages(['lines' => 'A purchase order needs at least one line.']);
        }

        return DB::transaction(function () use ($supplier, $warehouse, $date, $lines, $notes, $user) {
            $number = 'PO-'.now()->format('Ym').'-'.str_pad((string) (PurchaseOrder::count() + 1), 4, '0', STR_PAD_LEFT);

            $order = PurchaseOrder::create([
                'number' => $number,
                'supplier_id' => $supplier->id,
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

    public function receive(PurchaseOrder $order, User $user): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $user) {
            $order = PurchaseOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== 'draft') {
                throw ValidationException::withMessages(['status' => 'Only draft purchase orders can be received.']);
            }

            foreach ($order->lines as $line) {
                $this->inventory->adjust(
                    Product::findOrFail($line->product_id),
                    $order->warehouse,
                    'in',
                    (float) $line->quantity,
                    "Received {$order->number}",
                    $user,
                );
            }

            $expenseAccount = Account::where('type', 'expense')->orderBy('code')->firstOrFail();
            $total = (float) $order->lines->sum(fn ($line) => $line->quantity * $line->unit_price);

            $payable = $this->payables->create(
                $order->supplier,
                $expenseAccount,
                $total,
                now()->toDateString(),
                now()->addDays(30)->toDateString(),
                "Payable for purchase order {$order->number}",
                $user,
            );

            $order->update(['status' => 'received', 'payable_id' => $payable->id]);

            return $order;
        });
    }
}

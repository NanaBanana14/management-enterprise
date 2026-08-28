<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function adjust(Product $product, Warehouse $warehouse, string $type, float $quantity, ?string $note, User $user): StockMovement
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be greater than zero.']);
        }

        return DB::transaction(function () use ($product, $warehouse, $type, $quantity, $note, $user) {
            $stock = $this->lockStock($product, $warehouse);

            $delta = $type === 'in' ? $quantity : -$quantity;

            if ($type === 'out' && $stock->quantity < $quantity) {
                throw ValidationException::withMessages(['quantity' => 'Insufficient stock for this movement.']);
            }

            $stock->increment('quantity', $delta);

            return StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => $type,
                'quantity' => $quantity,
                'reference' => 'ADJ-'.Str::upper(Str::random(6)),
                'note' => $note,
                'created_by' => $user->id,
            ]);
        });
    }

    public function transfer(Product $product, Warehouse $from, Warehouse $to, float $quantity, ?string $note, User $user): void
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be greater than zero.']);
        }

        if ($from->id === $to->id) {
            throw ValidationException::withMessages(['to_warehouse_id' => 'Source and destination warehouses must differ.']);
        }

        DB::transaction(function () use ($product, $from, $to, $quantity, $note, $user) {
            $fromStock = $this->lockStock($product, $from);

            if ($fromStock->quantity < $quantity) {
                throw ValidationException::withMessages(['quantity' => 'Insufficient stock at source warehouse.']);
            }

            $toStock = $this->lockStock($product, $to);

            $fromStock->decrement('quantity', $quantity);
            $toStock->increment('quantity', $quantity);

            $reference = 'TRF-'.Str::upper(Str::random(6));

            StockMovement::create([
                'product_id' => $product->id, 'warehouse_id' => $from->id,
                'type' => 'transfer_out', 'quantity' => $quantity,
                'reference' => $reference, 'note' => $note, 'created_by' => $user->id,
            ]);

            StockMovement::create([
                'product_id' => $product->id, 'warehouse_id' => $to->id,
                'type' => 'transfer_in', 'quantity' => $quantity,
                'reference' => $reference, 'note' => $note, 'created_by' => $user->id,
            ]);
        });
    }

    private function lockStock(Product $product, Warehouse $warehouse): ProductStock
    {
        $stock = ProductStock::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            return $stock;
        }

        return ProductStock::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 0,
        ]);
    }
}

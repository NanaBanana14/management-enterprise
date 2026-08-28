<?php

namespace Tests\Feature\Erp;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_receiving_a_purchase_order_increases_stock()
    {
        $supplier = Supplier::create(['name' => 'Test Supplier']);
        $warehouse = Warehouse::create(['code' => 'WH-P1', 'name' => 'Test Warehouse']);
        $product = Product::create(['sku' => 'SKU-P1', 'name' => 'Test Product', 'unit' => 'pcs', 'price' => 1000]);
        $user = User::factory()->create();

        $service = app(PurchaseOrderService::class);
        $order = $service->create($supplier, $warehouse, now()->toDateString(), [
            ['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 1000],
        ], null, $user);

        $service->receive($order, $user);

        $this->assertSame('received', $order->fresh()->status);
        $this->assertSame(10.0, $product->fresh()->totalStock());
    }
}

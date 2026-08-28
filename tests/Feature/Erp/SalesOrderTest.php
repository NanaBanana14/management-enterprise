<?php

namespace Tests\Feature\Erp;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\SalesOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SalesOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_fulfilling_a_sales_order_decreases_stock()
    {
        $customer = Customer::create(['name' => 'Test Customer']);
        $warehouse = Warehouse::create(['code' => 'WH-S1', 'name' => 'Test Warehouse']);
        $product = Product::create(['sku' => 'SKU-S1', 'name' => 'Test Product', 'unit' => 'pcs', 'price' => 1000]);
        $user = User::factory()->create();

        app(InventoryService::class)->adjust($product, $warehouse, 'in', 20, null, $user);

        $service = app(SalesOrderService::class);
        $order = $service->create($customer, $warehouse, now()->toDateString(), [
            ['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 2000],
        ], null, $user);

        $service->fulfill($order, $user);

        $this->assertSame('fulfilled', $order->fresh()->status);
        $this->assertSame(15.0, $product->fresh()->totalStock());
    }

    public function test_fulfilling_beyond_available_stock_is_rejected()
    {
        $customer = Customer::create(['name' => 'Test Customer']);
        $warehouse = Warehouse::create(['code' => 'WH-S2', 'name' => 'Test Warehouse']);
        $product = Product::create(['sku' => 'SKU-S2', 'name' => 'Test Product', 'unit' => 'pcs', 'price' => 1000]);
        $user = User::factory()->create();

        $service = app(SalesOrderService::class);
        $order = $service->create($customer, $warehouse, now()->toDateString(), [
            ['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 2000],
        ], null, $user);

        $this->expectException(ValidationException::class);

        $service->fulfill($order, $user);
    }
}

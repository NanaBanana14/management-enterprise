<?php

namespace Tests\Feature\Erp;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_in_increases_quantity()
    {
        $product = Product::create(['sku' => 'SKU-T1', 'name' => 'Test Product', 'unit' => 'pcs', 'price' => 1000]);
        $warehouse = Warehouse::create(['code' => 'WH-T1', 'name' => 'Test Warehouse']);
        $user = User::factory()->create();

        app(InventoryService::class)->adjust($product, $warehouse, 'in', 10, null, $user);

        $this->assertSame(10.0, $product->fresh()->totalStock());
    }

    public function test_stock_out_beyond_available_is_rejected()
    {
        $product = Product::create(['sku' => 'SKU-T2', 'name' => 'Test Product', 'unit' => 'pcs', 'price' => 1000]);
        $warehouse = Warehouse::create(['code' => 'WH-T2', 'name' => 'Test Warehouse']);
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        app(InventoryService::class)->adjust($product, $warehouse, 'out', 5, null, $user);
    }

    public function test_transfer_moves_quantity_between_warehouses()
    {
        $product = Product::create(['sku' => 'SKU-T3', 'name' => 'Test Product', 'unit' => 'pcs', 'price' => 1000]);
        $from = Warehouse::create(['code' => 'WH-T3A', 'name' => 'Source Warehouse']);
        $to = Warehouse::create(['code' => 'WH-T3B', 'name' => 'Destination Warehouse']);
        $user = User::factory()->create();

        $service = app(InventoryService::class);
        $service->adjust($product, $from, 'in', 20, null, $user);
        $service->transfer($product, $from, $to, 8, null, $user);

        $this->assertSame(12.0, (float) $product->stocks()->where('warehouse_id', $from->id)->first()->quantity);
        $this->assertSame(8.0, (float) $product->stocks()->where('warehouse_id', $to->id)->first()->quantity);
    }
}

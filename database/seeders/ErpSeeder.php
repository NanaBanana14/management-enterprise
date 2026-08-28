<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;

class ErpSeeder extends Seeder
{
    private const WAREHOUSES = [
        ['WH-01', 'Main Warehouse', 'Jl. Industri No. 1, Jakarta'],
        ['WH-02', 'Branch Warehouse', 'Jl. Gatot Subroto No. 45, Bandung'],
    ];

    private const PRODUCTS = [
        ['SKU-001', 'Office Chair', 'pcs', 850000],
        ['SKU-002', 'Standing Desk', 'pcs', 2200000],
        ['SKU-003', 'A4 Paper Ream', 'ream', 45000],
        ['SKU-004', 'Wireless Mouse', 'pcs', 125000],
        ['SKU-005', 'Laptop Stand', 'pcs', 175000],
    ];

    private const SUPPLIERS = [
        ['PT Sumber Furnitur', 'Andi Wijaya', '021-5551234', 'sales@sumberfurnitur.test'],
        ['CV Alat Kantor Jaya', 'Rina Susanti', '021-5555678', 'order@alatkantorjaya.test'],
    ];

    private const CUSTOMERS = [
        ['PT Cahaya Abadi', 'Budi Santoso', '021-7771234', 'purchasing@cahayaabadi.test'],
        ['CV Mitra Sejahtera', 'Siti Aminah', '021-7775678', 'admin@mitrasejahtera.test'],
    ];

    public function run(): void
    {
        foreach (self::WAREHOUSES as [$code, $name, $address]) {
            Warehouse::query()->updateOrCreate(['code' => $code], ['name' => $name, 'address' => $address]);
        }

        foreach (self::PRODUCTS as [$sku, $name, $unit, $price]) {
            Product::query()->updateOrCreate(['sku' => $sku], ['name' => $name, 'unit' => $unit, 'price' => $price]);
        }

        foreach (self::SUPPLIERS as [$name, $contact, $phone, $email]) {
            Supplier::query()->updateOrCreate(['name' => $name], ['contact_person' => $contact, 'phone' => $phone, 'email' => $email]);
        }

        foreach (self::CUSTOMERS as [$name, $contact, $phone, $email]) {
            Customer::query()->updateOrCreate(['name' => $name], ['contact_person' => $contact, 'phone' => $phone, 'email' => $email]);
        }

        $admin = User::where('email', 'admin@nexa.test')->first();
        $mainWarehouse = Warehouse::where('code', 'WH-01')->first();

        if (! $admin || ! $mainWarehouse) {
            return;
        }

        $inventory = app(InventoryService::class);

        foreach (Product::all() as $product) {
            if ($product->stocks()->exists()) {
                continue;
            }

            $inventory->adjust($product, $mainWarehouse, 'in', 50, 'Initial stock', $admin);
        }
    }
}

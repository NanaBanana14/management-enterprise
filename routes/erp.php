<?php

use App\Http\Controllers\Erp\CustomerController;
use App\Http\Controllers\Erp\InventoryController;
use App\Http\Controllers\Erp\ProductController;
use App\Http\Controllers\Erp\SupplierController;
use App\Http\Controllers\Erp\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::prefix('erp')->name('erp.')->middleware(['auth', 'verified'])->group(function () {
    Route::middleware('permission:product.view')->group(function () {
        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::post('products', [ProductController::class, 'store'])->name('products.store')->middleware('permission:product.manage');
        Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update')->middleware('permission:product.manage');
    });

    Route::middleware('permission:warehouse.view')->group(function () {
        Route::get('warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
        Route::post('warehouses', [WarehouseController::class, 'store'])->name('warehouses.store')->middleware('permission:warehouse.manage');
        Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update')->middleware('permission:warehouse.manage');
    });

    Route::middleware('permission:inventory.view')->group(function () {
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust')->middleware('permission:inventory.adjust');
        Route::post('inventory/transfer', [InventoryController::class, 'transfer'])->name('inventory.transfer')->middleware('permission:inventory.transfer');
    });

    Route::middleware('permission:supplier.view')->group(function () {
        Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store')->middleware('permission:supplier.manage');
        Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update')->middleware('permission:supplier.manage');
    });

    Route::middleware('permission:customer.view')->group(function () {
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::post('customers', [CustomerController::class, 'store'])->name('customers.store')->middleware('permission:customer.manage');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update')->middleware('permission:customer.manage');
    });
});

<?php

use App\Http\Controllers\AssetController;
use Illuminate\Support\Facades\Route;

Route::prefix('assets')->name('assets.')->middleware(['auth', 'verified'])->group(function () {
    Route::middleware('permission:asset.view')->group(function () {
        Route::get('/', [AssetController::class, 'index'])->name('index');
        Route::get('/{asset}', [AssetController::class, 'show'])->name('show');
        Route::post('/', [AssetController::class, 'store'])->name('store')->middleware('permission:asset.create');
        Route::post('/{asset}/reassign', [AssetController::class, 'reassign'])->name('reassign')->middleware('permission:asset.create');
        Route::post('/depreciation-runs', [AssetController::class, 'runDepreciation'])->name('depreciation.run')->middleware('permission:asset.manage');
        Route::post('/{asset}/dispose', [AssetController::class, 'dispose'])->name('dispose')->middleware('permission:asset.manage');
    });
});

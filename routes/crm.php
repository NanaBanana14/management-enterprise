<?php

use App\Http\Controllers\Crm\OpportunityController;
use Illuminate\Support\Facades\Route;

Route::prefix('crm')->name('crm.')->middleware(['auth', 'verified'])->group(function () {
    Route::middleware('permission:opportunity.view')->group(function () {
        Route::get('opportunities', [OpportunityController::class, 'index'])->name('opportunities.index');
        Route::post('opportunities', [OpportunityController::class, 'store'])->name('opportunities.store')->middleware('permission:opportunity.manage');
        Route::get('opportunities/{opportunity}', [OpportunityController::class, 'show'])->name('opportunities.show');
        Route::post('opportunities/{opportunity}/stage', [OpportunityController::class, 'moveStage'])->name('opportunities.stage')->middleware('permission:opportunity.manage');
        Route::post('opportunities/{opportunity}/win', [OpportunityController::class, 'markWon'])->name('opportunities.win')->middleware('permission:opportunity.manage');
        Route::post('opportunities/{opportunity}/notes', [OpportunityController::class, 'storeNote'])->name('opportunities.notes.store')->middleware('permission:opportunity.manage');
    });
});

<?php

use App\Http\Controllers\Finance\AccountController;
use App\Http\Controllers\Finance\JournalEntryController;
use App\Http\Controllers\Finance\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('finance')->name('finance.')->middleware(['auth', 'verified'])->group(function () {
    Route::middleware('permission:account.view')->group(function () {
        Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::post('accounts', [AccountController::class, 'store'])->name('accounts.store')->middleware('permission:account.manage');
        Route::put('accounts/{account}', [AccountController::class, 'update'])->name('accounts.update')->middleware('permission:account.manage');
    });

    Route::middleware('permission:journal.view')->group(function () {
        Route::get('journal', [JournalEntryController::class, 'index'])->name('journal.index');
        Route::get('journal/create', [JournalEntryController::class, 'create'])->name('journal.create')->middleware('permission:journal.create');
        Route::post('journal', [JournalEntryController::class, 'store'])->name('journal.store')->middleware('permission:journal.create');
        Route::get('journal/{journalEntry}', [JournalEntryController::class, 'show'])->name('journal.show');
    });

    Route::get('reports', ReportController::class)->name('reports.index')->middleware('permission:report.view');
});

<?php

use App\Http\Controllers\Finance\AccountController;
use App\Http\Controllers\Finance\CashBankController;
use App\Http\Controllers\Finance\InvoiceController;
use App\Http\Controllers\Finance\JournalEntryController;
use App\Http\Controllers\Finance\PayableController;
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

    Route::middleware('permission:cashbank.view')->group(function () {
        Route::get('cashbank', [CashBankController::class, 'index'])->name('cashbank.index');
        Route::post('cashbank/income', [CashBankController::class, 'recordIncome'])->name('cashbank.income')->middleware('permission:income.manage');
        Route::post('cashbank/expense', [CashBankController::class, 'recordExpense'])->name('cashbank.expense')->middleware('permission:expense.manage');
        Route::post('cashbank/transfer', [CashBankController::class, 'transfer'])->name('cashbank.transfer')->middleware('permission:cashbank.manage');
    });

    Route::middleware('permission:invoice.view')->group(function () {
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store')->middleware('permission:invoice.create');
        Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.markPaid')->middleware('permission:invoice.approve');
    });

    Route::middleware('permission:payable.view')->group(function () {
        Route::get('payables', [PayableController::class, 'index'])->name('payables.index');
        Route::post('payables', [PayableController::class, 'store'])->name('payables.store')->middleware('permission:payable.manage');
        Route::post('payables/{payable}/mark-paid', [PayableController::class, 'markPaid'])->name('payables.markPaid')->middleware('permission:payable.manage');
    });

    Route::get('reports', ReportController::class)->name('reports.index')->middleware('permission:report.view');
});

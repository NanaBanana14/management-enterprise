<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(request()->user() ? 'dashboard' : 'login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)
            ->except('show')
            ->middlewareFor(['index'], 'permission:users.view')
            ->middlewareFor(['create', 'store'], 'permission:users.create')
            ->middlewareFor(['edit', 'update'], 'permission:users.update')
            ->middlewareFor(['destroy'], 'permission:users.delete');

        Route::resource('roles', RoleController::class)
            ->except('show')
            ->middlewareFor(['index'], 'permission:roles.view')
            ->middlewareFor(['create', 'store'], 'permission:roles.create')
            ->middlewareFor(['edit', 'update'], 'permission:roles.update')
            ->middlewareFor(['destroy'], 'permission:roles.delete');

        Route::get('audit-logs', AuditLogController::class)
            ->name('audit-logs.index')
            ->middleware('permission:audit.view');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/hris.php';
require __DIR__.'/finance.php';

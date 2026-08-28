<?php

use App\Http\Controllers\Hris\AttendanceController;
use App\Http\Controllers\Hris\DepartmentController;
use App\Http\Controllers\Hris\EmployeeController;
use App\Http\Controllers\Hris\LeaveController;
use App\Http\Controllers\Hris\OvertimeController;
use App\Http\Controllers\Hris\PayrollPeriodController;
use App\Http\Controllers\Hris\PayslipController;
use App\Http\Controllers\Hris\PositionController;
use Illuminate\Support\Facades\Route;

Route::prefix('hris')->name('hris.')->middleware(['auth', 'verified'])->group(function () {
    Route::resource('departments', DepartmentController::class)
        ->except('show')
        ->middlewareFor(['index'], 'permission:department.view')
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'permission:department.manage');

    Route::resource('positions', PositionController::class)
        ->except('show')
        ->middlewareFor(['index'], 'permission:position.view')
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'permission:position.manage');

    Route::resource('employees', EmployeeController::class)
        ->middlewareFor(['index', 'show'], 'permission:employee.view')
        ->middlewareFor(['create', 'store'], 'permission:employee.create')
        ->middlewareFor(['edit', 'update'], 'permission:employee.update')
        ->middlewareFor(['destroy'], 'permission:employee.delete');

    Route::middleware('permission:attendance.view')->group(function () {
        Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.check-in');
        Route::post('attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.check-out');
    });

    Route::middleware('permission:leave.view')->group(function () {
        Route::get('leave', [LeaveController::class, 'index'])->name('leave.index');
        Route::post('leave', [LeaveController::class, 'store'])->name('leave.store')->middleware('permission:leave.create');
        Route::post('leave/{leaveRequest}/approve', [LeaveController::class, 'approve'])->name('leave.approve')->middleware('permission:leave.approve');
        Route::post('leave/{leaveRequest}/reject', [LeaveController::class, 'reject'])->name('leave.reject')->middleware('permission:leave.approve');
        Route::post('leave/{leaveRequest}/cancel', [LeaveController::class, 'cancel'])->name('leave.cancel');
    });

    Route::middleware('permission:overtime.view')->group(function () {
        Route::get('overtime', [OvertimeController::class, 'index'])->name('overtime.index');
        Route::post('overtime', [OvertimeController::class, 'store'])->name('overtime.store')->middleware('permission:overtime.create');
        Route::post('overtime/{overtimeRequest}/approve', [OvertimeController::class, 'approve'])->name('overtime.approve')->middleware('permission:overtime.approve');
        Route::post('overtime/{overtimeRequest}/reject', [OvertimeController::class, 'reject'])->name('overtime.reject')->middleware('permission:overtime.approve');
        Route::post('overtime/{overtimeRequest}/cancel', [OvertimeController::class, 'cancel'])->name('overtime.cancel');
    });

    Route::middleware('permission:payroll.view')->group(function () {
        Route::get('payroll', [PayslipController::class, 'myPayslips'])->name('payroll.mine');
        Route::get('payroll/payslips/{payslip}', [PayslipController::class, 'show'])->name('payroll.payslips.show');
        Route::post('payroll/payslips/{payslip}/items', [PayslipController::class, 'storeItem'])->name('payroll.payslips.items.store');
        Route::delete('payroll/payslips/{payslip}/items/{item}', [PayslipController::class, 'destroyItem'])->name('payroll.payslips.items.destroy');
        Route::post('payroll/payslips/{payslip}/approve', [PayslipController::class, 'approve'])->name('payroll.payslips.approve');

        Route::get('payroll/periods', [PayrollPeriodController::class, 'index'])->name('payroll.periods.index');
        Route::post('payroll/periods', [PayrollPeriodController::class, 'store'])->name('payroll.periods.store');
        Route::get('payroll/periods/{period}', [PayslipController::class, 'index'])->name('payroll.periods.show');
        Route::post('payroll/periods/{period}/generate', [PayrollPeriodController::class, 'generate'])->name('payroll.periods.generate');
    });
});

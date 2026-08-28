<?php

use App\Http\Controllers\Hris\ApplicantController;
use App\Http\Controllers\Hris\AttendanceController;
use App\Http\Controllers\Hris\DepartmentController;
use App\Http\Controllers\Hris\EmployeeController;
use App\Http\Controllers\Hris\KpiController;
use App\Http\Controllers\Hris\LeaveController;
use App\Http\Controllers\Hris\OvertimeController;
use App\Http\Controllers\Hris\PayrollPeriodController;
use App\Http\Controllers\Hris\PayslipController;
use App\Http\Controllers\Hris\PerformancePeriodController;
use App\Http\Controllers\Hris\PerformanceReviewController;
use App\Http\Controllers\Hris\PositionController;
use App\Http\Controllers\Hris\TrainingController;
use App\Http\Controllers\Hris\TrainingEnrollmentController;
use App\Http\Controllers\Hris\VacancyController;
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

    Route::middleware('permission:kpi.view')->group(function () {
        Route::get('kpis', [KpiController::class, 'index'])->name('kpis.index');
        Route::post('kpis/categories', [KpiController::class, 'storeCategory'])->name('kpis.categories.store');
        Route::post('kpis', [KpiController::class, 'storeKpi'])->name('kpis.store');
        Route::delete('kpis/{kpi}', [KpiController::class, 'destroyKpi'])->name('kpis.destroy');
    });

    Route::middleware('permission:performance.view')->group(function () {
        Route::get('performance', [PerformanceReviewController::class, 'myReviews'])->name('performance.mine');
        Route::get('performance/reviews/{performanceReview}', [PerformanceReviewController::class, 'show'])->name('performance.reviews.show');
        Route::post('performance/reviews/{performanceReview}/items/{item}', [PerformanceReviewController::class, 'scoreItem'])->name('performance.reviews.items.score');
        Route::post('performance/reviews/{performanceReview}/submit', [PerformanceReviewController::class, 'submit'])->name('performance.reviews.submit');

        Route::get('performance/periods', [PerformancePeriodController::class, 'index'])->name('performance.periods.index');
        Route::post('performance/periods', [PerformancePeriodController::class, 'store'])->name('performance.periods.store');
        Route::get('performance/periods/{period}', [PerformanceReviewController::class, 'index'])->name('performance.periods.show');
        Route::post('performance/periods/{period}/reviews', [PerformanceReviewController::class, 'store'])->name('performance.periods.reviews.store');
    });

    Route::middleware('permission:recruitment.view')->group(function () {
        Route::get('recruitment/vacancies', [VacancyController::class, 'index'])->name('recruitment.vacancies.index');
        Route::post('recruitment/vacancies', [VacancyController::class, 'store'])->name('recruitment.vacancies.store');
        Route::get('recruitment/vacancies/{vacancy}', [VacancyController::class, 'show'])->name('recruitment.vacancies.show');
        Route::post('recruitment/vacancies/{vacancy}/applicants', [ApplicantController::class, 'store'])->name('recruitment.applicants.store');
        Route::get('recruitment/applicants/{applicant}', [ApplicantController::class, 'show'])->name('recruitment.applicants.show');
        Route::post('recruitment/applicants/{applicant}/stage', [ApplicantController::class, 'moveStage'])->name('recruitment.applicants.stage');
        Route::post('recruitment/applicants/{applicant}/notes', [ApplicantController::class, 'storeNote'])->name('recruitment.applicants.notes.store');
    });

    Route::middleware('permission:training.view')->group(function () {
        Route::get('training', [TrainingController::class, 'index'])->name('training.index');
        Route::post('training/categories', [TrainingController::class, 'storeCategory'])->name('training.categories.store');
        Route::post('training/programs', [TrainingController::class, 'storeProgram'])->name('training.programs.store');
        Route::post('training/programs/{program}/enroll', [TrainingEnrollmentController::class, 'store'])->name('training.programs.enroll');
        Route::post('training/programs/{program}/enrollments/{enrollment}', [TrainingEnrollmentController::class, 'updateStatus'])->name('training.programs.enrollments.update');
    });
});

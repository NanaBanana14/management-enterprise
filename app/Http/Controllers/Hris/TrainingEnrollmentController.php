<?php

namespace App\Http\Controllers\Hris;

use App\Enums\TrainingEnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\TrainingProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class TrainingEnrollmentController extends Controller
{
    public function store(Request $request, TrainingProgram $program): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 403, 'No employee profile is linked to your account.');

        $program->enrollments()->firstOrCreate(
            ['employee_id' => $employee->id],
            ['status' => TrainingEnrollmentStatus::Enrolled->value, 'enrolled_at' => now()],
        );

        return back()->with('success', 'Enrolled.');
    }

    public function updateStatus(Request $request, TrainingProgram $program, int $enrollment): RedirectResponse
    {
        abort_unless($request->user()->can('training.manage'), 403);

        $data = $request->validate(['status' => ['required', new Enum(TrainingEnrollmentStatus::class)]]);

        $program->enrollments()->whereKey($enrollment)->update([
            'status' => $data['status'],
            'completed_at' => $data['status'] === TrainingEnrollmentStatus::Completed->value ? now() : null,
        ]);

        return back()->with('success', 'Enrollment updated.');
    }
}

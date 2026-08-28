<?php

namespace App\Http\Controllers\Hris;

use App\Http\Controllers\Controller;
use App\Models\TrainingCategory;
use App\Models\TrainingProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainingController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $employeeId = $user->employee?->id;

        $categories = TrainingCategory::query()
            ->with(['programs.enrollments' => function ($query) use ($employeeId) {
                $query->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId));
            }])
            ->orderBy('name')
            ->get()
            ->map(fn (TrainingCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'programs' => $category->programs->map(fn (TrainingProgram $program) => [
                    'id' => $program->id,
                    'name' => $program->name,
                    'provider' => $program->provider,
                    'duration_hours' => $program->duration_hours,
                    'enrollments_count' => $program->enrollments()->count(),
                    'my_enrollment' => $employeeId ? $program->enrollments->first()?->only('id', 'status') : null,
                ]),
            ]);

        return Inertia::render('hris/training/Index', [
            'categories' => $categories,
            'hasEmployeeProfile' => $employeeId !== null,
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('training.manage'), 403);

        TrainingCategory::create($request->validate(['name' => ['required', 'string', 'max:255']]));

        return back()->with('success', 'Category added.');
    }

    public function storeProgram(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('training.manage'), 403);

        $data = $request->validate([
            'training_category_id' => ['required', 'exists:training_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'max:255'],
            'duration_hours' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ]);

        TrainingProgram::create($data);

        return back()->with('success', 'Training program added.');
    }
}

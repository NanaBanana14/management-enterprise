<?php

namespace App\Http\Controllers\Hris;

use App\Enums\TrainingAudience;
use App\Enums\TrainingMaterialType;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\TrainingCategory;
use App\Models\TrainingMaterial;
use App\Models\TrainingProgram;
use App\Services\TrainingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TrainingController extends Controller
{
    public function __construct(private readonly TrainingService $training) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $employee = $user->employee;
        $canManage = $user->can('training.manage');

        $categories = TrainingCategory::query()
            ->with(['programs' => function ($query) use ($employee, $canManage) {
                $query->where('audience', TrainingAudience::Staff->value);

                if (! $canManage) {
                    $query->visibleTo($employee?->department_id);
                }
            }, 'programs.enrollments' => function ($query) use ($employee) {
                $query->when($employee, fn ($q) => $q->where('employee_id', $employee->id));
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
                    'department' => $program->department?->name,
                    'enrollments_count' => $program->enrollments()->count(),
                    'my_enrollment' => $employee ? $program->enrollments->first()?->only('id', 'status') : null,
                ]),
            ])
            ->filter(fn (array $category) => $category['programs']->isNotEmpty())
            ->values();

        return Inertia::render('hris/training/Index', [
            'categories' => $categories,
            'hasEmployeeProfile' => $employee !== null,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, TrainingProgram $program): Response
    {
        $canManage = $request->user()->can('training.manage');

        $program->load(['category:id,name', 'department:id,name', 'materials']);

        return Inertia::render('hris/training/Show', [
            'program' => [
                'id' => $program->id,
                'name' => $program->name,
                'description' => $program->description,
                'provider' => $program->provider,
                'duration_hours' => $program->duration_hours,
                'audience' => $program->audience->value,
                'department' => $program->department?->name,
                'category' => $program->category->name,
                'materials' => $program->materials->map(fn ($m) => [
                    'id' => $m->id,
                    'title' => $m->title,
                    'type' => $m->type->value,
                    'body' => $m->body,
                    'video_url' => $m->video_url,
                    'file_url' => $m->fileUrl(),
                ]),
            ],
            'canManage' => $canManage,
            'enrollments' => $canManage ? $program->enrollments()->with('employee:id,name')->get()->map(fn ($e) => [
                'id' => $e->id,
                'employee' => $e->employee->name,
                'status' => $e->status->value,
            ]) : [],
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
            'department_id' => ['nullable', 'exists:departments,id'],
            'audience' => ['required', Rule::enum(TrainingAudience::class)],
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'max:255'],
            'duration_hours' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ]);

        $this->training->createProgram(
            TrainingCategory::findOrFail($data['training_category_id']),
            $data['name'],
            TrainingAudience::from($data['audience']),
            isset($data['department_id']) ? Department::find($data['department_id']) : null,
            $data['provider'] ?? null,
            $data['duration_hours'] ?? null,
            $data['description'] ?? null,
        );

        return back()->with('success', 'Training program added.');
    }

    public function storeMaterial(Request $request, TrainingProgram $program): RedirectResponse
    {
        abort_unless($request->user()->can('training.manage'), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(TrainingMaterialType::class)],
            'body' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx', 'max:10240'],
        ]);

        $this->training->addMaterial(
            $program,
            $data['title'],
            TrainingMaterialType::from($data['type']),
            $data['body'] ?? null,
            $data['video_url'] ?? null,
            $request->file('file'),
            $program->materials()->count(),
        );

        return back()->with('success', 'Material added.');
    }

    public function updateMaterial(Request $request, TrainingProgram $program, TrainingMaterial $material): RedirectResponse
    {
        abort_unless($request->user()->can('training.manage'), 403);
        abort_unless($material->training_program_id === $program->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(TrainingMaterialType::class)],
            'body' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx', 'max:10240'],
        ]);

        $this->training->updateMaterial(
            $material,
            $data['title'],
            TrainingMaterialType::from($data['type']),
            $data['body'] ?? null,
            $data['video_url'] ?? null,
            $request->file('file'),
        );

        return back()->with('success', 'Material updated.');
    }

    public function destroyMaterial(Request $request, TrainingProgram $program, TrainingMaterial $material): RedirectResponse
    {
        abort_unless($request->user()->can('training.manage'), 403);
        abort_unless($material->training_program_id === $program->id, 404);

        $this->training->deleteMaterial($material);

        return back()->with('success', 'Material removed.');
    }
}

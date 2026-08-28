<?php

namespace App\Http\Controllers\Hris;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Position;
use App\Models\Vacancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VacancyController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('hris/recruitment/Vacancies', [
            'vacancies' => Vacancy::query()
                ->with(['department:id,name', 'position:id,name'])
                ->withCount('applicants')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Vacancy $vacancy) => [
                    'id' => $vacancy->id,
                    'title' => $vacancy->title,
                    'status' => $vacancy->status,
                    'department' => $vacancy->department->name,
                    'position' => $vacancy->position->name,
                    'applicants_count' => $vacancy->applicants_count,
                ]),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'positions' => Position::query()->orderBy('name')->get(['id', 'name', 'department_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('recruitment.manage'), 403);

        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'position_id' => ['required', 'exists:positions,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        Vacancy::create($data);

        return back()->with('success', 'Vacancy created.');
    }

    public function show(Vacancy $vacancy): Response
    {
        $vacancy->load(['department:id,name', 'position:id,name']);

        return Inertia::render('hris/recruitment/Applicants', [
            'vacancy' => [
                'id' => $vacancy->id,
                'title' => $vacancy->title,
                'status' => $vacancy->status,
                'department' => $vacancy->department->name,
                'position' => $vacancy->position->name,
            ],
            'applicants' => $vacancy->applicants()
                ->orderByDesc('applied_at')
                ->get()
                ->map(fn ($applicant) => [
                    'id' => $applicant->id,
                    'name' => $applicant->name,
                    'email' => $applicant->email,
                    'stage' => $applicant->stage->value,
                    'applied_at' => $applicant->applied_at->format('Y-m-d'),
                ]),
        ]);
    }
}

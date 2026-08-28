<?php

namespace App\Http\Controllers\Hris;

use App\Enums\ApplicantStage;
use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Vacancy;
use App\Services\RecruitmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

class ApplicantController extends Controller
{
    public function __construct(private readonly RecruitmentService $recruitment) {}

    public function store(Request $request, Vacancy $vacancy): RedirectResponse
    {
        abort_unless($request->user()->can('recruitment.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $vacancy->applicants()->create([
            ...$data,
            'stage' => ApplicantStage::Applied->value,
            'applied_at' => now(),
        ]);

        return back()->with('success', 'Applicant added.');
    }

    public function show(Applicant $applicant): Response
    {
        $applicant->load(['vacancy:id,title', 'notes.author:id,name']);

        return Inertia::render('hris/recruitment/Show', [
            'applicant' => [
                'id' => $applicant->id,
                'name' => $applicant->name,
                'email' => $applicant->email,
                'phone' => $applicant->phone,
                'stage' => $applicant->stage->value,
                'applied_at' => $applicant->applied_at->format('Y-m-d'),
                'vacancy' => $applicant->vacancy->only('id', 'title'),
                'notes' => $applicant->notes->map(fn ($note) => [
                    'id' => $note->id,
                    'note' => $note->note,
                    'author' => $note->author->name,
                    'created_at' => $note->created_at->format('Y-m-d H:i'),
                ]),
            ],
            'stages' => array_map(fn (ApplicantStage $s) => ['value' => $s->value, 'label' => $s->label()], ApplicantStage::cases()),
        ]);
    }

    public function moveStage(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('recruitment.manage'), 403);

        $data = $request->validate(['stage' => ['required', new Enum(ApplicantStage::class)]]);

        $this->recruitment->moveStage($applicant, ApplicantStage::from($data['stage']));

        return back()->with('success', 'Stage updated.');
    }

    public function storeNote(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('recruitment.manage'), 403);

        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);

        $this->recruitment->addNote($applicant, $request->user(), $data['note']);

        return back()->with('success', 'Note added.');
    }
}

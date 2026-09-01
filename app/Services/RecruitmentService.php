<?php

namespace App\Services;

use App\Enums\ApplicantStage;
use App\Enums\ScreeningResult;
use App\Models\Applicant;
use App\Models\ApplicantTrainingResult;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecruitmentService
{
    private const TERMINAL_STAGES = [ApplicantStage::Hired, ApplicantStage::Rejected];

    public function moveStage(Applicant $applicant, ApplicantStage $stage): Applicant
    {
        return DB::transaction(function () use ($applicant, $stage) {
            $applicant = Applicant::query()->whereKey($applicant->id)->lockForUpdate()->firstOrFail();

            if (in_array($applicant->stage, self::TERMINAL_STAGES, true)) {
                throw ValidationException::withMessages([
                    'stage' => "This applicant is already {$applicant->stage->label()} and can't be moved further.",
                ]);
            }

            if ($stage === ApplicantStage::Hired) {
                $blocking = $applicant->trainingResults()
                    ->with('program:id,name')
                    ->where('result', '!=', ScreeningResult::Passed->value)
                    ->first();

                if ($blocking) {
                    throw ValidationException::withMessages([
                        'stage' => "This applicant hasn't passed the screening training \"{$blocking->program->name}\" yet and can't be hired.",
                    ]);
                }
            }

            $applicant->update(['stage' => $stage->value]);

            return $applicant;
        });
    }

    public function addNote(Applicant $applicant, User $author, string $note): void
    {
        $applicant->notes()->create(['user_id' => $author->id, 'note' => $note]);
    }

    public function assignScreening(Applicant $applicant, TrainingProgram $program): ApplicantTrainingResult
    {
        return $applicant->trainingResults()->firstOrCreate(
            ['training_program_id' => $program->id],
            ['result' => ScreeningResult::Pending->value],
        );
    }

    public function recordScreeningResult(ApplicantTrainingResult $result, ScreeningResult $outcome, ?string $notes, User $assessor): ApplicantTrainingResult
    {
        $result->update([
            'result' => $outcome,
            'notes' => $notes,
            'assessed_by' => $assessor->id,
            'assessed_at' => now(),
        ]);

        return $result;
    }
}

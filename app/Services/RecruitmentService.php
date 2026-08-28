<?php

namespace App\Services;

use App\Enums\ApplicantStage;
use App\Models\Applicant;
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

            $applicant->update(['stage' => $stage->value]);

            return $applicant;
        });
    }

    public function addNote(Applicant $applicant, User $author, string $note): void
    {
        $applicant->notes()->create(['user_id' => $author->id, 'note' => $note]);
    }
}

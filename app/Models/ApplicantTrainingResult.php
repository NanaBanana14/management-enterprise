<?php

namespace App\Models;

use App\Enums\ScreeningResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantTrainingResult extends Model
{
    protected $fillable = ['applicant_id', 'training_program_id', 'result', 'notes', 'assessed_by', 'assessed_at'];

    protected function casts(): array
    {
        return [
            'result' => ScreeningResult::class,
            'assessed_at' => 'date',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}

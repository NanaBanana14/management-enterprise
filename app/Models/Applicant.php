<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\ApplicantStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Applicant extends Model
{
    use Auditable, HasFactory;

    protected $fillable = ['vacancy_id', 'name', 'email', 'phone', 'resume_path', 'stage', 'applied_at'];

    protected function casts(): array
    {
        return [
            'stage' => ApplicantStage::class,
            'applied_at' => 'date',
        ];
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ApplicantNote::class);
    }

    public function trainingResults(): HasMany
    {
        return $this->hasMany(ApplicantTrainingResult::class);
    }
}

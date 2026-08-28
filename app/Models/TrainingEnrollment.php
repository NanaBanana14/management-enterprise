<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\TrainingEnrollmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingEnrollment extends Model
{
    use Auditable, HasFactory;

    protected $fillable = ['training_program_id', 'employee_id', 'status', 'enrolled_at', 'completed_at', 'notes'];

    protected function casts(): array
    {
        return [
            'status' => TrainingEnrollmentStatus::class,
            'enrolled_at' => 'date',
            'completed_at' => 'date',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

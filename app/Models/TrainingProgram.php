<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\TrainingAudience;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingProgram extends Model
{
    use Auditable, HasFactory;

    protected $fillable = ['training_category_id', 'department_id', 'name', 'audience', 'description', 'provider', 'duration_hours'];

    protected function casts(): array
    {
        return [
            'audience' => TrainingAudience::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TrainingCategory::class, 'training_category_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(TrainingMaterial::class)->orderBy('order');
    }

    public function scopeVisibleTo(Builder $query, ?int $departmentId): Builder
    {
        return $query->where(function (Builder $q) use ($departmentId) {
            $q->whereNull('department_id')->orWhere('department_id', $departmentId);
        });
    }
}

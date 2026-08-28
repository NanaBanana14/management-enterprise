<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\PerformanceReviewStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceReview extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'performance_period_id',
        'employee_id',
        'reviewer_id',
        'status',
        'overall_score',
        'summary',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PerformanceReviewStatus::class,
            'overall_score' => 'decimal:2',
            'submitted_at' => 'datetime',
        ];
    }

    public function performancePeriod(): BelongsTo
    {
        return $this->belongsTo(PerformancePeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PerformanceReviewItem::class);
    }
}

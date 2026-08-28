<?php

namespace App\Services;

use App\Enums\PerformanceReviewStatus;
use App\Models\Employee;
use App\Models\Kpi;
use App\Models\PerformancePeriod;
use App\Models\PerformanceReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PerformanceService
{
    public function createReview(PerformancePeriod $period, Employee $employee, User $reviewer): PerformanceReview
    {
        return DB::transaction(function () use ($period, $employee, $reviewer) {
            if ($period->reviews()->where('employee_id', $employee->id)->exists()) {
                throw ValidationException::withMessages([
                    'employee_id' => 'A review already exists for this employee in this period.',
                ]);
            }

            $review = PerformanceReview::create([
                'performance_period_id' => $period->id,
                'employee_id' => $employee->id,
                'reviewer_id' => $reviewer->id,
                'status' => PerformanceReviewStatus::Draft->value,
            ]);

            foreach (Kpi::all() as $kpi) {
                $review->items()->create(['kpi_id' => $kpi->id]);
            }

            return $review;
        });
    }

    public function scoreItem(PerformanceReview $review, int $itemId, int $score, ?string $notes): PerformanceReview
    {
        return DB::transaction(function () use ($review, $itemId, $score, $notes) {
            $review = PerformanceReview::query()->whereKey($review->id)->lockForUpdate()->firstOrFail();

            if ($review->status !== PerformanceReviewStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only draft reviews can be scored.']);
            }

            if ($score < 0 || $score > 100) {
                throw ValidationException::withMessages(['score' => 'Score must be between 0 and 100.']);
            }

            $review->items()->whereKey($itemId)->update(['score' => $score, 'notes' => $notes]);

            $this->recalculate($review);

            return $review->fresh('items');
        });
    }

    public function submit(PerformanceReview $review, ?string $summary): PerformanceReview
    {
        return DB::transaction(function () use ($review, $summary) {
            $review = PerformanceReview::query()->whereKey($review->id)->lockForUpdate()->firstOrFail();

            if ($review->status !== PerformanceReviewStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only draft reviews can be submitted.']);
            }

            if ($review->items()->whereNull('score')->exists()) {
                throw ValidationException::withMessages(['status' => 'Every KPI must be scored before submitting.']);
            }

            $review->update([
                'status' => PerformanceReviewStatus::Submitted->value,
                'summary' => $summary,
                'submitted_at' => now(),
            ]);

            return $review;
        });
    }

    private function recalculate(PerformanceReview $review): void
    {
        $items = $review->items()->with('kpi')->get();

        $totalWeight = $items->sum(fn ($item) => $item->kpi->weight);

        if ($totalWeight === 0) {
            return;
        }

        $weightedScore = $items->sum(fn ($item) => ($item->score ?? 0) * $item->kpi->weight);

        $review->update(['overall_score' => round($weightedScore / $totalWeight, 2)]);
    }
}

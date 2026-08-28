<?php

namespace App\Http\Controllers\Hris;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PerformancePeriod;
use App\Models\PerformanceReview;
use App\Services\PerformanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PerformanceReviewController extends Controller
{
    public function __construct(private readonly PerformanceService $performance) {}

    public function myReviews(Request $request): Response
    {
        $user = $request->user();

        $reviews = PerformanceReview::query()
            ->with('performancePeriod:id,name')
            ->when($user->employee, fn ($query) => $query->where('employee_id', $user->employee->id), fn ($query) => $query->whereRaw('1 = 0'))
            ->where('status', 'submitted')
            ->orderByDesc('submitted_at')
            ->get()
            ->map(fn (PerformanceReview $review) => [
                'id' => $review->id,
                'period' => $review->performancePeriod->name,
                'overall_score' => (float) $review->overall_score,
                'submitted_at' => $review->submitted_at?->format('Y-m-d'),
            ]);

        return Inertia::render('hris/performance/MyReviews', ['reviews' => $reviews]);
    }

    public function index(Request $request, PerformancePeriod $period): Response
    {
        abort_unless($request->user()->can('performance.manage'), 403);

        $reviews = $period->reviews()
            ->with(['employee:id,name,employee_number', 'reviewer:id,name'])
            ->get()
            ->map(fn (PerformanceReview $review) => [
                'id' => $review->id,
                'employee' => $review->employee->only('id', 'name', 'employee_number'),
                'reviewer' => $review->reviewer->only('id', 'name'),
                'status' => $review->status->value,
                'overall_score' => $review->overall_score !== null ? (float) $review->overall_score : null,
            ]);

        $reviewedEmployeeIds = $period->reviews()->pluck('employee_id');

        return Inertia::render('hris/performance/Reviews', [
            'period' => $period->only('id', 'name'),
            'reviews' => $reviews,
            'availableEmployees' => Employee::query()
                ->whereNotIn('id', $reviewedEmployeeIds)
                ->where('employment_status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, PerformancePeriod $period): RedirectResponse
    {
        abort_unless($request->user()->can('performance.manage'), 403);

        $data = $request->validate(['employee_id' => ['required', 'exists:employees,id']]);

        $review = $this->performance->createReview($period, Employee::findOrFail($data['employee_id']), $request->user());

        return to_route('hris.performance.reviews.show', $review)->with('success', 'Review created.');
    }

    public function show(Request $request, PerformanceReview $performanceReview): Response
    {
        $user = $request->user();
        $isOwner = $user->employee && $performanceReview->employee_id === $user->employee->id;

        abort_unless($isOwner || $user->can('performance.manage'), 403);

        $performanceReview->load(['employee:id,name,employee_number', 'performancePeriod:id,name', 'items.kpi.category']);

        return Inertia::render('hris/performance/Show', [
            'review' => [
                'id' => $performanceReview->id,
                'status' => $performanceReview->status->value,
                'overall_score' => $performanceReview->overall_score !== null ? (float) $performanceReview->overall_score : null,
                'summary' => $performanceReview->summary,
                'employee' => $performanceReview->employee->only('id', 'name', 'employee_number'),
                'period' => $performanceReview->performancePeriod->only('id', 'name'),
                'items' => $performanceReview->items->map(fn ($item) => [
                    'id' => $item->id,
                    'score' => $item->score,
                    'notes' => $item->notes,
                    'kpi' => [
                        'id' => $item->kpi->id,
                        'name' => $item->kpi->name,
                        'weight' => $item->kpi->weight,
                        'category' => $item->kpi->category->name,
                    ],
                ]),
            ],
            'canEdit' => $user->can('performance.manage') && $performanceReview->status->value === 'draft',
        ]);
    }

    public function scoreItem(Request $request, PerformanceReview $performanceReview, int $item): RedirectResponse
    {
        abort_unless($request->user()->can('performance.manage'), 403);

        $data = $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->performance->scoreItem($performanceReview, $item, $data['score'], $data['notes'] ?? null);

        return back()->with('success', 'Score saved.');
    }

    public function submit(Request $request, PerformanceReview $performanceReview): RedirectResponse
    {
        abort_unless($request->user()->can('performance.manage'), 403);

        $data = $request->validate(['summary' => ['nullable', 'string', 'max:2000']]);

        $this->performance->submit($performanceReview, $data['summary'] ?? null);

        return back()->with('success', 'Review submitted.');
    }
}

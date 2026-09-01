<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Enums\OvertimeStatus;
use App\Models\Account;
use App\Models\Applicant;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\Opportunity;
use App\Models\OvertimeRequest;
use App\Models\Payable;
use App\Models\Payslip;
use App\Models\PerformanceReview;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\TrainingEnrollment;
use App\Models\User;
use App\Models\Vacancy;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $hris = $user->can('employee.view') ? [
            'activeEmployees' => Employee::where('employment_status', 'active')->count(),
            'pendingLeave' => LeaveRequest::where('status', LeaveStatus::Pending->value)->count(),
            'pendingOvertime' => OvertimeRequest::where('status', OvertimeStatus::Pending->value)->count(),
            'attendanceTrend' => $this->attendanceTrend(),
            'leaveStatusBreakdown' => $this->leaveStatusBreakdown(),
            'recentLeaveRequests' => $user->can('leave.view') ? $this->recentLeaveRequests() : null,
            'recruitmentSummary' => $user->can('recruitment.view') ? $this->recruitmentSummary() : null,
            'performanceSummary' => $user->can('performance.view') ? $this->performanceSummary() : null,
            'trainingSummary' => $user->can('training.view') ? $this->trainingSummary() : null,
        ] : null;

        $finance = $user->can('report.view') ? [
            'cashBankBalance' => Account::where('is_cash_bank', true)->get()->sum(fn (Account $a) => $a->balance()),
            'receivableOutstanding' => (float) Invoice::where('status', 'unpaid')->sum('amount'),
            'payableOutstanding' => (float) Payable::where('status', 'unpaid')->sum('amount'),
            'receivableOverdue' => Invoice::where('status', 'unpaid')->whereDate('due_date', '<', now())->count(),
            'payableOverdue' => Payable::where('status', 'unpaid')->whereDate('due_date', '<', now())->count(),
            'cashFlowTrend' => $this->cashFlowTrend(),
            'recentTransactions' => $this->recentTransactions(),
        ] : null;

        $crm = $user->can('opportunity.view') ? [
            'openOpportunities' => Opportunity::whereNotIn('stage', ['won', 'lost'])->count(),
            'openPipelineValue' => (float) Opportunity::whereNotIn('stage', ['won', 'lost'])
                ->with('lines')
                ->get()
                ->sum(fn (Opportunity $o) => $o->lines->sum(fn ($l) => $l->quantity * $l->unit_price)),
            'stageBreakdown' => $this->opportunityStageBreakdown(),
            'recentOpportunities' => $this->recentOpportunities(),
        ] : null;

        $erp = $user->can('product.view') ? [
            'totalProducts' => Product::count(),
            'lowStockProducts' => Product::query()->withSum('stocks', 'quantity')->get()->filter(fn (Product $p) => (float) ($p->stocks_sum_quantity ?? 0) < 10)->count(),
            'draftPurchaseOrders' => $user->can('purchase.view') ? PurchaseOrder::where('status', 'draft')->count() : null,
            'draftSalesOrders' => $user->can('sales.view') ? SalesOrder::where('status', 'draft')->count() : null,
            'topProductsByStock' => $this->topProductsByStock(),
            'recentStockMovements' => $this->recentStockMovements(),
        ] : null;

        $platform = $user->can('users.view') ? [
            'stats' => [
                'totalUsers' => User::count(),
                'activeUsers' => User::where('is_active', true)->count(),
                'totalRoles' => Role::count(),
            ],
            'usersByRole' => Role::query()
                ->withCount('users')
                ->orderByDesc('users_count')
                ->get()
                ->map(fn (Role $role) => ['role' => $role->name, 'count' => $role->users_count]),
        ] : null;

        $me = $user->employee ? $this->myOverview($user->employee) : null;

        return Inertia::render('Dashboard', [
            'hris' => $hris,
            'finance' => $finance,
            'crm' => $crm,
            'erp' => $erp,
            'platform' => $platform,
            'me' => $me,
        ]);
    }

    private function attendanceTrend(): array
    {
        return Attendance::query()
            ->whereDate('date', '>=', now()->subDays(13)->toDateString())
            ->get(['date', 'status'])
            ->groupBy(fn (Attendance $a) => $a->date->toDateString())
            ->map(fn ($day, $date) => [
                'date' => $date,
                'present' => $day->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late])->count(),
                'absent' => $day->where('status', AttendanceStatus::Absent)->count(),
            ])
            ->sortKeys()
            ->values()
            ->all();
    }

    private function leaveStatusBreakdown(): array
    {
        return LeaveRequest::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => ['status' => $row->status->value, 'count' => (int) $row->count])
            ->all();
    }

    private function recentLeaveRequests(): array
    {
        return LeaveRequest::query()
            ->with(['employee:id,name', 'leaveType:id,name'])
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (LeaveRequest $r) => [
                'employee' => $r->employee->name,
                'type' => $r->leaveType->name,
                'days' => (float) $r->days,
                'status' => $r->status->value,
            ])
            ->all();
    }

    private function recruitmentSummary(): array
    {
        return [
            'openVacancies' => Vacancy::where('status', 'open')->count(),
            'activeApplicants' => Applicant::whereNotIn('stage', ['hired', 'rejected'])->count(),
        ];
    }

    private function performanceSummary(): array
    {
        $current = PerformanceReview::query()->latest('created_at')->first()?->performance_period_id;

        if (! $current) {
            return ['periodName' => null, 'submitted' => 0, 'total' => 0];
        }

        $reviews = PerformanceReview::where('performance_period_id', $current)->with('performancePeriod:id,name')->get();

        return [
            'periodName' => $reviews->first()?->performancePeriod?->name,
            'submitted' => $reviews->where('status', 'submitted')->count(),
            'total' => $reviews->count(),
        ];
    }

    private function trainingSummary(): array
    {
        return [
            'activeEnrollments' => TrainingEnrollment::whereIn('status', ['enrolled', 'in_progress'])->count(),
            'completedEnrollments' => TrainingEnrollment::where('status', 'completed')->count(),
        ];
    }

    private function cashFlowTrend(): array
    {
        $from = now()->subMonths(5)->startOfMonth();

        $lines = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->whereDate('journal_entries.date', '>=', $from->toDateString())
            ->whereIn('accounts.type', ['revenue', 'expense'])
            ->get(['journal_entries.date as entry_date', 'accounts.type as account_type', 'journal_entry_lines.debit', 'journal_entry_lines.credit']);

        return collect(range(5, 0))
            ->map(fn (int $i) => now()->subMonths($i))
            ->map(function (Carbon $month) use ($lines) {
                $key = $month->format('Y-m');
                $monthLines = $lines->filter(fn ($l) => Carbon::parse($l->entry_date)->format('Y-m') === $key);

                return [
                    'month' => $month->format('M Y'),
                    'income' => (float) $monthLines->where('account_type', 'revenue')->sum('credit'),
                    'expense' => (float) $monthLines->where('account_type', 'expense')->sum('debit'),
                ];
            })
            ->values()
            ->all();
    }

    private function recentTransactions(): array
    {
        return JournalEntry::query()
            ->withSum('lines as total_debit', 'debit')
            ->latest('date')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (JournalEntry $e) => [
                'reference' => $e->reference,
                'description' => $e->description,
                'date' => $e->date->toDateString(),
                'total' => (float) $e->total_debit,
            ])
            ->all();
    }

    private function opportunityStageBreakdown(): array
    {
        return Opportunity::query()
            ->selectRaw('stage, count(*) as count')
            ->groupBy('stage')
            ->get()
            ->map(fn ($row) => ['stage' => $row->stage->value, 'count' => (int) $row->count])
            ->all();
    }

    private function recentOpportunities(): array
    {
        return Opportunity::query()
            ->with('customer:id,name')
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (Opportunity $o) => [
                'title' => $o->title,
                'customer' => $o->customer->name,
                'stage' => $o->stage->value,
            ])
            ->all();
    }

    private function topProductsByStock(): array
    {
        return Product::query()
            ->withSum('stocks', 'quantity')
            ->orderByDesc('stocks_sum_quantity')
            ->limit(8)
            ->get()
            ->map(fn (Product $p) => ['name' => $p->name, 'quantity' => (float) ($p->stocks_sum_quantity ?? 0)])
            ->all();
    }

    private function recentStockMovements(): array
    {
        return StockMovement::query()
            ->with(['product:id,name', 'warehouse:id,name'])
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (StockMovement $m) => [
                'product' => $m->product->name,
                'warehouse' => $m->warehouse->name,
                'type' => $m->type,
                'quantity' => (float) $m->quantity,
            ])
            ->all();
    }

    private function myOverview(Employee $employee): array
    {
        $presentThisMonth = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->whereIn('status', [AttendanceStatus::Present->value, AttendanceStatus::Late->value])
            ->count();

        $leaveBalance = LeaveBalance::where('employee_id', $employee->id)
            ->where('year', now()->year)
            ->get()
            ->sum(fn (LeaveBalance $b) => $b->remainingDays());

        $latestPayslip = Payslip::where('employee_id', $employee->id)
            ->latest('created_at')
            ->first();

        return [
            'presentThisMonth' => $presentThisMonth,
            'leaveBalance' => (float) $leaveBalance,
            'latestPayslip' => $latestPayslip ? [
                'netSalary' => (float) $latestPayslip->net_salary,
                'status' => $latestPayslip->status->value,
            ] : null,
        ];
    }
}

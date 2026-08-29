<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Enums\OvertimeStatus;
use App\Models\Account;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\JournalEntryLine;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Payable;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\User;
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
        ] : null;

        $finance = $user->can('report.view') ? [
            'cashBankBalance' => Account::where('is_cash_bank', true)->get()->sum(fn (Account $a) => $a->balance()),
            'receivableOutstanding' => (float) Invoice::where('status', 'unpaid')->sum('amount'),
            'payableOutstanding' => (float) Payable::where('status', 'unpaid')->sum('amount'),
            'cashFlowTrend' => $this->cashFlowTrend(),
        ] : null;

        $erp = $user->can('product.view') ? [
            'totalProducts' => Product::count(),
            'lowStockProducts' => Product::query()->withSum('stocks', 'quantity')->get()->filter(fn (Product $p) => (float) ($p->stocks_sum_quantity ?? 0) < 10)->count(),
            'draftPurchaseOrders' => PurchaseOrder::where('status', 'draft')->count(),
            'draftSalesOrders' => SalesOrder::where('status', 'draft')->count(),
            'topProductsByStock' => $this->topProductsByStock(),
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

        return Inertia::render('Dashboard', [
            'hris' => $hris,
            'finance' => $finance,
            'erp' => $erp,
            'platform' => $platform,
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
}

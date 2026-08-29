<?php

namespace App\Services;

use App\Enums\OvertimeStatus;
use App\Enums\PayslipItemType;
use App\Enums\PayslipStatus;
use App\Models\Account;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function __construct(private JournalService $journal) {}

    public function generate(PayrollPeriod $period): int
    {
        return DB::transaction(function () use ($period) {
            $existing = $period->payslips()->pluck('employee_id');

            $employees = Employee::query()
                ->where('employment_status', 'active')
                ->whereNotIn('id', $existing)
                ->get();

            foreach ($employees as $employee) {
                $overtimeHours = (float) $employee->overtimeRequests()
                    ->where('status', OvertimeStatus::Approved->value)
                    ->whereBetween('date', [$period->start_date, $period->end_date])
                    ->sum('hours');

                $hourlyRate = (float) $employee->basic_salary / max(1, (int) config('payroll.monthly_working_hours'));
                $overtimeAmount = round($overtimeHours * $hourlyRate * (float) config('payroll.overtime_multiplier'), 2);

                Payslip::create([
                    'payroll_period_id' => $period->id,
                    'employee_id' => $employee->id,
                    'basic_salary' => $employee->basic_salary,
                    'overtime_hours' => $overtimeHours,
                    'overtime_amount' => $overtimeAmount,
                    'net_salary' => (float) $employee->basic_salary + $overtimeAmount,
                    'status' => PayslipStatus::Draft->value,
                ]);
            }

            return $employees->count();
        });
    }

    public function addItem(Payslip $payslip, PayslipItemType $type, string $label, float $amount): Payslip
    {
        return DB::transaction(function () use ($payslip, $type, $label, $amount) {
            $payslip = Payslip::query()->whereKey($payslip->id)->lockForUpdate()->firstOrFail();

            if ($payslip->status !== PayslipStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only draft payslips can be edited.']);
            }

            $payslip->items()->create(['type' => $type->value, 'label' => $label, 'amount' => $amount]);

            $this->recalculate($payslip);

            return $payslip->fresh('items');
        });
    }

    public function removeItem(Payslip $payslip, int $itemId): Payslip
    {
        return DB::transaction(function () use ($payslip, $itemId) {
            $payslip = Payslip::query()->whereKey($payslip->id)->lockForUpdate()->firstOrFail();

            if ($payslip->status !== PayslipStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only draft payslips can be edited.']);
            }

            $payslip->items()->whereKey($itemId)->delete();

            $this->recalculate($payslip);

            return $payslip->fresh('items');
        });
    }

    public function approve(Payslip $payslip, User $approver): Payslip
    {
        return DB::transaction(function () use ($payslip, $approver) {
            $payslip = Payslip::query()->whereKey($payslip->id)->lockForUpdate()->firstOrFail();

            if ($payslip->status !== PayslipStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only draft payslips can be approved.']);
            }

            $payslip->update([
                'status' => PayslipStatus::Approved->value,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            return $payslip;
        });
    }

    public function closePeriod(PayrollPeriod $period, User $processor): PayrollPeriod
    {
        return DB::transaction(function () use ($period, $processor) {
            $period = PayrollPeriod::query()->whereKey($period->id)->lockForUpdate()->firstOrFail();

            if ($period->payslips()->where('status', PayslipStatus::Draft->value)->exists()) {
                throw ValidationException::withMessages(['status' => 'All payslips must be approved before closing the period.']);
            }

            $period->payslips()->update(['status' => PayslipStatus::Paid->value]);

            $totalNetSalary = (float) $period->payslips()->sum('net_salary');
            $salaryExpense = Account::where('type', 'expense')->orderBy('code')->firstOrFail();
            $cashBank = Account::where('is_cash_bank', true)->orderBy('code')->firstOrFail();

            $entry = $this->journal->create(now()->toDateString(), 'PR-'.now()->format('Ym').'-'.$period->id, "Payroll for {$period->name}", [
                ['account_id' => $salaryExpense->id, 'debit' => $totalNetSalary, 'credit' => 0],
                ['account_id' => $cashBank->id, 'debit' => 0, 'credit' => $totalNetSalary],
            ], $processor);

            $period->update([
                'status' => 'closed',
                'processed_by' => $processor->id,
                'processed_at' => now(),
                'journal_entry_id' => $entry->id,
            ]);

            return $period;
        });
    }

    private function recalculate(Payslip $payslip): void
    {
        $totals = $payslip->items()
            ->selectRaw('type, sum(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $allowance = (float) ($totals[PayslipItemType::Allowance->value] ?? 0);
        $bonus = (float) ($totals[PayslipItemType::Bonus->value] ?? 0);
        $deduction = (float) ($totals[PayslipItemType::Deduction->value] ?? 0);

        $netSalary = (float) $payslip->basic_salary + (float) $payslip->overtime_amount + $allowance + $bonus - $deduction;

        $payslip->update([
            'allowance_total' => $allowance,
            'bonus_total' => $bonus,
            'deduction_total' => $deduction,
            'net_salary' => $netSalary,
        ]);
    }
}

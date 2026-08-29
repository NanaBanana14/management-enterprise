<?php

namespace Tests\Feature\Hris;

use App\Enums\PayslipItemType;
use App\Models\Account;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Position;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    private function employee(float $basicSalary = 10_000_000): Employee
    {
        $department = Department::factory()->create();
        $position = Position::factory()->for($department)->create();

        return Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'basic_salary' => $basicSalary,
        ]);
    }

    private function period(): PayrollPeriod
    {
        return PayrollPeriod::create([
            'name' => 'September 2026',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
        ]);
    }

    public function test_generate_creates_a_draft_payslip_per_active_employee()
    {
        $this->employee();
        $this->employee();
        $period = $this->period();

        $count = app(PayrollService::class)->generate($period);

        $this->assertSame(2, $count);
        $this->assertSame(2, $period->payslips()->count());
        $this->assertTrue($period->payslips()->where('status', 'draft')->exists());
    }

    public function test_generate_is_idempotent_for_already_generated_employees()
    {
        $this->employee();
        $period = $this->period();
        $service = app(PayrollService::class);

        $service->generate($period);
        $second = $service->generate($period);

        $this->assertSame(0, $second);
        $this->assertSame(1, $period->payslips()->count());
    }

    public function test_approved_overtime_within_the_period_is_included_in_net_salary()
    {
        $employee = $this->employee(17_300_000); // hourly rate = 100,000 at 173 hours/month
        $period = $this->period();

        $employee->overtimeRequests()->create([
            'date' => '2026-09-10',
            'hours' => 4,
            'status' => 'approved',
        ]);

        // Outside the period - should not count
        $employee->overtimeRequests()->create([
            'date' => '2026-08-10',
            'hours' => 10,
            'status' => 'approved',
        ]);

        // Pending - should not count
        $employee->overtimeRequests()->create([
            'date' => '2026-09-15',
            'hours' => 5,
            'status' => 'pending',
        ]);

        app(PayrollService::class)->generate($period);

        $payslip = $period->payslips()->first();

        // 4 hours * 100,000 * 1.5 multiplier = 600,000
        $this->assertSame(4.0, (float) $payslip->overtime_hours);
        $this->assertSame(600000.0, (float) $payslip->overtime_amount);
        $this->assertSame(17300000.0 + 600000.0, (float) $payslip->net_salary);
    }

    public function test_adding_items_recalculates_net_salary()
    {
        $employee = $this->employee(10_000_000);
        $period = $this->period();
        $service = app(PayrollService::class);

        $service->generate($period);
        $payslip = $period->payslips()->first();

        $service->addItem($payslip, PayslipItemType::Allowance, 'Transport', 500000);
        $service->addItem($payslip, PayslipItemType::Deduction, 'BPJS', 200000);

        $payslip->refresh();

        $this->assertSame(10000000.0 + 500000.0 - 200000.0, (float) $payslip->net_salary);
    }

    public function test_cannot_add_items_to_a_non_draft_payslip()
    {
        $employee = $this->employee();
        $period = $this->period();
        $service = app(PayrollService::class);
        $approver = User::factory()->create();

        $service->generate($period);
        $payslip = $period->payslips()->first();
        $service->approve($payslip, $approver);

        $this->expectException(ValidationException::class);
        $service->addItem($payslip, PayslipItemType::Bonus, 'Late bonus', 100000);
    }

    public function test_cannot_approve_an_already_approved_payslip()
    {
        $employee = $this->employee();
        $period = $this->period();
        $service = app(PayrollService::class);
        $approver = User::factory()->create();

        $service->generate($period);
        $payslip = $period->payslips()->first();
        $service->approve($payslip, $approver);

        $this->expectException(ValidationException::class);
        $service->approve($payslip, $approver);
    }

    public function test_closing_a_period_posts_a_payroll_journal_entry()
    {
        Account::create(['code' => '5100', 'name' => 'Salary Expense', 'type' => 'expense']);
        Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset', 'is_cash_bank' => true]);

        $this->employee(5_000_000);
        $period = $this->period();
        $service = app(PayrollService::class);
        $processor = User::factory()->create();

        $service->generate($period);
        $service->approve($period->payslips()->first(), $processor);
        $service->closePeriod($period, $processor);

        $period->refresh();
        $this->assertSame('closed', $period->status->value);
        $this->assertNotNull($period->journal_entry_id);
        $this->assertSame(5_000_000.0, (float) $period->journalEntry->lines()->sum('debit'));
    }

    public function test_approve_all_approves_every_draft_payslip_in_the_period()
    {
        $this->employee();
        $this->employee();
        $this->employee();
        $period = $this->period();
        $service = app(PayrollService::class);
        $approver = User::factory()->create();

        $service->generate($period);
        $count = $service->approveAll($period, $approver);

        $this->assertSame(3, $count);
        $this->assertSame(3, $period->payslips()->where('status', 'approved')->count());
        $this->assertSame(0, $period->payslips()->where('status', 'draft')->count());
    }

    public function test_approve_all_skips_payslips_that_are_already_approved()
    {
        $this->employee();
        $this->employee();
        $period = $this->period();
        $service = app(PayrollService::class);
        $approver = User::factory()->create();

        $service->generate($period);
        $service->approve($period->payslips()->first(), $approver);

        $count = $service->approveAll($period, $approver);

        $this->assertSame(1, $count);
        $this->assertSame(2, $period->payslips()->where('status', 'approved')->count());
    }
}

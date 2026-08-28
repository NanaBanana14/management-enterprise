<?php

namespace App\Http\Controllers\Hris;

use App\Enums\PayslipItemType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hris\StorePayslipItemRequest;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayslipController extends Controller
{
    public function __construct(private readonly PayrollService $payroll) {}

    private function canManagePayroll($user): bool
    {
        return $user->can('payroll.process') || $user->can('payroll.approve');
    }

    public function myPayslips(Request $request): Response
    {
        $user = $request->user();

        $payslips = Payslip::query()
            ->with('payrollPeriod:id,name,start_date,end_date')
            ->when($user->employee, fn ($query) => $query->where('employee_id', $user->employee->id), fn ($query) => $query->whereRaw('1 = 0'))
            ->whereIn('status', ['approved', 'paid'])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->through(fn (Payslip $payslip) => [
                'id' => $payslip->id,
                'period' => $payslip->payrollPeriod->name,
                'net_salary' => (float) $payslip->net_salary,
                'status' => $payslip->status->value,
            ]);

        return Inertia::render('hris/payroll/MyPayslips', [
            'payslips' => $payslips,
            'canManage' => $this->canManagePayroll($user),
        ]);
    }

    public function index(Request $request, PayrollPeriod $period): Response
    {
        abort_unless($this->canManagePayroll($request->user()), 403);

        $payslips = $period->payslips()
            ->with('employee:id,name,employee_number')
            ->orderBy('id')
            ->get()
            ->map(fn (Payslip $payslip) => [
                'id' => $payslip->id,
                'employee' => $payslip->employee->only('id', 'name', 'employee_number'),
                'basic_salary' => (float) $payslip->basic_salary,
                'overtime_amount' => (float) $payslip->overtime_amount,
                'allowance_total' => (float) $payslip->allowance_total,
                'bonus_total' => (float) $payslip->bonus_total,
                'deduction_total' => (float) $payslip->deduction_total,
                'net_salary' => (float) $payslip->net_salary,
                'status' => $payslip->status->value,
            ]);

        return Inertia::render('hris/payroll/Payslips', [
            'period' => [
                'id' => $period->id,
                'name' => $period->name,
                'start_date' => $period->start_date->format('Y-m-d'),
                'end_date' => $period->end_date->format('Y-m-d'),
                'status' => $period->status->value,
            ],
            'payslips' => $payslips,
            'canProcess' => $request->user()->can('payroll.process'),
            'canApprove' => $request->user()->can('payroll.approve'),
        ]);
    }

    public function show(Request $request, Payslip $payslip): Response
    {
        $user = $request->user();
        $isOwner = $user->employee && $payslip->employee_id === $user->employee->id;

        abort_unless($isOwner || $this->canManagePayroll($user), 403);

        $payslip->load(['employee:id,name,employee_number', 'payrollPeriod:id,name,start_date,end_date', 'items']);

        return Inertia::render('hris/payroll/Show', [
            'payslip' => [
                'id' => $payslip->id,
                'basic_salary' => (float) $payslip->basic_salary,
                'overtime_hours' => (float) $payslip->overtime_hours,
                'overtime_amount' => (float) $payslip->overtime_amount,
                'allowance_total' => (float) $payslip->allowance_total,
                'bonus_total' => (float) $payslip->bonus_total,
                'deduction_total' => (float) $payslip->deduction_total,
                'net_salary' => (float) $payslip->net_salary,
                'status' => $payslip->status->value,
                'employee' => $payslip->employee->only('id', 'name', 'employee_number'),
                'period' => $payslip->payrollPeriod->only('id', 'name'),
                'items' => $payslip->items->map(fn ($item) => [
                    'id' => $item->id,
                    'type' => $item->type->value,
                    'label' => $item->label,
                    'amount' => (float) $item->amount,
                ]),
            ],
            'canEdit' => $this->canManagePayroll($user) && $payslip->status->value === 'draft' && $user->can('payroll.process'),
            'canApprove' => $user->can('payroll.approve') && $payslip->status->value === 'draft',
        ]);
    }

    public function storeItem(StorePayslipItemRequest $request, Payslip $payslip): RedirectResponse
    {
        $data = $request->validated();

        $this->payroll->addItem($payslip, PayslipItemType::from($data['type']), $data['label'], (float) $data['amount']);

        return back()->with('success', 'Item added.');
    }

    public function destroyItem(Request $request, Payslip $payslip, int $item): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.process'), 403);

        $this->payroll->removeItem($payslip, $item);

        return back()->with('success', 'Item removed.');
    }

    public function approve(Request $request, Payslip $payslip): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.approve'), 403);

        $this->payroll->approve($payslip, $request->user());

        return back()->with('success', 'Payslip approved.');
    }
}

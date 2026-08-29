<?php

namespace App\Http\Controllers\Hris;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hris\StorePayrollPeriodRequest;
use App\Models\PayrollPeriod;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollPeriodController extends Controller
{
    public function __construct(private readonly PayrollService $payroll) {}

    public function index(): Response
    {
        $periods = PayrollPeriod::query()
            ->withCount('payslips')
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (PayrollPeriod $period) => [
                'id' => $period->id,
                'name' => $period->name,
                'start_date' => $period->start_date->format('Y-m-d'),
                'end_date' => $period->end_date->format('Y-m-d'),
                'status' => $period->status->value,
                'payslips_count' => $period->payslips_count,
                'total_net' => (float) $period->payslips()->sum('net_salary'),
            ]);

        return Inertia::render('hris/payroll/Periods', [
            'periods' => $periods,
        ]);
    }

    public function store(StorePayrollPeriodRequest $request): RedirectResponse
    {
        $period = PayrollPeriod::create($request->validated());

        return to_route('hris.payroll.periods.show', $period)->with('success', "Payroll period \"{$period->name}\" created.");
    }

    public function generate(Request $request, PayrollPeriod $period): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.process'), 403);

        $count = $this->payroll->generate($period);

        return back()->with('success', "Generated {$count} payslip(s).");
    }

    public function close(Request $request, PayrollPeriod $period): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.approve'), 403);

        $this->payroll->closePeriod($period, $request->user());

        return back()->with('success', "Payroll period \"{$period->name}\" closed and posted to Finance.");
    }
}

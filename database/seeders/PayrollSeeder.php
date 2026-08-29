<?php

namespace Database\Seeders;

use App\Enums\PayslipItemType;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Database\Seeder;

class PayrollSeeder extends Seeder
{
    public function run(): void
    {
        if (PayrollPeriod::exists()) {
            return;
        }

        $admin = User::where('email', 'admin@nexa.test')->first();

        if (! $admin) {
            return;
        }

        $service = app(PayrollService::class);

        foreach ([2, 1] as $monthsAgo) {
            $start = now()->subMonths($monthsAgo)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            $period = PayrollPeriod::create([
                'name' => $start->format('F Y'),
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ]);

            $service->generate($period);

            $payslips = $period->payslips()->get();

            foreach ($payslips as $index => $payslip) {
                if ($index === 0) {
                    $service->addItem($payslip, PayslipItemType::Allowance, 'Transport Allowance', 500_000);
                    $service->addItem($payslip, PayslipItemType::Bonus, 'Performance Bonus', 1_000_000);
                } elseif ($index === 1) {
                    $service->addItem($payslip, PayslipItemType::Deduction, 'Late Penalty', 150_000);
                }

                $service->approve($payslip, $admin);
            }

            $service->closePeriod($period, $admin);
        }

        // Current month stays open with generated draft payslips, ready for a live approve/close demo.
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $currentPeriod = PayrollPeriod::create([
            'name' => $start->format('F Y'),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ]);

        $service->generate($currentPeriod);
    }
}

<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __invoke(): Response
    {
        $accounts = Account::query()->where('is_active', true)->orderBy('code')->get();

        $byType = $accounts->groupBy('type')->map(fn ($group) => $group->map(fn (Account $a) => [
            'code' => $a->code,
            'name' => $a->name,
            'balance' => $a->balance(),
        ])->values());

        $revenue = (float) ($byType->get('revenue', collect())->sum('balance'));
        $expense = (float) ($byType->get('expense', collect())->sum('balance'));

        $assets = (float) ($byType->get('asset', collect())->sum('balance'));
        $liabilities = (float) ($byType->get('liability', collect())->sum('balance'));
        $equity = (float) ($byType->get('equity', collect())->sum('balance'));

        return Inertia::render('finance/reports/Index', [
            'accountsByType' => $byType,
            'profitAndLoss' => [
                'revenue' => $revenue,
                'expense' => $expense,
                'net' => $revenue - $expense,
            ],
            'balanceSheet' => [
                'assets' => $assets,
                'liabilities' => $liabilities,
                'equity' => $equity,
                'liabilitiesPlusEquity' => $liabilities + $equity,
            ],
        ]);
    }
}

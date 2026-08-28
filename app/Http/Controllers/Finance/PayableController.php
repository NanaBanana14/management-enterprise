<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Payable;
use App\Models\Supplier;
use App\Services\PayableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayableController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('finance/payables/Index', [
            'payables' => Payable::query()
                ->with('supplier:id,name')
                ->latest('date')
                ->get()
                ->map(fn (Payable $p) => [
                    'id' => $p->id,
                    'number' => $p->number,
                    'supplier' => $p->supplier->name,
                    'date' => $p->date->toDateString(),
                    'due_date' => $p->due_date->toDateString(),
                    'amount' => (float) $p->amount,
                    'status' => $p->status,
                ]),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'expenseAccounts' => Account::query()->where('type', 'expense')->orderBy('code')->get(['id', 'code', 'name']),
            'cashBankAccounts' => Account::query()->where('is_cash_bank', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request, PayableService $service): RedirectResponse
    {
        abort_unless($request->user()->can('payable.manage'), 403);

        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'expense_account_id' => ['required', 'exists:accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $service->create(
            Supplier::findOrFail($data['supplier_id']),
            Account::findOrFail($data['expense_account_id']),
            (float) $data['amount'],
            $data['date'],
            $data['due_date'],
            $data['description'] ?? null,
            $request->user(),
        );

        return back()->with('success', 'Payable created.');
    }

    public function markPaid(Request $request, Payable $payable, PayableService $service): RedirectResponse
    {
        abort_unless($request->user()->can('payable.manage'), 403);

        $data = $request->validate([
            'cash_bank_account_id' => ['required', 'exists:accounts,id'],
        ]);

        $service->markPaid($payable, Account::findOrFail($data['cash_bank_account_id']), $request->user());

        return back()->with('success', 'Payable marked as paid.');
    }
}

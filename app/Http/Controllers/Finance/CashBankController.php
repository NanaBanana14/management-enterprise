<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntryLine;
use App\Services\CashBankService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashBankController extends Controller
{
    public function index(): Response
    {
        $accounts = Account::query()->where('is_cash_bank', true)->orderBy('code')->get();

        return Inertia::render('finance/cashbank/Index', [
            'accounts' => $accounts->map(fn (Account $a) => [
                'id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
                'balance' => $a->balance(),
            ]),
            'transactions' => JournalEntryLine::query()
                ->whereIn('account_id', $accounts->pluck('id'))
                ->with(['account:id,name,code', 'journalEntry:id,date,reference,description'])
                ->latest('id')
                ->limit(30)
                ->get()
                ->map(fn (JournalEntryLine $line) => [
                    'id' => $line->id,
                    'date' => $line->journalEntry->date->toDateString(),
                    'reference' => $line->journalEntry->reference,
                    'description' => $line->journalEntry->description,
                    'account' => $line->account->name,
                    'debit' => (float) $line->debit,
                    'credit' => (float) $line->credit,
                ]),
            'revenueAccounts' => Account::query()->where('type', 'revenue')->orderBy('code')->get(['id', 'code', 'name']),
            'expenseAccounts' => Account::query()->where('type', 'expense')->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function recordIncome(Request $request, CashBankService $service): RedirectResponse
    {
        abort_unless($request->user()->can('income.manage'), 403);

        $data = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'revenue_account_id' => ['required', 'exists:accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $service->recordIncome(
            Account::findOrFail($data['account_id']),
            Account::findOrFail($data['revenue_account_id']),
            (float) $data['amount'],
            $data['date'],
            $data['description'] ?? null,
            $request->user(),
        );

        return back()->with('success', 'Income recorded.');
    }

    public function recordExpense(Request $request, CashBankService $service): RedirectResponse
    {
        abort_unless($request->user()->can('expense.manage'), 403);

        $data = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'expense_account_id' => ['required', 'exists:accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $service->recordExpense(
            Account::findOrFail($data['account_id']),
            Account::findOrFail($data['expense_account_id']),
            (float) $data['amount'],
            $data['date'],
            $data['description'] ?? null,
            $request->user(),
        );

        return back()->with('success', 'Expense recorded.');
    }

    public function transfer(Request $request, CashBankService $service): RedirectResponse
    {
        abort_unless($request->user()->can('cashbank.manage'), 403);

        $data = $request->validate([
            'from_account_id' => ['required', 'exists:accounts,id'],
            'to_account_id' => ['required', 'exists:accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $service->transfer(
            Account::findOrFail($data['from_account_id']),
            Account::findOrFail($data['to_account_id']),
            (float) $data['amount'],
            $data['date'],
            $data['description'] ?? null,
            $request->user(),
        );

        return back()->with('success', 'Transfer recorded.');
    }
}

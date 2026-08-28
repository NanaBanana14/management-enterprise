<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('finance/invoices/Index', [
            'invoices' => Invoice::query()
                ->with('customer:id,name')
                ->latest('date')
                ->get()
                ->map(fn (Invoice $i) => [
                    'id' => $i->id,
                    'number' => $i->number,
                    'customer' => $i->customer->name,
                    'date' => $i->date->toDateString(),
                    'due_date' => $i->due_date->toDateString(),
                    'amount' => (float) $i->amount,
                    'status' => $i->status,
                ]),
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'revenueAccounts' => Account::query()->where('type', 'revenue')->orderBy('code')->get(['id', 'code', 'name']),
            'cashBankAccounts' => Account::query()->where('is_cash_bank', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request, InvoiceService $service): RedirectResponse
    {
        abort_unless($request->user()->can('invoice.create'), 403);

        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'revenue_account_id' => ['required', 'exists:accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $service->create(
            Customer::findOrFail($data['customer_id']),
            Account::findOrFail($data['revenue_account_id']),
            (float) $data['amount'],
            $data['date'],
            $data['due_date'],
            $data['description'] ?? null,
            $request->user(),
        );

        return back()->with('success', 'Invoice created.');
    }

    public function markPaid(Request $request, Invoice $invoice, InvoiceService $service): RedirectResponse
    {
        abort_unless($request->user()->can('invoice.approve'), 403);

        $data = $request->validate([
            'cash_bank_account_id' => ['required', 'exists:accounts,id'],
        ]);

        $service->markPaid($invoice, Account::findOrFail($data['cash_bank_account_id']), $request->user());

        return back()->with('success', 'Invoice marked as paid.');
    }
}

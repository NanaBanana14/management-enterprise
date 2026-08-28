<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(private JournalService $journal) {}

    public function create(Customer $customer, Account $revenueAccount, float $amount, string $date, string $dueDate, ?string $description, User $user): Invoice
    {
        return DB::transaction(function () use ($customer, $revenueAccount, $amount, $date, $dueDate, $description, $user) {
            $number = 'INV-'.now()->format('Ym').'-'.Str::padLeft((string) (Invoice::count() + 1), 4, '0');

            $entry = $this->journal->create($date, 'AR-'.Str::upper(Str::random(8)), $description ?? "Invoice {$number}", [
                ['account_id' => $this->arAccount()->id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $revenueAccount->id, 'debit' => 0, 'credit' => $amount],
            ], $user);

            return Invoice::create([
                'number' => $number,
                'customer_id' => $customer->id,
                'revenue_account_id' => $revenueAccount->id,
                'journal_entry_id' => $entry->id,
                'date' => $date,
                'due_date' => $dueDate,
                'amount' => $amount,
                'description' => $description,
                'status' => 'unpaid',
                'created_by' => $user->id,
            ]);
        });
    }

    public function markPaid(Invoice $invoice, Account $cashBankAccount, User $user): Invoice
    {
        return DB::transaction(function () use ($invoice, $cashBankAccount, $user) {
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($invoice->status !== 'unpaid') {
                throw ValidationException::withMessages(['status' => 'Only unpaid invoices can be marked as paid.']);
            }

            $entry = $this->journal->create(now()->toDateString(), 'AR-PMT-'.Str::upper(Str::random(8)), "Payment for invoice {$invoice->number}", [
                ['account_id' => $cashBankAccount->id, 'debit' => (float) $invoice->amount, 'credit' => 0],
                ['account_id' => $this->arAccount()->id, 'debit' => 0, 'credit' => (float) $invoice->amount],
            ], $user);

            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_journal_entry_id' => $entry->id,
            ]);

            return $invoice;
        });
    }

    private function arAccount(): Account
    {
        return Account::where('code', '1300')->firstOrFail();
    }
}

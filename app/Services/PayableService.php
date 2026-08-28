<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Payable;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayableService
{
    public function __construct(private JournalService $journal) {}

    public function create(Supplier $supplier, Account $expenseAccount, float $amount, string $date, string $dueDate, ?string $description, User $user): Payable
    {
        return DB::transaction(function () use ($supplier, $expenseAccount, $amount, $date, $dueDate, $description, $user) {
            $number = 'PAY-'.now()->format('Ym').'-'.Str::padLeft((string) (Payable::count() + 1), 4, '0');

            $entry = $this->journal->create($date, 'AP-'.Str::upper(Str::random(8)), $description ?? "Payable {$number}", [
                ['account_id' => $expenseAccount->id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $this->apAccount()->id, 'debit' => 0, 'credit' => $amount],
            ], $user);

            return Payable::create([
                'number' => $number,
                'supplier_id' => $supplier->id,
                'expense_account_id' => $expenseAccount->id,
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

    public function markPaid(Payable $payable, Account $cashBankAccount, User $user): Payable
    {
        return DB::transaction(function () use ($payable, $cashBankAccount, $user) {
            $payable = Payable::query()->whereKey($payable->id)->lockForUpdate()->firstOrFail();

            if ($payable->status !== 'unpaid') {
                throw ValidationException::withMessages(['status' => 'Only unpaid payables can be marked as paid.']);
            }

            $entry = $this->journal->create(now()->toDateString(), 'AP-PMT-'.Str::upper(Str::random(8)), "Payment for payable {$payable->number}", [
                ['account_id' => $this->apAccount()->id, 'debit' => (float) $payable->amount, 'credit' => 0],
                ['account_id' => $cashBankAccount->id, 'debit' => 0, 'credit' => (float) $payable->amount],
            ], $user);

            $payable->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_journal_entry_id' => $entry->id,
            ]);

            return $payable;
        });
    }

    private function apAccount(): Account
    {
        return Account::where('code', '2100')->firstOrFail();
    }
}

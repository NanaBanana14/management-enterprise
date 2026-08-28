<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CashBankService
{
    public function __construct(private JournalService $journal) {}

    public function recordIncome(Account $cashBankAccount, Account $revenueAccount, float $amount, string $date, ?string $description, User $user): JournalEntry
    {
        $this->assertCashBank($cashBankAccount);

        return $this->journal->create($date, 'INC-'.Str::upper(Str::random(8)), $description, [
            ['account_id' => $cashBankAccount->id, 'debit' => $amount, 'credit' => 0],
            ['account_id' => $revenueAccount->id, 'debit' => 0, 'credit' => $amount],
        ], $user);
    }

    public function recordExpense(Account $cashBankAccount, Account $expenseAccount, float $amount, string $date, ?string $description, User $user): JournalEntry
    {
        $this->assertCashBank($cashBankAccount);

        return $this->journal->create($date, 'EXP-'.Str::upper(Str::random(8)), $description, [
            ['account_id' => $expenseAccount->id, 'debit' => $amount, 'credit' => 0],
            ['account_id' => $cashBankAccount->id, 'debit' => 0, 'credit' => $amount],
        ], $user);
    }

    public function transfer(Account $from, Account $to, float $amount, string $date, ?string $description, User $user): JournalEntry
    {
        $this->assertCashBank($from);
        $this->assertCashBank($to);

        return $this->journal->create($date, 'TRF-'.Str::upper(Str::random(8)), $description, [
            ['account_id' => $to->id, 'debit' => $amount, 'credit' => 0],
            ['account_id' => $from->id, 'debit' => 0, 'credit' => $amount],
        ], $user);
    }

    private function assertCashBank(Account $account): void
    {
        if (! $account->is_cash_bank) {
            throw ValidationException::withMessages(['account_id' => "{$account->name} is not a cash or bank account."]);
        }
    }
}

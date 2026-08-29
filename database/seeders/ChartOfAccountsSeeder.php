<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    private const ACCOUNTS = [
        ['1000', 'Assets', 'asset'],
        ['1100', 'Cash', 'asset'],
        ['1200', 'Bank', 'asset'],
        ['1300', 'Accounts Receivable', 'asset'],
        ['1400', 'Inventory', 'asset'],
        ['2000', 'Liabilities', 'liability'],
        ['2100', 'Accounts Payable', 'liability'],
        ['3000', 'Equity', 'equity'],
        ['3100', "Owner's Capital", 'equity'],
        ['4000', 'Revenue', 'revenue'],
        ['4100', 'Sales Revenue', 'revenue'],
        ['5000', 'Expenses', 'expense'],
        ['5100', 'Salary Expense', 'expense'],
        ['5200', 'Operational Expense', 'expense'],
        ['5300', 'Purchase Expense', 'expense'],
    ];

    public function run(): void
    {
        foreach (self::ACCOUNTS as [$code, $name, $type]) {
            Account::query()->updateOrCreate(['code' => $code], ['name' => $name, 'type' => $type]);
        }

        Account::whereIn('code', ['1100', '1200'])->update(['is_cash_bank' => true]);

        $admin = User::where('email', 'admin@nexa.test')->first();
        $cash = Account::where('code', '1100')->first();
        $capital = Account::where('code', '3100')->first();
        $bank = Account::where('code', '1200')->first();
        $salaryExpense = Account::where('code', '5100')->first();

        if (! $admin || JournalEntry::exists()) {
            return;
        }

        $opening = JournalEntry::create([
            'date' => now()->subMonth(),
            'reference' => 'JE-00001',
            'description' => 'Owner capital injection',
            'status' => 'posted',
            'created_by' => $admin->id,
        ]);
        $opening->lines()->createMany([
            ['account_id' => $cash->id, 'debit' => 50_000_000, 'credit' => 0],
            ['account_id' => $capital->id, 'debit' => 0, 'credit' => 50_000_000],
        ]);

        $payroll = JournalEntry::create([
            'date' => now()->subDays(10),
            'reference' => 'JE-00002',
            'description' => 'Monthly salary payment',
            'status' => 'posted',
            'created_by' => $admin->id,
        ]);
        $payroll->lines()->createMany([
            ['account_id' => $salaryExpense->id, 'debit' => 12_000_000, 'credit' => 0],
            ['account_id' => $bank->id, 'debit' => 0, 'credit' => 12_000_000],
        ]);

        $this->seedSixMonthHistory($admin);
    }

    private function seedSixMonthHistory(User $admin): void
    {
        $revenue = Account::where('code', '4100')->first();
        $salaryExpense = Account::where('code', '5100')->first();
        $operationalExpense = Account::where('code', '5200')->first();
        $purchaseExpense = Account::where('code', '5300')->first();
        $cash = Account::where('code', '1100')->first();
        $bank = Account::where('code', '1200')->first();

        $ref = 3;
        $post = function (string $date, string $description, int $debitAccountId, int $creditAccountId, float $amount) use ($admin, &$ref) {
            $entry = JournalEntry::create([
                'date' => $date,
                'reference' => 'JE-'.str_pad((string) $ref, 5, '0', STR_PAD_LEFT),
                'description' => $description,
                'status' => 'posted',
                'created_by' => $admin->id,
            ]);
            $entry->lines()->createMany([
                ['account_id' => $debitAccountId, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $creditAccountId, 'debit' => 0, 'credit' => $amount],
            ]);
            $ref++;
        };

        for ($monthsAgo = 5; $monthsAgo >= 0; $monthsAgo--) {
            $month = now()->subMonths($monthsAgo);

            $salesCount = fake()->numberBetween(3, 6);
            for ($i = 0; $i < $salesCount; $i++) {
                $day = $month->copy()->day(fake()->numberBetween(1, min(28, $month->daysInMonth)));
                $cashOrBank = fake()->boolean() ? $cash : $bank;
                $post($day->toDateString(), 'Sales revenue', $cashOrBank->id, $revenue->id, fake()->numberBetween(2_000_000, 18_000_000));
            }

            $salaryDay = $month->copy()->day(min(28, $month->daysInMonth));
            $post($salaryDay->toDateString(), 'Monthly salary payment', $salaryExpense->id, $bank->id, fake()->numberBetween(9_000_000, 14_000_000));

            $opCount = fake()->numberBetween(1, 3);
            for ($i = 0; $i < $opCount; $i++) {
                $day = $month->copy()->day(fake()->numberBetween(1, min(28, $month->daysInMonth)));
                $post($day->toDateString(), 'Operational expense', $operationalExpense->id, $cash->id, fake()->numberBetween(500_000, 4_000_000));
            }

            if (fake()->boolean(60)) {
                $day = $month->copy()->day(fake()->numberBetween(1, min(28, $month->daysInMonth)));
                $post($day->toDateString(), 'Purchase expense', $purchaseExpense->id, $bank->id, fake()->numberBetween(1_500_000, 8_000_000));
            }
        }
    }
}

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
    }
}

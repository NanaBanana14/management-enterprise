<?php

namespace Tests\Feature\Finance;

use App\Models\Account;
use App\Models\User;
use App\Services\CashBankService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CashBankTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_income_increases_cash_and_revenue()
    {
        $cash = Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset', 'is_cash_bank' => true]);
        $revenue = Account::create(['code' => '4100', 'name' => 'Sales Revenue', 'type' => 'revenue']);
        $user = User::factory()->create();

        app(CashBankService::class)->recordIncome($cash, $revenue, 500_000, now()->toDateString(), null, $user);

        $this->assertSame(500_000.0, $cash->balance());
        $this->assertSame(500_000.0, $revenue->balance());
    }

    public function test_recording_expense_against_a_non_cash_account_is_rejected()
    {
        $notCashBank = Account::create(['code' => '1400', 'name' => 'Inventory', 'type' => 'asset', 'is_cash_bank' => false]);
        $expense = Account::create(['code' => '5200', 'name' => 'Operational Expense', 'type' => 'expense']);
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        app(CashBankService::class)->recordExpense($notCashBank, $expense, 200_000, now()->toDateString(), null, $user);
    }
}

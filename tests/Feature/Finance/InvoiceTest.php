<?php

namespace Tests\Feature\Finance;

use App\Models\Account;
use App\Models\Customer;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_invoice_posts_a_balanced_journal_entry()
    {
        $ar = Account::create(['code' => '1300', 'name' => 'Accounts Receivable', 'type' => 'asset']);
        $revenue = Account::create(['code' => '4100', 'name' => 'Sales Revenue', 'type' => 'revenue']);
        $customer = Customer::create(['name' => 'Test Customer']);
        $user = User::factory()->create();

        $invoice = app(InvoiceService::class)->create($customer, $revenue, 1_000_000, now()->toDateString(), now()->addDays(30)->toDateString(), null, $user);

        $this->assertSame('unpaid', $invoice->status);
        $this->assertSame(1_000_000.0, $ar->balance());
        $this->assertSame(1_000_000.0, $revenue->balance());
    }

    public function test_marking_an_invoice_paid_settles_receivable_into_cash()
    {
        $ar = Account::create(['code' => '1300', 'name' => 'Accounts Receivable', 'type' => 'asset']);
        $revenue = Account::create(['code' => '4100', 'name' => 'Sales Revenue', 'type' => 'revenue']);
        $cash = Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset', 'is_cash_bank' => true]);
        $customer = Customer::create(['name' => 'Test Customer']);
        $user = User::factory()->create();

        $service = app(InvoiceService::class);
        $invoice = $service->create($customer, $revenue, 1_000_000, now()->toDateString(), now()->addDays(30)->toDateString(), null, $user);
        $service->markPaid($invoice, $cash, $user);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(0.0, $ar->balance());
        $this->assertSame(1_000_000.0, $cash->balance());
    }
}

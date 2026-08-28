<?php

namespace Tests\Feature\Finance;

use App\Models\Account;
use App\Models\User;
use App\Services\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class JournalTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_balanced_entry_posts_successfully()
    {
        $cash = Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
        $capital = Account::create(['code' => '3100', 'name' => 'Capital', 'type' => 'equity']);
        $user = User::factory()->create();

        $entry = app(JournalService::class)->create('2026-01-01', 'JE-00001', 'Opening balance', [
            ['account_id' => $cash->id, 'debit' => 1_000_000, 'credit' => 0],
            ['account_id' => $capital->id, 'debit' => 0, 'credit' => 1_000_000],
        ], $user);

        $this->assertSame(2, $entry->lines()->count());
        $this->assertSame(1_000_000.0, $cash->balance());
    }

    public function test_an_unbalanced_entry_is_rejected()
    {
        $cash = Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
        $capital = Account::create(['code' => '3100', 'name' => 'Capital', 'type' => 'equity']);
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        app(JournalService::class)->create('2026-01-01', 'JE-00002', null, [
            ['account_id' => $cash->id, 'debit' => 1_000_000, 'credit' => 0],
            ['account_id' => $capital->id, 'debit' => 0, 'credit' => 900_000],
        ], $user);
    }
}

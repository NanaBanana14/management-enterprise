<?php

namespace Tests\Feature\Crm;

use App\Enums\OpportunityStage;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\OpportunityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OpportunityTest extends TestCase
{
    use RefreshDatabase;

    private function opportunity(OpportunityService $service, User $creator): Opportunity
    {
        $customer = Customer::create(['name' => 'Test Customer']);
        $warehouse = Warehouse::create(['code' => 'WH-CRM', 'name' => 'Test Warehouse']);
        $product = Product::create(['sku' => 'SKU-CRM', 'name' => 'Test Product', 'unit' => 'pcs', 'price' => 100_000]);

        return $service->create(
            $customer,
            $warehouse,
            'Test Opportunity',
            [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100_000]],
            'Website',
            null,
            null,
            $creator,
        );
    }

    public function test_creating_an_opportunity_stores_it_with_its_lines()
    {
        $creator = User::factory()->create();
        $opportunity = $this->opportunity(app(OpportunityService::class), $creator);

        $this->assertSame('prospecting', $opportunity->stage->value);
        $this->assertSame(1, $opportunity->lines()->count());
    }

    public function test_move_stage_on_an_already_terminal_opportunity_is_rejected()
    {
        $service = app(OpportunityService::class);
        $creator = User::factory()->create();
        $opportunity = $this->opportunity($service, $creator);

        $service->moveStage($opportunity, OpportunityStage::Lost);

        $this->expectException(ValidationException::class);
        $service->moveStage($opportunity, OpportunityStage::Qualified);
    }

    public function test_marking_won_creates_a_matching_sales_order_and_links_it()
    {
        Account::create(['code' => '4100', 'name' => 'Sales Revenue', 'type' => 'revenue']);

        $service = app(OpportunityService::class);
        $creator = User::factory()->create();
        $opportunity = $this->opportunity($service, $creator);

        $won = $service->markWon($opportunity, $creator);

        $this->assertSame('won', $won->stage->value);
        $this->assertNotNull($won->sales_order_id);
        $this->assertSame('draft', $won->salesOrder->status);
        $this->assertSame(200_000.0, (float) $won->salesOrder->lines->sum(fn ($line) => $line->quantity * $line->unit_price));
    }

    public function test_a_note_can_be_added_and_is_attributed_to_its_author()
    {
        $service = app(OpportunityService::class);
        $creator = User::factory()->create();
        $opportunity = $this->opportunity($service, $creator);

        $service->addNote($opportunity, $creator, 'Follow up next week.');

        $note = $opportunity->notes()->first();
        $this->assertSame('Follow up next week.', $note->note);
        $this->assertSame($creator->id, $note->user_id);
    }
}

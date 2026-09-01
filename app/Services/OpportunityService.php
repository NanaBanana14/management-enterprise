<?php

namespace App\Services;

use App\Enums\OpportunityStage;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpportunityService
{
    private const TERMINAL_STAGES = [OpportunityStage::Won, OpportunityStage::Lost];

    public function __construct(private SalesOrderService $salesOrders) {}

    /**
     * @param  array<int, array{product_id: int, quantity: float, unit_price: float}>  $lines
     */
    public function create(
        Customer $customer,
        Warehouse $warehouse,
        string $title,
        array $lines,
        ?string $source,
        ?string $expectedCloseDate,
        ?int $assignedTo,
        User $creator,
    ): Opportunity {
        $lines = array_values(array_filter($lines, fn ($line) => (float) $line['quantity'] > 0));

        if (count($lines) === 0) {
            throw ValidationException::withMessages(['lines' => 'An opportunity needs at least one line.']);
        }

        return DB::transaction(function () use ($customer, $warehouse, $title, $lines, $source, $expectedCloseDate, $assignedTo, $creator) {
            $opportunity = Opportunity::create([
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'title' => $title,
                'stage' => OpportunityStage::Prospecting->value,
                'source' => $source,
                'expected_close_date' => $expectedCloseDate,
                'assigned_to' => $assignedTo,
                'created_by' => $creator->id,
            ]);

            foreach ($lines as $line) {
                $opportunity->lines()->create($line);
            }

            return $opportunity->load('lines');
        });
    }

    public function moveStage(Opportunity $opportunity, OpportunityStage $stage): Opportunity
    {
        return DB::transaction(function () use ($opportunity, $stage) {
            $opportunity = Opportunity::query()->whereKey($opportunity->id)->lockForUpdate()->firstOrFail();

            if (in_array($opportunity->stage, self::TERMINAL_STAGES, true)) {
                throw ValidationException::withMessages([
                    'stage' => "This opportunity is already {$opportunity->stage->label()} and can't be moved further.",
                ]);
            }

            $opportunity->update(['stage' => $stage->value]);

            return $opportunity;
        });
    }

    public function markWon(Opportunity $opportunity, User $user): Opportunity
    {
        return DB::transaction(function () use ($opportunity, $user) {
            $opportunity = Opportunity::query()->whereKey($opportunity->id)->lockForUpdate()->firstOrFail();

            if (in_array($opportunity->stage, self::TERMINAL_STAGES, true)) {
                throw ValidationException::withMessages([
                    'stage' => "This opportunity is already {$opportunity->stage->label()} and can't be moved further.",
                ]);
            }

            $lines = $opportunity->lines()->get(['product_id', 'quantity', 'unit_price'])->map->toArray()->all();

            $order = $this->salesOrders->create(
                $opportunity->customer,
                $opportunity->warehouse,
                now()->toDateString(),
                $lines,
                "From opportunity: {$opportunity->title}",
                $user,
            );

            $opportunity->update([
                'stage' => OpportunityStage::Won->value,
                'sales_order_id' => $order->id,
            ]);

            return $opportunity;
        });
    }

    public function addNote(Opportunity $opportunity, User $author, string $note): void
    {
        $opportunity->notes()->create(['user_id' => $author->id, 'note' => $note]);
    }
}

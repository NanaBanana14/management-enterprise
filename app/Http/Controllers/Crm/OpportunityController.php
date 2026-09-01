<?php

namespace App\Http\Controllers\Crm;

use App\Enums\OpportunityStage;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\OpportunityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

class OpportunityController extends Controller
{
    public function __construct(private readonly OpportunityService $opportunities) {}

    public function index(): Response
    {
        return Inertia::render('crm/opportunities/Index', [
            'opportunities' => Opportunity::query()
                ->with(['customer:id,name', 'warehouse:id,name', 'lines'])
                ->latest('created_at')
                ->get()
                ->map(fn (Opportunity $o) => [
                    'id' => $o->id,
                    'title' => $o->title,
                    'customer' => $o->customer->name,
                    'warehouse' => $o->warehouse->name,
                    'stage' => $o->stage->value,
                    'expected_close_date' => $o->expected_close_date?->toDateString(),
                    'total' => (float) $o->lines->sum(fn ($l) => $l->quantity * $l->unit_price),
                ]),
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(['id', 'sku', 'name', 'price']),
            'assignableUsers' => User::role(['Sales Staff', 'Super Admin'])->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('opportunity.manage'), 403);

        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'title' => ['required', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'expected_close_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $this->opportunities->create(
            Customer::findOrFail($data['customer_id']),
            Warehouse::findOrFail($data['warehouse_id']),
            $data['title'],
            $data['lines'],
            $data['source'] ?? null,
            $data['expected_close_date'] ?? null,
            $data['assigned_to'] ?? null,
            $request->user(),
        );

        return back()->with('success', 'Opportunity created.');
    }

    public function show(Opportunity $opportunity): Response
    {
        $opportunity->load(['customer:id,name', 'warehouse:id,name', 'assignee:id,name', 'creator:id,name', 'salesOrder:id,number', 'lines.product:id,sku,name', 'notes.author:id,name']);

        return Inertia::render('crm/opportunities/Show', [
            'opportunity' => [
                'id' => $opportunity->id,
                'title' => $opportunity->title,
                'stage' => $opportunity->stage->value,
                'source' => $opportunity->source,
                'expected_close_date' => $opportunity->expected_close_date?->toDateString(),
                'customer' => $opportunity->customer->only('id', 'name'),
                'warehouse' => $opportunity->warehouse->only('id', 'name'),
                'assignee' => $opportunity->assignee?->name,
                'creator' => $opportunity->creator->name,
                'salesOrder' => $opportunity->salesOrder?->only('id', 'number'),
                'lines' => $opportunity->lines->map(fn ($line) => [
                    'product' => "{$line->product->sku} — {$line->product->name}",
                    'quantity' => (float) $line->quantity,
                    'unit_price' => (float) $line->unit_price,
                ]),
                'notes' => $opportunity->notes->map(fn ($note) => [
                    'id' => $note->id,
                    'note' => $note->note,
                    'author' => $note->author->name,
                    'created_at' => $note->created_at->format('Y-m-d H:i'),
                ]),
            ],
            'stages' => array_map(fn (OpportunityStage $s) => ['value' => $s->value, 'label' => $s->label()], OpportunityStage::cases()),
        ]);
    }

    public function moveStage(Request $request, Opportunity $opportunity): RedirectResponse
    {
        abort_unless($request->user()->can('opportunity.manage'), 403);

        $data = $request->validate(['stage' => ['required', new Enum(OpportunityStage::class)]]);

        $this->opportunities->moveStage($opportunity, OpportunityStage::from($data['stage']));

        return back()->with('success', 'Stage updated.');
    }

    public function markWon(Request $request, Opportunity $opportunity): RedirectResponse
    {
        abort_unless($request->user()->can('opportunity.manage'), 403);

        $this->opportunities->markWon($opportunity, $request->user());

        return back()->with('success', 'Opportunity won — a sales order has been created.');
    }

    public function storeNote(Request $request, Opportunity $opportunity): RedirectResponse
    {
        abort_unless($request->user()->can('opportunity.manage'), 403);

        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);

        $this->opportunities->addNote($opportunity, $request->user(), $data['note']);

        return back()->with('success', 'Note added.');
    }
}

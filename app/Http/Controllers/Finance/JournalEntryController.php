<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\JournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JournalEntryController extends Controller
{
    public function __construct(private readonly JournalService $journal) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim();

        return Inertia::render('finance/journal/Index', [
            'entries' => JournalEntry::query()
                ->withSum('lines as total_debit', 'debit')
                ->when($search->isNotEmpty(), fn ($query) => $query->where(fn ($q) => $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")))
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString()
                ->through(fn (JournalEntry $entry) => [
                    'id' => $entry->id,
                    'date' => $entry->date->format('Y-m-d'),
                    'reference' => $entry->reference,
                    'description' => $entry->description,
                    'total' => (float) $entry->total_debit,
                ]),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('finance/journal/Create', [
            'accounts' => Account::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'nextReference' => 'JE-'.str_pad((string) (JournalEntry::max('id') + 1), 5, '0', STR_PAD_LEFT),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('journal.create'), 403);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'reference' => ['required', 'string', 'max:50', 'unique:journal_entries,reference'],
            'description' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'exists:accounts,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.memo' => ['nullable', 'string', 'max:255'],
        ]);

        $entry = $this->journal->create($data['date'], $data['reference'], $data['description'] ?? null, $data['lines'], $request->user());

        return to_route('finance.journal.show', $entry)->with('success', 'Journal entry posted.');
    }

    public function show(JournalEntry $journalEntry): Response
    {
        $journalEntry->load(['lines.account:id,code,name', 'creator:id,name']);

        return Inertia::render('finance/journal/Show', [
            'entry' => [
                'id' => $journalEntry->id,
                'date' => $journalEntry->date->format('Y-m-d'),
                'reference' => $journalEntry->reference,
                'description' => $journalEntry->description,
                'creator' => $journalEntry->creator?->name,
                'lines' => $journalEntry->lines->map(fn ($line) => [
                    'account' => "{$line->account->code} — {$line->account->name}",
                    'debit' => (float) $line->debit,
                    'credit' => (float) $line->credit,
                    'memo' => $line->memo,
                ]),
            ],
        ]);
    }
}

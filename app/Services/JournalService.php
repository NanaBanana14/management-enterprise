<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalService
{
    /**
     * @param  array<int, array{account_id: int, debit: float, credit: float, memo?: string}>  $lines
     */
    public function create(string $date, string $reference, ?string $description, array $lines, User $creator): JournalEntry
    {
        return DB::transaction(function () use ($date, $reference, $description, $lines, $creator) {
            $lines = array_values(array_filter($lines, fn ($line) => (float) $line['debit'] > 0 || (float) $line['credit'] > 0));

            if (count($lines) < 2) {
                throw ValidationException::withMessages(['lines' => 'A journal entry needs at least two lines.']);
            }

            $totalDebit = round(array_sum(array_column($lines, 'debit')), 2);
            $totalCredit = round(array_sum(array_column($lines, 'credit')), 2);

            if ($totalDebit !== $totalCredit) {
                throw ValidationException::withMessages([
                    'lines' => "Total debit ({$totalDebit}) must equal total credit ({$totalCredit}).",
                ]);
            }

            $entry = JournalEntry::create([
                'date' => $date,
                'reference' => $reference,
                'description' => $description,
                'status' => 'posted',
                'created_by' => $creator->id,
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?: 0,
                    'credit' => $line['credit'] ?: 0,
                    'memo' => $line['memo'] ?? null,
                ]);
            }

            return $entry;
        });
    }
}

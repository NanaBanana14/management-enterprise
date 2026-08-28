<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'number', 'customer_id', 'revenue_account_id', 'journal_entry_id', 'payment_journal_entry_id',
        'date', 'due_date', 'amount', 'description', 'status', 'paid_at', 'created_by',
    ];

    protected $casts = ['amount' => 'decimal:2', 'date' => 'date', 'due_date' => 'date', 'paid_at' => 'datetime'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function revenueAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'revenue_account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}

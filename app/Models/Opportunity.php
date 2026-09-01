<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\OpportunityStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opportunity extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'customer_id', 'warehouse_id', 'title', 'stage', 'source',
        'expected_close_date', 'assigned_to', 'sales_order_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'stage' => OpportunityStage::class,
            'expected_close_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OpportunityLine::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OpportunityNote::class);
    }
}

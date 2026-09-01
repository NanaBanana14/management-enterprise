<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\AssetCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'code', 'name', 'category', 'description', 'warehouse_id', 'employee_id',
        'acquisition_date', 'acquisition_cost', 'salvage_value', 'useful_life_months',
        'accumulated_depreciation', 'status', 'disposal_date', 'disposal_value',
        'disposal_journal_entry_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => AssetCategory::class,
            'acquisition_date' => 'date',
            'acquisition_cost' => 'decimal:2',
            'salvage_value' => 'decimal:2',
            'accumulated_depreciation' => 'decimal:2',
            'disposal_date' => 'date',
            'disposal_value' => 'decimal:2',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function disposalJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'disposal_journal_entry_id');
    }

    public function depreciationEntries(): HasMany
    {
        return $this->hasMany(AssetDepreciationEntry::class);
    }

    public function bookValue(): float
    {
        return (float) $this->acquisition_cost - (float) $this->accumulated_depreciation;
    }
}

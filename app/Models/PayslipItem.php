<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\PayslipItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipItem extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'payslip_id',
        'type',
        'label',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'type' => PayslipItemType::class,
            'amount' => 'decimal:2',
        ];
    }

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }
}

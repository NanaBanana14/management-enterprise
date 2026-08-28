<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\PayslipStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'basic_salary',
        'overtime_hours',
        'overtime_amount',
        'allowance_total',
        'bonus_total',
        'deduction_total',
        'net_salary',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'overtime_hours' => 'decimal:1',
            'overtime_amount' => 'decimal:2',
            'allowance_total' => 'decimal:2',
            'bonus_total' => 'decimal:2',
            'deduction_total' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'status' => PayslipStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayslipItem::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

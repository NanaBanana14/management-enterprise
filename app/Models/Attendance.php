<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'check_in_at',
        'check_out_at',
        'check_in_ip',
        'check_out_ip',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'status' => AttendanceStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isEarlyCheckout(): bool
    {
        if (! $this->check_out_at) {
            return false;
        }

        $scheduledEnd = $this->date->copy()->setTimeFromTimeString(config('attendance.work_end'));

        return $this->check_out_at->lt($scheduledEnd);
    }
}

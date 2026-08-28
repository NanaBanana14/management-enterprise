<?php

namespace App\Enums;

enum TrainingEnrollmentStatus: string
{
    case Enrolled = 'enrolled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Enrolled => 'Enrolled',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }
}

<?php

namespace App\Enums;

enum TrainingAudience: string
{
    case Staff = 'staff';
    case Recruitment = 'recruitment';

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'Staff Training',
            self::Recruitment => 'Recruitment Screening',
        };
    }
}

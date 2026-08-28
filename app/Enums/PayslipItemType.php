<?php

namespace App\Enums;

enum PayslipItemType: string
{
    case Allowance = 'allowance';
    case Bonus = 'bonus';
    case Deduction = 'deduction';

    public function label(): string
    {
        return match ($this) {
            self::Allowance => 'Allowance',
            self::Bonus => 'Bonus',
            self::Deduction => 'Deduction',
        };
    }
}

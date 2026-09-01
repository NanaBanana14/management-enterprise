<?php

namespace App\Enums;

enum AssetCategory: string
{
    case Equipment = 'equipment';
    case Vehicle = 'vehicle';
    case Furniture = 'furniture';
    case Building = 'building';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Equipment => 'Equipment',
            self::Vehicle => 'Vehicle',
            self::Furniture => 'Furniture',
            self::Building => 'Building',
            self::Other => 'Other',
        };
    }
}

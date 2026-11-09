<?php

namespace App\Enums;

/**
 * Times of day a menu can be planned for. Values match the Category enum so a
 * planned breakfast and a breakfast recipe read the same everywhere.
 */
enum MealSlot: string
{
    case Sarapan = 'sarapan';
    case MakanSiang = 'makan_siang';
    case MakanMalam = 'makan_malam';
    case Camilan = 'camilan';

    public function label(): string
    {
        return match ($this) {
            self::Sarapan => 'Sarapan',
            self::MakanSiang => 'Makan siang',
            self::MakanMalam => 'Makan malam',
            self::Camilan => 'Camilan',
        };
    }
}

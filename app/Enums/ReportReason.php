<?php

namespace App\Enums;

enum ReportReason: string
{
    case Spam = 'spam';
    case HakCipta = 'hak_cipta';
    case TidakPantas = 'tidak_pantas';
    case Berbahaya = 'berbahaya';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Spam => 'Spam atau iklan',
            self::HakCipta => 'Menyalin karya orang tanpa izin',
            self::TidakPantas => 'Konten tidak pantas',
            self::Berbahaya => 'Berbahaya atau menyesatkan',
            self::Lainnya => 'Lainnya',
        };
    }
}

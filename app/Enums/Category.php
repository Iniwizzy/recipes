<?php

namespace App\Enums;

enum Category: string
{
    case Sarapan = 'sarapan';
    case MakanSiang = 'makan_siang';
    case MakanMalam = 'makan_malam';
    case Camilan = 'camilan';
    case Minuman = 'minuman';

    public function label(): string
    {
        return match ($this) {
            self::Sarapan => 'Sarapan',
            self::MakanSiang => 'Makan siang',
            self::MakanMalam => 'Makan malam',
            self::Camilan => 'Camilan',
            self::Minuman => 'Minuman',
        };
    }

    /**
     * Fixed chart slot (1-5) so a category keeps the same color in every chart.
     */
    public function slot(): int
    {
        return match ($this) {
            self::Sarapan => 1,
            self::MakanSiang => 2,
            self::MakanMalam => 3,
            self::Camilan => 4,
            self::Minuman => 5,
        };
    }

    /**
     * Full class name (not built from the slot) so Tailwind can find it in the source.
     */
    public function swatch(): string
    {
        return match ($this) {
            self::Sarapan => 'bg-s1',
            self::MakanSiang => 'bg-s2',
            self::MakanMalam => 'bg-s3',
            self::Camilan => 'bg-s4',
            self::Minuman => 'bg-s5',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}

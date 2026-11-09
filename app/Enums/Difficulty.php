<?php

namespace App\Enums;

enum Difficulty: string
{
    case Mudah = 'mudah';
    case Sedang = 'sedang';
    case Sulit = 'sulit';

    public function label(): string
    {
        return ucfirst($this->value);
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

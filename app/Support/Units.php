<?php

namespace App\Support;

/**
 * Units people write in Indonesian recipes. Used for the recipe form's suggestions
 * and to recognise "2 butir telur" as amount + unit + name.
 */
class Units
{
    /** Shown as suggestions in the recipe form. */
    public const COMMON = [
        'piring', 'butir', 'siung', 'buah', 'batang', 'lembar', 'ikat', 'sdm', 'sdt',
        'gram', 'kg', 'ml', 'liter', 'gelas', 'cm', 'potong', 'ekor', 'secukupnya',
    ];

    /** Everything recognised when parsing a typed line. */
    public const KNOWN = [
        'piring', 'butir', 'siung', 'buah', 'batang', 'lembar', 'ikat', 'sdm', 'sdt',
        'gram', 'gr', 'g', 'kg', 'ons', 'ml', 'liter', 'l', 'gelas', 'cangkir', 'cm',
        'potong', 'ekor', 'bungkus', 'sachet', 'papan', 'bonggol', 'ruas', 'genggam',
        'kaleng', 'botol', 'pak', 'kotak', 'sisir', 'tangkai', 'helai', 'mangkuk',
    ];

    public static function isKnown(string $word): bool
    {
        return in_array(mb_strtolower(trim($word)), self::KNOWN, true);
    }
}

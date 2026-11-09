<?php

namespace App\Support;

/**
 * Formats ingredient amounts the way people write them in Indonesian recipes:
 * common fractions (½, ⅓, ¼ …) when close, otherwise one decimal with a comma.
 * resources/js/quantity.js mirrors this for live portion scaling.
 */
class Quantity
{
    private const MARKS = [
        [0, ''],
        [0.25, '¼'],
        [1 / 3, '⅓'],
        [0.5, '½'],
        [2 / 3, '⅔'],
        [0.75, '¾'],
        [1, ''],
    ];

    public static function format(?float $value): string
    {
        if ($value === null) {
            return '';
        }

        $whole = (int) floor($value + 1e-9);
        $fraction = $value - $whole;

        $best = self::MARKS[0];
        $gap = 1.0;
        foreach (self::MARKS as $mark) {
            $distance = abs($fraction - $mark[0]);
            if ($distance < $gap) {
                $gap = $distance;
                $best = $mark;
            }
        }

        if ($gap > 0.04) {
            return str_replace('.', ',', (string) round($value, 1));
        }

        if ($best[0] === 1) {
            $whole++;
        }
        $glyph = $best[0] === 1 ? '' : $best[1];

        if ($whole === 0) {
            return $glyph !== '' ? $glyph : '0';
        }

        return $glyph !== '' ? $whole.' '.$glyph : (string) $whole;
    }

    /**
     * Reads what people type in the form: "2", "1,5", "1.5", "1/2", "1 1/2".
     */
    public static function parse(?string $input): ?float
    {
        $input = trim((string) $input);
        if ($input === '') {
            return null;
        }

        $input = str_replace(',', '.', $input);
        $glyphs = ['½' => ' 1/2', '⅓' => ' 1/3', '⅔' => ' 2/3', '¼' => ' 1/4', '¾' => ' 3/4'];
        $input = trim(strtr($input, $glyphs));

        if (preg_match('/^(\d+)\s+(\d+)\/(\d+)$/', $input, $m) && (int) $m[3] > 0) {
            return (int) $m[1] + (int) $m[2] / (int) $m[3];
        }
        if (preg_match('/^(\d+)\/(\d+)$/', $input, $m) && (int) $m[2] > 0) {
            return (int) $m[1] / (int) $m[2];
        }
        if (is_numeric($input)) {
            return (float) $input;
        }

        return null;
    }
}

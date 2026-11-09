<?php

namespace App\Support;

use App\Models\Recipe;
use App\Models\ShoppingListItem;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Adds ingredients to a user's shopping list, merging them with items that are
 * still on the list: "2 siung bawang merah" + "3 siung bawang merah" becomes
 * "5 siung bawang merah". Items only merge when name and unit match and both
 * have an amount (or both are "secukupnya"), so nothing is silently lost.
 */
class ShoppingList
{
    /**
     * @param  array<int, array{name: string, quantity: ?float, unit: ?string}>  $rows
     * @return int number of rows added or merged
     */
    public static function add(User $user, array $rows, ?string $source = null): int
    {
        $open = $user->shoppingListItems()->whereNull('checked_at')->get();
        $count = 0;

        foreach ($rows as $row) {
            $name = self::clean($row['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $unit = self::clean($row['unit'] ?? '') ?: null;
            $quantity = isset($row['quantity']) ? round((float) $row['quantity'], 2) : null;

            $match = $open->first(fn (ShoppingListItem $item) => self::key($item->name, $item->unit, $item->quantity !== null)
                === self::key($name, $unit, $quantity !== null));

            if ($match) {
                if ($quantity !== null) {
                    $match->quantity = round($match->quantity + $quantity, 2);
                }
                if ($source && ! in_array($source, $match->sources ?? [], true)) {
                    $match->sources = [...($match->sources ?? []), $source];
                }
                $match->save();
            } else {
                $open->push($user->shoppingListItems()->create([
                    'name' => $name,
                    'quantity' => $quantity,
                    'unit' => $unit,
                    'sources' => $source ? [$source] : null,
                ]));
            }
            $count++;
        }

        return $count;
    }

    /**
     * A recipe's ingredients as shopping rows, scaled from the recipe's own
     * portion count to $servings. $indexes limits it to some ingredients
     * (positions in the recipe's ingredient order).
     *
     * @param  array<int, int>|null  $indexes
     * @return array<int, array{name: string, quantity: ?float, unit: ?string}>
     */
    public static function rowsFromRecipe(Recipe $recipe, int $servings, ?array $indexes = null): array
    {
        $factor = max(1, min($servings, 50)) / max(1, $recipe->servings);
        $ingredients = $recipe->ingredients()->get()->values();

        if ($indexes !== null) {
            $ingredients = collect(array_unique(array_map('intval', $indexes)))
                ->map(fn (int $index) => $ingredients[$index] ?? null)
                ->filter();
        }

        return $ingredients->map(fn ($ingredient) => [
            'name' => $ingredient->name,
            'quantity' => $ingredient->quantity === null ? null : $ingredient->quantity * $factor,
            'unit' => $ingredient->unit,
        ])->values()->all();
    }

    /**
     * Reads a typed line such as "2 butir telur", "1,5 kg ayam", "1/2 sdt garam" or "garam".
     *
     * @return array{name: string, quantity: ?float, unit: ?string}
     */
    public static function parseLine(string $line): array
    {
        $line = self::clean($line);
        $quantity = null;
        $rest = $line;

        $number = '\d+\s+\d+/\d+|\d+(?:[.,]\d+)?(?:/\d+)?|[½⅓⅔¼¾]';
        if (preg_match("~^({$number})\s+(.+)$~u", $line, $m)) {
            // "2 butir telur"
            $quantity = Quantity::parse($m[1]);
            $rest = $m[2];
        } elseif (preg_match("~^({$number})(\pL+)\s+(.+)$~u", $line, $m) && Units::isKnown($m[2])) {
            // "2kg ayam" (unit written against the number); "7up" stays a name
            $quantity = Quantity::parse($m[1]);
            $rest = $m[2].' '.$m[3];
        }

        $unit = null;
        $parts = explode(' ', $rest, 2);
        if ($quantity !== null && count($parts) === 2 && Units::isKnown($parts[0])) {
            $unit = mb_strtolower($parts[0]);
            $rest = $parts[1];
        }

        return ['name' => $rest, 'quantity' => $quantity, 'unit' => $unit];
    }

    private static function clean(?string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $value));
    }

    private static function key(string $name, ?string $unit, bool $hasAmount): string
    {
        return Str::lower(self::clean($name)).'|'.Str::lower(self::clean($unit)).'|'.($hasAmount ? '1' : '0');
    }
}

<?php

namespace App\Models;

use App\Support\Quantity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ingredient extends Model
{
    protected $fillable = ['position', 'quantity', 'unit', 'name', 'note'];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'position' => 'integer',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function amount(float $factor = 1): string
    {
        if ($this->quantity === null) {
            return $this->unit ?: 'Secukupnya';
        }

        return trim(Quantity::format($this->quantity * $factor).' '.$this->unit);
    }
}

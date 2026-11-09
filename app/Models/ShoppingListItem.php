<?php

namespace App\Models;

use App\Support\Quantity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShoppingListItem extends Model
{
    protected $fillable = ['name', 'quantity', 'unit', 'sources', 'checked_at'];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'sources' => 'array',
            'checked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function amount(): string
    {
        if ($this->quantity === null) {
            return (string) $this->unit;
        }

        return trim(Quantity::format($this->quantity).' '.$this->unit);
    }

    /**
     * One line of the plain-text list that is copied or sent to WhatsApp.
     */
    public function line(): string
    {
        return trim($this->amount().' '.$this->name);
    }
}

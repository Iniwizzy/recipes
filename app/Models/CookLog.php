<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CookLog extends Model
{
    protected $fillable = ['user_id', 'recipe_id', 'cooked_at', 'minutes', 'servings'];

    protected function casts(): array
    {
        return [
            'cooked_at' => 'datetime',
            'minutes' => 'integer',
            'servings' => 'integer',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

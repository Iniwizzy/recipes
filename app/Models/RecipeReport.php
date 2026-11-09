<?php

namespace App\Models;

use App\Enums\ReportReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeReport extends Model
{
    protected $fillable = ['user_id', 'recipe_id', 'reason', 'note', 'resolved_at', 'resolution'];

    protected function casts(): array
    {
        return [
            'reason' => ReportReason::class,
            'resolved_at' => 'datetime',
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

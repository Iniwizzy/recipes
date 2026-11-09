<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Step extends Model
{
    protected $fillable = ['position', 'instruction', 'timer_seconds'];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'timer_seconds' => 'integer',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function timerMinutes(): ?int
    {
        return $this->timer_seconds ? (int) round($this->timer_seconds / 60) : null;
    }
}

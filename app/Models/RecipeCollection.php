<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A user's own grouping of recipes ("Menu anak", "Lebaran", "Bekal").
 * Named RecipeCollection so it is never confused with Laravel's Collection class.
 */
class RecipeCollection extends Model
{
    protected $table = 'collections';

    protected $fillable = ['name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'collection_recipe', 'collection_id', 'recipe_id')->withTimestamps();
    }

    /**
     * Card color (tint-1 … tint-5), fixed per collection.
     */
    public function tintClass(): string
    {
        return 'tint-'.(($this->id % 5) + 1);
    }
}

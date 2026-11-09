<?php

namespace App\Models;

use App\Enums\Category;
use App\Enums\Difficulty;
use App\Support\CoverImage;
use Database\Factories\RecipeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Recipe extends Model
{
    /** @use HasFactory<RecipeFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'category',
        'difficulty',
        'servings',
        'prep_minutes',
        'cook_minutes',
        'cover_path',
        'source_url',
        'notes',
        'is_favorite',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'category' => Category::class,
            'difficulty' => Difficulty::class,
            'servings' => 'integer',
            'prep_minutes' => 'integer',
            'cook_minutes' => 'integer',
            'is_favorite' => 'boolean',
            'is_public' => 'boolean',
            'cooked_count' => 'integer',
            'last_cooked_at' => 'datetime',
            'published_at' => 'datetime',
            'hidden_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Recipe $recipe) {
            if (! $recipe->slug || $recipe->isDirty('title')) {
                $recipe->slug = static::uniqueSlug($recipe->title, $recipe->id);
            }
            if ($recipe->is_public && ! $recipe->published_at) {
                $recipe->published_at = now();
            }
        });
    }

    /**
     * Recipes anyone may see in the catalog: shared by their owner and not taken down.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('recipes.is_public', true)->whereNull('recipes.hidden_at');
    }

    public function isListed(): bool
    {
        return $this->is_public && $this->hidden_at === null;
    }

    public function savers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'recipe_saves')->withTimestamps();
    }

    public function forkedFrom(): BelongsTo
    {
        return $this->belongsTo(Recipe::class, 'forked_from_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(RecipeReport::class);
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'resep';
        $slug = $base;
        $n = 2;
        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class)->orderBy('position');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(Step::class)->orderBy('position');
    }

    public function cookLogs(): HasMany
    {
        return $this->hasMany(CookLog::class);
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(RecipeCollection::class, 'collection_recipe', 'recipe_id', 'collection_id')->withTimestamps();
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('title', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhereHas('ingredients', fn (Builder $i) => $i->where('name', 'like', $like));
        });
    }

    public function totalMinutes(): ?int
    {
        $total = (int) $this->prep_minutes + (int) $this->cook_minutes;

        return $total > 0 ? $total : null;
    }

    public function coverUrl(bool $thumb = false): ?string
    {
        if (! $this->cover_path) {
            return null;
        }

        $disk = Storage::disk('public');
        $thumbPath = CoverImage::thumbPath($this->cover_path);

        return $thumb && $disk->exists($thumbPath) ? $disk->url($thumbPath) : $disk->url($this->cover_path);
    }

    /**
     * Tint classes (background + icon color) used when a recipe has no cover photo.
     */
    public function tintClass(): string
    {
        return 'tint-'.$this->category->slot();
    }

    public function sourceLabel(): ?string
    {
        if (! $this->source_url) {
            return null;
        }

        $host = Str::of(parse_url($this->source_url, PHP_URL_HOST) ?? '')->replaceStart('www.', '')->toString();

        return match (true) {
            str_contains($host, 'tiktok') => 'TikTok',
            str_contains($host, 'youtube') || str_contains($host, 'youtu.be') => 'YouTube',
            str_contains($host, 'instagram') => 'Instagram',
            default => $host ?: null,
        };
    }
}

<?php

namespace App\Support;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * "Salin & ubah": gives a cook their own private, editable copy of a public
 * recipe. The copy remembers where it came from so the author keeps credit.
 */
class RecipeCopier
{
    public static function copy(Recipe $source, User $user): Recipe
    {
        $source->loadMissing('ingredients', 'steps');

        return DB::transaction(function () use ($source, $user) {
            $copy = new Recipe($source->only([
                'title', 'description', 'category', 'difficulty', 'servings',
                'prep_minutes', 'cook_minutes', 'source_url',
            ]));
            $copy->user_id = $user->id;
            $copy->forked_from_id = $source->id;
            $copy->is_public = false;
            $copy->cover_path = self::copyCover($source->cover_path);
            $copy->save();

            foreach ($source->ingredients as $ingredient) {
                $copy->ingredients()->create($ingredient->only(['position', 'quantity', 'unit', 'name', 'note']));
            }
            foreach ($source->steps as $step) {
                $copy->steps()->create($step->only(['position', 'instruction', 'timer_seconds']));
            }

            return $copy;
        });
    }

    private static function copyCover(?string $path): ?string
    {
        $disk = Storage::disk('public');
        if (! $path || ! $disk->exists($path)) {
            return null;
        }

        $newPath = 'covers/'.Str::uuid()->toString().'.'.pathinfo($path, PATHINFO_EXTENSION);
        $disk->copy($path, $newPath);
        if ($disk->exists(CoverImage::thumbPath($path))) {
            $disk->copy(CoverImage::thumbPath($path), CoverImage::thumbPath($newPath));
        }

        return $newPath;
    }
}

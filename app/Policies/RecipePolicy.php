<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\User;

class RecipePolicy
{
    /**
     * Owners always see their recipes; everyone (guests too) sees recipes in the
     * public catalog; moderators also see recipes they took down.
     */
    public function view(?User $user, Recipe $recipe): bool
    {
        if ($user && ($user->id === $recipe->user_id || $user->is_admin)) {
            return true;
        }

        return $recipe->isListed();
    }

    public function update(User $user, Recipe $recipe): bool
    {
        return $user->id === $recipe->user_id;
    }

    public function delete(User $user, Recipe $recipe): bool
    {
        return $user->id === $recipe->user_id;
    }

    public function cook(User $user, Recipe $recipe): bool
    {
        return $this->view($user, $recipe);
    }

    /**
     * Bookmarking, copying and reporting are for other people's public recipes.
     */
    public function save(User $user, Recipe $recipe): bool
    {
        return $user->id !== $recipe->user_id && $recipe->isListed();
    }

    public function fork(User $user, Recipe $recipe): bool
    {
        return $user->id !== $recipe->user_id && $recipe->isListed();
    }

    public function report(User $user, Recipe $recipe): bool
    {
        return $user->id !== $recipe->user_id && $recipe->isListed();
    }
}

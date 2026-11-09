<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\User;

class RecipePolicy
{
    public function view(?User $user, Recipe $recipe): bool
    {
        return $recipe->is_public || ($user && $user->id === $recipe->user_id);
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
}

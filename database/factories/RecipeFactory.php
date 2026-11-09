<?php

namespace Database\Factories;

use App\Enums\Category;
use App\Enums\Difficulty;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => ucwords(fake()->unique()->words(3, true)),
            'description' => fake()->sentence(12),
            'category' => fake()->randomElement(Category::cases()),
            'difficulty' => fake()->randomElement(Difficulty::cases()),
            'servings' => 2,
            'prep_minutes' => 10,
            'cook_minutes' => 20,
        ];
    }

    public function withContent(int $ingredients = 3, int $steps = 3): static
    {
        return $this->afterCreating(function (Recipe $recipe) use ($ingredients, $steps) {
            for ($i = 0; $i < $ingredients; $i++) {
                $recipe->ingredients()->create([
                    'position' => $i,
                    'quantity' => fake()->randomElement([0.5, 1, 2, 3]),
                    'unit' => fake()->randomElement(['sdm', 'sdt', 'buah', 'siung']),
                    'name' => fake()->word(),
                ]);
            }
            for ($i = 0; $i < $steps; $i++) {
                $recipe->steps()->create([
                    'position' => $i,
                    'instruction' => fake()->sentence(10),
                    'timer_seconds' => $i === 1 ? 120 : null,
                ]);
            }
        });
    }
}

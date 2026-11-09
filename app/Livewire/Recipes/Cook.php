<?php

namespace App\Livewire\Recipes;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Step;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.cook')]
class Cook extends Component
{
    public Recipe $recipe;

    public ?int $timesCooked = null;

    public function mount(Recipe $recipe): void
    {
        $this->authorize('cook', $recipe);
        $this->recipe = $recipe;
    }

    /**
     * Called once when the cook taps "Selesai" on the last step.
     */
    public function finish(int $minutes): int
    {
        $this->authorize('cook', $this->recipe);
        $minutes = max(1, min($minutes, 600));

        auth()->user()->cookLogs()->create([
            'recipe_id' => $this->recipe->id,
            'cooked_at' => now(),
            'minutes' => $minutes,
            'servings' => $this->recipe->servings,
        ]);

        $this->recipe->increment('cooked_count', 1, ['last_cooked_at' => now()]);
        $this->timesCooked = $this->recipe->cooked_count;

        return $this->timesCooked;
    }

    public function render()
    {
        $this->recipe->load('ingredients', 'steps');

        return view('livewire.recipes.cook', [
            'steps' => $this->recipe->steps->map(fn (Step $step) => [
                'text' => $step->instruction,
                'timer' => $step->timer_seconds ?: null,
                'chips' => $this->mentionedIngredients($step, $this->recipe->ingredients),
            ])->values()->all(),
        ])->title('Mode Masak: '.$this->recipe->title);
    }

    /**
     * Ingredients a step talks about, so the cook sees what to have ready.
     * Matches the full name, then its first two words, then the first word
     * unless that word is too generic ("bawang" would match every step).
     *
     * @return array<int, string>
     */
    private function mentionedIngredients(Step $step, Collection $ingredients): array
    {
        $text = Str::lower($step->instruction);
        $generic = ['bawang', 'daun', 'air', 'cabai', 'tepung', 'gula', 'ikan'];

        return $ingredients->filter(function (Ingredient $ingredient) use ($text, $generic) {
            $name = Str::lower($ingredient->name);
            $words = preg_split('/\s+/', $name) ?: [];
            $candidates = [$name, implode(' ', array_slice($words, 0, 2))];
            if (isset($words[0]) && mb_strlen($words[0]) >= 4 && ! in_array($words[0], $generic, true)) {
                $candidates[] = $words[0];
            }
            foreach (array_unique($candidates) as $candidate) {
                if ($candidate !== '' && preg_match('/\b'.preg_quote($candidate, '/').'\b/u', $text)) {
                    return true;
                }
            }

            return false;
        })->map(fn (Ingredient $i) => trim($i->amount().' '.$i->name))->values()->all();
    }
}

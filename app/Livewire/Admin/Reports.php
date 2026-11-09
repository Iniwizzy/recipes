<?php

namespace App\Livewire\Admin;

use App\Models\Recipe;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Moderators review reported recipes: hide them from the catalog or dismiss the reports.
 * Hiding never deletes anything; the owner keeps the recipe.
 */
#[Title('Laporan resep')]
class Reports extends Component
{
    public function hide(int $recipeId): void
    {
        $this->authorize('moderate');
        $recipe = Recipe::findOrFail($recipeId);
        $recipe->forceFill(['hidden_at' => now()])->save();
        $recipe->reports()->whereNull('resolved_at')->update(['resolved_at' => now(), 'resolution' => 'disembunyikan']);
    }

    public function dismiss(int $recipeId): void
    {
        $this->authorize('moderate');
        Recipe::findOrFail($recipeId)->reports()->whereNull('resolved_at')->update(['resolved_at' => now(), 'resolution' => 'diabaikan']);
    }

    public function unhide(int $recipeId): void
    {
        $this->authorize('moderate');
        Recipe::findOrFail($recipeId)->forceFill(['hidden_at' => null])->save();
    }

    public function render()
    {
        $this->authorize('moderate');

        return view('livewire.admin.reports', [
            'open' => Recipe::query()
                ->whereHas('reports', fn ($q) => $q->whereNull('resolved_at'))
                ->with(['user:id,name,username', 'reports' => fn ($q) => $q->whereNull('resolved_at')->with('user:id,name')->latest()])
                ->get()
                ->sortByDesc(fn (Recipe $r) => $r->reports->count()),
            'hidden' => Recipe::query()->whereNotNull('hidden_at')->with('user:id,name')->latest('hidden_at')->limit(20)->get(),
        ]);
    }
}

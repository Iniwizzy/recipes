<?php

namespace App\Livewire\Recipes;

use App\Models\Recipe;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Show extends Component
{
    public Recipe $recipe;

    public function mount(Recipe $recipe): void
    {
        $this->authorize('view', $recipe);
        $this->recipe = $recipe;
    }

    public function toggleFavorite(): void
    {
        $this->authorize('update', $this->recipe);
        $this->recipe->update(['is_favorite' => ! $this->recipe->is_favorite]);
    }

    public function delete()
    {
        $this->authorize('delete', $this->recipe);

        if ($this->recipe->cover_path) {
            Storage::disk('public')->delete($this->recipe->cover_path);
        }
        $title = $this->recipe->title;
        $this->recipe->delete();

        session()->flash('status', "Resep \"{$title}\" sudah dihapus.");

        return $this->redirectRoute('recipes.index', navigate: true);
    }

    public function render()
    {
        $this->recipe->load('ingredients', 'steps');
        $recipe = $this->recipe;

        return view('livewire.recipes.show', [
            'canEdit' => auth()->user()?->can('update', $recipe) ?? false,
            'scaler' => [
                'base' => $recipe->servings,
                'items' => $recipe->ingredients->map(fn ($i) => [
                    'q' => $i->quantity,
                    'unit' => $i->unit,
                ])->all(),
            ],
            'timers' => $recipe->steps->whereNotNull('timer_seconds')->count(),
            'youtubeId' => $this->youtubeId($recipe->source_url),
        ])->title($recipe->title);
    }

    private function youtubeId(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return $m[1];
        }

        return null;
    }
}

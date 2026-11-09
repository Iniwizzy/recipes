<?php

namespace App\Livewire\Recipes;

use App\Models\Recipe;
use App\Support\CoverImage;
use App\Support\ShoppingList;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Show extends Component
{
    public Recipe $recipe;

    public ?string $addedMessage = null;

    public string $newCollection = '';

    public function mount(Recipe $recipe): void
    {
        $this->authorize('view', $recipe);
        $this->recipe = $recipe;
    }

    public function toggleCollection(int $collectionId): void
    {
        $this->authorize('view', $this->recipe);
        auth()->user()->recipeCollections()->findOrFail($collectionId)->recipes()->toggle($this->recipe->id);
    }

    public function createCollection(): void
    {
        $this->authorize('view', $this->recipe);
        $this->newCollection = trim($this->newCollection);
        $this->validate(
            ['newCollection' => ['required', 'string', 'max:60', Rule::unique('collections', 'name')->where('user_id', auth()->id())]],
            [
                'newCollection.required' => 'Beri nama koleksinya dulu.',
                'newCollection.unique' => 'Koleksi dengan nama ini sudah ada. Centang saja di atas.',
            ]
        );

        auth()->user()->recipeCollections()->create(['name' => $this->newCollection])
            ->recipes()->attach($this->recipe->id);
        $this->reset('newCollection');
    }

    public function toggleFavorite(): void
    {
        $this->authorize('update', $this->recipe);
        $this->recipe->update(['is_favorite' => ! $this->recipe->is_favorite]);
    }

    /**
     * Puts the ingredients that are not ticked yet on the shopping list,
     * scaled to the portion count currently shown on the page.
     *
     * @param  array<int, int>  $indexes  positions of the unticked ingredients
     */
    public function addToShoppingList(int $servings, array $indexes): void
    {
        $this->authorize('view', $this->recipe);

        $rows = ShoppingList::rowsFromRecipe($this->recipe, $servings, $indexes);
        $count = ShoppingList::add(auth()->user(), $rows, $this->recipe->title);
        $this->addedMessage = $count ? "{$count} bahan masuk daftar belanja." : null;
    }

    public function delete()
    {
        $this->authorize('delete', $this->recipe);

        CoverImage::delete($this->recipe->cover_path);
        $title = $this->recipe->title;
        $this->recipe->delete();

        session()->flash('status', "Resep \"{$title}\" sudah dihapus.");

        return $this->redirectRoute('recipes.index', navigate: true);
    }

    public function render()
    {
        $this->recipe->load('ingredients', 'steps');
        $recipe = $this->recipe;

        $inCollections = $recipe->collections()->where('collections.user_id', auth()->id())->pluck('collections.id')->all();

        return view('livewire.recipes.show', [
            'canEdit' => auth()->user()?->can('update', $recipe) ?? false,
            'collections' => auth()->user()->recipeCollections()->withCount('recipes')->orderBy('name')->get(),
            'inCollections' => $inCollections,
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

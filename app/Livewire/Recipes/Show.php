<?php

namespace App\Livewire\Recipes;

use App\Enums\MealSlot;
use App\Enums\ReportReason;
use App\Models\Recipe;
use App\Support\CoverImage;
use App\Support\RecipeCopier;
use App\Support\ShoppingList;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Recipe page. The owner can edit; anyone may read a recipe from the public
 * catalog (guests too); signed-in cooks can save, copy, plan and report it.
 */
class Show extends Component
{
    public Recipe $recipe;

    public ?string $addedMessage = null;

    public string $newCollection = '';

    public string $planDate = '';

    public string $planSlot = 'makan_malam';

    public int $planServings = 2;

    public ?string $plannedMessage = null;

    public string $reportReason = 'spam';

    public string $reportNote = '';

    public bool $reportSent = false;

    public function mount(Recipe $recipe): void
    {
        if (! auth()->check() && ! $recipe->isListed()) {
            // Probably the owner's own private recipe: sign in first, then come back here.
            throw new AuthenticationException;
        }

        $this->authorize('view', $recipe);
        $this->recipe = $recipe;
        $this->planDate = now()->toDateString();
        $this->planServings = $recipe->servings ?: 2;
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
     * Bookmark someone else's public recipe; it then shows up under "Disimpan"
     * and in the meal-plan picker, and follows the author's later edits.
     */
    public function toggleSave(): void
    {
        $this->authorize('save', $this->recipe);
        $this->recipe->savers()->toggle(auth()->id());
    }

    public function fork()
    {
        $this->authorize('fork', $this->recipe);
        $copy = RecipeCopier::copy($this->recipe, auth()->user());
        session()->flash('status', 'Salinan dibuat di Resep saya. Ubah sesukamu; resep aslinya tidak ikut berubah.');

        return $this->redirectRoute('recipes.show', $copy, navigate: true);
    }

    public function planRecipe(): void
    {
        $this->authorize('view', $this->recipe);
        $this->validate([
            'planDate' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(27)->toDateString()],
            'planSlot' => ['required', Rule::enum(MealSlot::class)],
            'planServings' => ['required', 'integer', 'min:1', 'max:50'],
        ], [
            'planDate.after_or_equal' => 'Pilih hari ini atau hari setelahnya.',
            'planDate.before_or_equal' => 'Rencana paling jauh 4 minggu ke depan.',
        ]);

        auth()->user()->mealPlans()->create([
            'recipe_id' => $this->recipe->id,
            'date' => $this->planDate,
            'slot' => MealSlot::from($this->planSlot),
            'servings' => $this->planServings,
        ]);

        $date = Carbon::parse($this->planDate);
        $this->plannedMessage = 'Masuk rencana '.Str::lower($date->translatedFormat('l, j M')).', '.Str::lower(MealSlot::from($this->planSlot)->label()).'.';
    }

    public function report(): void
    {
        $this->authorize('report', $this->recipe);
        $this->validate([
            'reportReason' => ['required', Rule::enum(ReportReason::class)],
            'reportNote' => ['nullable', 'string', 'max:500'],
        ]);

        // One open report per person per recipe; sending again updates it.
        $this->recipe->reports()->updateOrCreate(
            ['user_id' => auth()->id(), 'resolved_at' => null],
            ['reason' => $this->reportReason, 'note' => trim($this->reportNote) ?: null]
        );
        $this->reportSent = true;
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
        $this->recipe->load('ingredients', 'steps', 'user:id,name,username', 'forkedFrom.user:id,name,username');
        $recipe = $this->recipe;
        $user = auth()->user();
        $isOwner = $user && $user->id === $recipe->user_id;

        $forkedFrom = $recipe->forkedFrom && ($user?->can('view', $recipe->forkedFrom) || $recipe->forkedFrom->isListed())
            ? $recipe->forkedFrom
            : null;

        $days = [];
        for ($i = 0; $i < 14; $i++) {
            $date = now()->addDays($i);
            $days[$date->toDateString()] = $i === 0 ? 'Hari ini' : ($i === 1 ? 'Besok' : $date->translatedFormat('l, j M'));
        }

        return view('livewire.recipes.show', [
            'isOwner' => $isOwner,
            'canEdit' => $user?->can('update', $recipe) ?? false,
            'canSave' => $user?->can('save', $recipe) ?? false,
            'isSaved' => $user ? $recipe->savers()->whereKey($user->id)->exists() : false,
            'canReport' => $user?->can('report', $recipe) ?? false,
            'collections' => $user ? $user->recipeCollections()->withCount('recipes')->orderBy('name')->get() : collect(),
            'inCollections' => $user
                ? $recipe->collections()->where('collections.user_id', $user->id)->pluck('collections.id')->all()
                : [],
            'forkedFrom' => $forkedFrom,
            'planDays' => $days,
            'mealSlots' => MealSlot::cases(),
            'reportReasons' => ReportReason::cases(),
            'scaler' => [
                'base' => $recipe->servings,
                'items' => $recipe->ingredients->map(fn ($i) => [
                    'q' => $i->quantity,
                    'unit' => $i->unit,
                ])->all(),
            ],
            'timers' => $recipe->steps->whereNotNull('timer_seconds')->count(),
            'youtubeId' => $this->youtubeId($recipe->source_url),
        ])
            ->title($recipe->title)
            ->layout($user ? 'components.layouts.app' : 'components.layouts.public')
            ->layoutData($recipe->isListed() ? [
                'description' => Str::limit($recipe->description ?: 'Resep '.$recipe->title.' di '.config('app.name').'.', 155),
                'ogImage' => $recipe->coverUrl() ? url($recipe->coverUrl()) : null,
            ] : []);
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

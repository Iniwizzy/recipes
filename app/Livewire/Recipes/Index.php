<?php

namespace App\Livewire\Recipes;

use App\Enums\Category;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Resep saya')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'kategori', except: '')]
    public string $category = '';

    #[Url(as: 'favorit', except: false)]
    public bool $favorites = false;

    /** Show recipes bookmarked from the public catalog instead of the cook's own. */
    #[Url(as: 'disimpan', except: false)]
    public bool $saved = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function pickCategory(string $value): void
    {
        $this->category = Category::tryFrom($value)?->value ?? '';
        $this->resetPage();
    }

    public function showSaved(bool $saved): void
    {
        $this->saved = $saved;
        $this->favorites = false;
        $this->resetPage();
    }

    public function render()
    {
        $category = Category::tryFrom($this->category);
        $user = auth()->user();

        $query = $this->saved
            ? $user->savedRecipes()->published()->with('user:id,name,username')->withCount('savers')->orderByPivot('created_at', 'desc')
            : $user->recipes()->when($this->favorites, fn ($q) => $q->where('is_favorite', true))->latest();

        $recipes = $query
            ->search($this->search)
            ->when($category, fn ($q) => $q->where('category', $category))
            ->paginate(12);

        return view('livewire.recipes.index', [
            'recipes' => $recipes,
            'categories' => Category::cases(),
            'active' => $category,
            'savedCount' => $user->savedRecipes()->published()->count(),
        ]);
    }
}

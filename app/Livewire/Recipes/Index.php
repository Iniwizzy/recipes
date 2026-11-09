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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function pickCategory(string $value): void
    {
        $this->category = Category::tryFrom($value)?->value ?? '';
        $this->resetPage();
    }

    public function render()
    {
        $category = Category::tryFrom($this->category);

        $recipes = auth()->user()->recipes()
            ->search($this->search)
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($this->favorites, fn ($q) => $q->where('is_favorite', true))
            ->latest()
            ->paginate(12);

        return view('livewire.recipes.index', [
            'recipes' => $recipes,
            'categories' => Category::cases(),
            'active' => $category,
        ]);
    }
}

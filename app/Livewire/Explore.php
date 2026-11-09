<?php

namespace App\Livewire;

use App\Enums\Category;
use App\Models\Recipe;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The public recipe catalog. Open to guests; signed-in cooks see it inside the app.
 */
class Explore extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** Comma-separated ingredients the cook already has ("telur, nasi"). */
    #[Url(as: 'bahan', except: '')]
    public string $have = '';

    #[Url(as: 'kategori', except: '')]
    public string $category = '';

    #[Url(as: 'waktu', except: 0)]
    public int $maxMinutes = 0;

    #[Url(as: 'urut', except: 'populer')]
    public string $sort = 'populer';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'have', 'maxMinutes', 'sort'], true)) {
            $this->resetPage();
        }
    }

    public function pickCategory(string $value): void
    {
        $this->category = Category::tryFrom($value)?->value ?? '';
        $this->resetPage();
    }

    /**
     * @return array<int, string>
     */
    private function haveTerms(): array
    {
        return collect(explode(',', $this->have))
            ->map(fn ($term) => trim($term))
            ->filter(fn ($term) => mb_strlen($term) >= 2)
            ->unique()
            ->take(5)
            ->values()
            ->all();
    }

    public function render()
    {
        $category = Category::tryFrom($this->category);
        $totalMinutes = 'COALESCE(prep_minutes, 0) + COALESCE(cook_minutes, 0)';

        $query = Recipe::published()
            ->with('user:id,name,username')
            ->withCount('savers')
            ->search($this->search)
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($this->maxMinutes > 0, fn ($q) => $q->whereRaw("{$totalMinutes} BETWEEN 1 AND ?", [$this->maxMinutes]));

        foreach ($this->haveTerms() as $term) {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
            $query->whereHas('ingredients', fn ($q) => $q->where('name', 'like', $like));
        }

        match ($this->sort) {
            'terbaru' => $query->latest('published_at'),
            'tercepat' => $query->orderByRaw("{$totalMinutes} = 0")->orderByRaw($totalMinutes),
            default => $query->orderByDesc('savers_count')->orderByDesc('cooked_count')->latest('published_at'),
        };

        return view('livewire.explore', [
            'recipes' => $query->paginate(24),
            'categories' => Category::cases(),
            'active' => $category,
            'haveTerms' => $this->haveTerms(),
            'total' => Recipe::published()->count(),
        ])
            ->layout(auth()->check() ? 'components.layouts.app' : 'components.layouts.public')
            ->title('Jelajahi resep')
            ->layoutData(['description' => 'Cari resep masakan rumahan dari komunitas Buku Resep, lalu simpan, salin, atau masukkan ke rencana menu.']);
    }
}

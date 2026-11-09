<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * A cook's public page: their recipes in the catalog. Open to guests.
 */
class Profile extends Component
{
    use WithPagination;

    public User $user;

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function render()
    {
        $recipes = $this->user->recipes()->published()
            ->with('user:id,name,username')
            ->withCount('savers')
            ->latest('published_at')
            ->paginate(24);

        return view('livewire.profile', [
            'recipes' => $recipes,
            'isMe' => auth()->id() === $this->user->id,
        ])
            ->title($this->user->name)
            ->layout(auth()->check() ? 'components.layouts.app' : 'components.layouts.public')
            ->layoutData(['description' => "Resep dari {$this->user->name} di ".config('app.name').'.']);
    }
}

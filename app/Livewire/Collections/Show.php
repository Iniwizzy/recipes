<?php

namespace App\Livewire\Collections;

use App\Models\RecipeCollection;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Show extends Component
{
    public RecipeCollection $collection;

    public string $name = '';

    public bool $renaming = false;

    public function mount(RecipeCollection $collection): void
    {
        // Someone else's collection simply does not exist for you.
        abort_unless($collection->user_id === auth()->id(), 404);
        $this->collection = $collection;
        $this->name = $collection->name;
    }

    public function rename(): void
    {
        $this->name = trim($this->name);
        $this->validate(
            ['name' => ['required', 'string', 'max:60', Rule::unique('collections', 'name')->where('user_id', auth()->id())->ignore($this->collection->id)]],
            [
                'name.required' => 'Nama koleksi tidak boleh kosong.',
                'name.unique' => 'Kamu sudah punya koleksi dengan nama ini.',
            ]
        );

        $this->collection->update(['name' => $this->name]);
        $this->renaming = false;
    }

    public function removeRecipe(int $recipeId): void
    {
        $this->collection->recipes()->detach($recipeId);
    }

    public function delete()
    {
        $name = $this->collection->name;
        $this->collection->delete();
        session()->flash('status', "Koleksi \"{$name}\" dihapus. Resep-resepnya tetap ada.");

        return $this->redirectRoute('collections.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.collections.show', [
            'recipes' => $this->collection->recipes()->orderByPivot('created_at', 'desc')->get(),
        ])->title($this->collection->name);
    }
}

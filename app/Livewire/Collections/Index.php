<?php

namespace App\Livewire\Collections;

use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Koleksi')]
class Index extends Component
{
    public string $name = '';

    public function create()
    {
        $this->name = trim($this->name);
        $this->validate(
            ['name' => ['required', 'string', 'max:60', Rule::unique('collections', 'name')->where('user_id', auth()->id())]],
            [
                'name.required' => 'Beri nama koleksinya dulu.',
                'name.unique' => 'Kamu sudah punya koleksi dengan nama ini.',
            ]
        );

        $collection = auth()->user()->recipeCollections()->create(['name' => $this->name]);

        return $this->redirectRoute('collections.show', $collection, navigate: true);
    }

    public function render()
    {
        return view('livewire.collections.index', [
            'collections' => auth()->user()->recipeCollections()
                ->withCount('recipes')
                ->with(['recipes' => fn ($q) => $q->orderByPivot('created_at', 'desc')])
                ->orderBy('name')
                ->get(),
        ]);
    }
}

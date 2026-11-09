<?php

namespace App\Livewire;

use App\Support\ShoppingList as ShoppingListService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Daftar belanja')]
class ShoppingList extends Component
{
    public string $line = '';

    public function add(): void
    {
        $this->validate(
            ['line' => ['required', 'string', 'max:150']],
            ['line.required' => 'Tulis dulu barang yang mau dibeli.']
        );

        $row = ShoppingListService::parseLine($this->line);
        if ($row['name'] === '') {
            $this->addError('line', 'Nama barangnya belum ada. Contoh: 2 butir telur.');

            return;
        }

        ShoppingListService::add(auth()->user(), [$row]);
        $this->reset('line');
    }

    public function toggle(int $id): void
    {
        $item = auth()->user()->shoppingListItems()->findOrFail($id);
        $item->update(['checked_at' => $item->checked_at ? null : now()]);
    }

    public function remove(int $id): void
    {
        auth()->user()->shoppingListItems()->findOrFail($id)->delete();
    }

    public function clearChecked(): void
    {
        auth()->user()->shoppingListItems()->whereNotNull('checked_at')->delete();
    }

    public function render()
    {
        $items = auth()->user()->shoppingListItems()->get();
        $open = $items->whereNull('checked_at')->sortBy('created_at')->values();
        $done = $items->whereNotNull('checked_at')->sortByDesc('checked_at')->values();

        $text = "Daftar belanja\n".$open->map(fn ($item) => '- '.$item->line())->implode("\n");

        return view('livewire.shopping-list', [
            'open' => $open,
            'done' => $done,
            'shareText' => $text,
            'whatsappUrl' => 'https://wa.me/?text='.rawurlencode($text),
        ]);
    }
}

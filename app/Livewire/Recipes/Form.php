<?php

namespace App\Livewire\Recipes;

use App\Enums\Category;
use App\Enums\Difficulty;
use App\Models\Recipe;
use App\Support\Quantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public ?Recipe $recipe = null;

    public string $title = '';

    public string $description = '';

    public string $category = 'sarapan';

    public string $difficulty = 'mudah';

    public $servings = 2;

    public $prep_minutes = '';

    public $cook_minutes = '';

    public string $source_url = '';

    public string $notes = '';

    public bool $is_public = false;

    /** @var array<int, array{quantity: string, unit: string, name: string, note: string}> */
    public array $ingredients = [];

    /** @var array<int, array{instruction: string, timer: string}> */
    public array $steps = [];

    public $cover = null;

    public bool $removeCover = false;

    public function mount(?Recipe $recipe = null): void
    {
        if ($recipe?->exists) {
            $this->authorize('update', $recipe);
            $this->recipe = $recipe;
            $this->title = $recipe->title;
            $this->servings = $recipe->servings;
            $this->is_public = (bool) $recipe->is_public;
            $this->description = (string) $recipe->description;
            $this->category = $recipe->category->value;
            $this->difficulty = $recipe->difficulty->value;
            $this->prep_minutes = (string) ($recipe->prep_minutes ?? '');
            $this->cook_minutes = (string) ($recipe->cook_minutes ?? '');
            $this->source_url = (string) $recipe->source_url;
            $this->notes = (string) $recipe->notes;
            $this->ingredients = $recipe->ingredients->map(fn ($i) => [
                'quantity' => $i->quantity === null ? '' : str_replace('.', ',', (string) round($i->quantity, 2)),
                'unit' => (string) $i->unit,
                'name' => $i->name,
                'note' => (string) $i->note,
            ])->all();
            $this->steps = $recipe->steps->map(fn ($s) => [
                'instruction' => $s->instruction,
                'timer' => $s->timerMinutes() ? (string) $s->timerMinutes() : '',
            ])->all();
        }

        if ($this->ingredients === []) {
            $this->ingredients = [$this->blankIngredient(), $this->blankIngredient(), $this->blankIngredient()];
        }
        if ($this->steps === []) {
            $this->steps = [$this->blankStep(), $this->blankStep()];
        }
    }

    public function addIngredient(): void
    {
        $this->ingredients[] = $this->blankIngredient();
    }

    public function removeIngredient(int $index): void
    {
        unset($this->ingredients[$index]);
        $this->ingredients = array_values($this->ingredients);
    }

    public function addStep(): void
    {
        $this->steps[] = $this->blankStep();
    }

    public function removeStep(int $index): void
    {
        unset($this->steps[$index]);
        $this->steps = array_values($this->steps);
    }

    public function moveStep(int $index, int $direction): void
    {
        $target = $index + $direction;
        if (! isset($this->steps[$index], $this->steps[$target])) {
            return;
        }
        [$this->steps[$index], $this->steps[$target]] = [$this->steps[$target], $this->steps[$index]];
    }

    public function save()
    {
        // Rows left completely empty are ignored instead of failing validation.
        $this->ingredients = array_values(array_filter(
            $this->ingredients,
            fn ($row) => trim($row['name'] ?? '') !== '' || trim($row['quantity'] ?? '') !== '' || trim($row['note'] ?? '') !== ''
        ));
        $this->steps = array_values(array_filter(
            $this->steps,
            fn ($row) => trim($row['instruction'] ?? '') !== '' || trim((string) ($row['timer'] ?? '')) !== ''
        ));

        $data = $this->validate();

        $recipe = DB::transaction(function () use ($data) {
            $recipe = $this->recipe ?? new Recipe(['user_id' => auth()->id()]);
            $recipe->user_id ??= auth()->id();
            $recipe->fill([
                'title' => trim($data['title']),
                'description' => $data['description'] ?: null,
                'category' => $data['category'],
                'difficulty' => $data['difficulty'],
                'servings' => (int) $data['servings'],
                'prep_minutes' => $data['prep_minutes'] === '' ? null : (int) $data['prep_minutes'],
                'cook_minutes' => $data['cook_minutes'] === '' ? null : (int) $data['cook_minutes'],
                'source_url' => $data['source_url'] ?: null,
                'notes' => $data['notes'] ?: null,
                'is_public' => (bool) $data['is_public'],
            ]);

            if ($this->removeCover && $recipe->cover_path) {
                Storage::disk('public')->delete($recipe->cover_path);
                $recipe->cover_path = null;
            }
            if ($this->cover) {
                if ($recipe->cover_path) {
                    Storage::disk('public')->delete($recipe->cover_path);
                }
                $recipe->cover_path = $this->cover->store('covers', 'public');
            }

            $recipe->save();

            $recipe->ingredients()->delete();
            foreach ($data['ingredients'] as $i => $row) {
                $recipe->ingredients()->create([
                    'position' => $i,
                    'quantity' => Quantity::parse($row['quantity'] ?? ''),
                    'unit' => trim($row['unit'] ?? '') ?: null,
                    'name' => trim($row['name']),
                    'note' => trim($row['note'] ?? '') ?: null,
                ]);
            }

            $recipe->steps()->delete();
            foreach ($data['steps'] as $i => $row) {
                $timer = trim((string) ($row['timer'] ?? ''));
                $recipe->steps()->create([
                    'position' => $i,
                    'instruction' => trim($row['instruction']),
                    'timer_seconds' => $timer === '' || (int) $timer === 0 ? null : (int) $timer * 60,
                ]);
            }

            return $recipe;
        });

        session()->flash('status', $this->recipe ? 'Perubahan resep disimpan.' : 'Resep baru disimpan.');

        return $this->redirectRoute('recipes.show', $recipe, navigate: true);
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['required', Rule::enum(Category::class)],
            'difficulty' => ['required', Rule::enum(Difficulty::class)],
            'servings' => ['required', 'integer', 'min:1', 'max:50'],
            'prep_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'cook_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_public' => ['boolean'],
            'cover' => ['nullable', 'image', 'max:4096'],
            'ingredients' => ['required', 'array', 'min:1', 'max:60'],
            'ingredients.*.quantity' => ['nullable', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (trim((string) $value) !== '' && Quantity::parse($value) === null) {
                    $fail('Jumlah tidak terbaca. Contoh: 2, 1,5, atau 1/2.');
                }
            }],
            'ingredients.*.unit' => ['nullable', 'string', 'max:30'],
            'ingredients.*.name' => ['required', 'string', 'max:120'],
            'ingredients.*.note' => ['nullable', 'string', 'max:120'],
            'steps' => ['required', 'array', 'min:1', 'max:40'],
            'steps.*.instruction' => ['required', 'string', 'max:2000'],
            'steps.*.timer' => ['nullable', 'integer', 'min:0', 'max:600'],
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'Nama resep wajib diisi.',
            'ingredients.required' => 'Tambahkan minimal satu bahan.',
            'ingredients.min' => 'Tambahkan minimal satu bahan.',
            'ingredients.*.name.required' => 'Nama bahan wajib diisi.',
            'steps.required' => 'Tambahkan minimal satu langkah.',
            'steps.min' => 'Tambahkan minimal satu langkah.',
            'steps.*.instruction.required' => 'Isi langkahnya atau hapus baris ini.',
            'steps.*.timer.integer' => 'Timer diisi angka menit.',
            'source_url.url' => 'Link harus diawali http:// atau https://',
            'cover.image' => 'Sampul harus berupa gambar (JPG atau PNG).',
            'cover.max' => 'Ukuran gambar maksimal 4 MB.',
        ];
    }

    public function render()
    {
        return view('livewire.recipes.form', [
            'categories' => Category::cases(),
            'difficulties' => Difficulty::cases(),
            'units' => ['piring', 'butir', 'siung', 'buah', 'batang', 'lembar', 'ikat', 'sdm', 'sdt', 'gram', 'kg', 'ml', 'liter', 'gelas', 'cm', 'potong', 'ekor', 'secukupnya'],
        ])->title($this->recipe ? 'Ubah resep' : 'Tulis resep');
    }

    private function blankIngredient(): array
    {
        return ['quantity' => '', 'unit' => '', 'name' => '', 'note' => ''];
    }

    private function blankStep(): array
    {
        return ['instruction' => '', 'timer' => ''];
    }
}

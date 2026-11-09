<?php

namespace App\Livewire;

use App\Enums\MealSlot;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Support\ShoppingList;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Rencana menu')]
class MealPlanner extends Component
{
    /** Any date inside the week being shown (YYYY-MM-DD); empty means this week. */
    #[Url(as: 'minggu', except: '')]
    public string $week = '';

    public bool $pickerOpen = false;

    public string $pickerDate = '';

    public string $pickerSlot = '';

    public string $search = '';

    public int $pickerServings = 2;

    public ?string $message = null;

    public function weekStart(): Carbon
    {
        try {
            $date = $this->week !== '' ? Carbon::parse($this->week) : now();
        } catch (\Throwable) {
            $date = now();
        }

        return $date->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
    }

    public function shiftWeek(int $weeks): void
    {
        $start = $this->weekStart()->addWeeks($weeks);
        $this->week = $start->isSameDay(now()->startOfWeek(Carbon::MONDAY)) ? '' : $start->toDateString();
        $this->message = null;
    }

    public function thisWeek(): void
    {
        $this->week = '';
        $this->message = null;
    }

    public function openPicker(string $date, string $slot): void
    {
        $this->pickerDate = Carbon::parse($date)->toDateString();
        $this->pickerSlot = MealSlot::from($slot)->value;
        $this->search = '';
        $this->pickerServings = auth()->user()->mealPlans()->latest('id')->value('servings') ?? 2;
        $this->pickerOpen = true;
    }

    public function closePicker(): void
    {
        $this->pickerOpen = false;
    }

    public function adjustPickerServings(int $delta): void
    {
        $this->pickerServings = max(1, min(50, $this->pickerServings + $delta));
    }

    public function pick(int $recipeId): void
    {
        $recipe = Recipe::findOrFail($recipeId);
        $this->authorize('view', $recipe);

        auth()->user()->mealPlans()->create([
            'recipe_id' => $recipe->id,
            'date' => Carbon::parse($this->pickerDate)->toDateString(),
            'slot' => MealSlot::from($this->pickerSlot),
            'servings' => max(1, min(50, $this->pickerServings)),
        ]);

        $this->pickerOpen = false;
        $this->message = null;
    }

    public function remove(int $id): void
    {
        auth()->user()->mealPlans()->findOrFail($id)->delete();
        $this->message = null;
    }

    public function adjustServings(int $id, int $delta): void
    {
        $plan = auth()->user()->mealPlans()->findOrFail($id);
        $plan->update(['servings' => max(1, min(50, $plan->servings + $delta))]);
    }

    /**
     * Puts every ingredient of the menus still ahead this week (today included)
     * on the shopping list, scaled to each menu's portions and merged.
     */
    public function addToShoppingList(): void
    {
        [$from, $to] = $this->upcomingRange();
        $plans = auth()->user()->mealPlans()->with('recipe')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get();

        $count = 0;
        foreach ($plans as $plan) {
            $count += ShoppingList::add(
                auth()->user(),
                ShoppingList::rowsFromRecipe($plan->recipe, $plan->servings),
                $plan->recipe->title
            );
        }

        $this->message = $count
            ? "{$count} bahan dari {$plans->count()} menu masuk daftar belanja."
            : 'Belum ada menu dari hari ini sampai akhir minggu.';
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function upcomingRange(): array
    {
        $start = $this->weekStart();
        $end = $start->copy()->addDays(6);

        return [$start->max(now()->startOfDay()), $end];
    }

    public function render()
    {
        $start = $this->weekStart();
        $end = $start->copy()->addDays(6);
        $today = now()->startOfDay();

        $plans = auth()->user()->mealPlans()
            ->with('recipe')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('id')
            ->get();

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);
            $slots = [];
            foreach (MealSlot::cases() as $slot) {
                $slots[] = [
                    'slot' => $slot,
                    'plans' => $plans->filter(fn (MealPlan $p) => $p->date->isSameDay($date) && $p->slot === $slot)->values(),
                ];
            }
            $days[] = [
                'date' => $date,
                'today' => $date->isSameDay($today),
                'past' => $date->lt($today),
                'slots' => $slots,
                'count' => $plans->filter(fn (MealPlan $p) => $p->date->isSameDay($date))->count(),
            ];
        }

        [$from] = $this->upcomingRange();
        $upcoming = $plans->filter(fn (MealPlan $p) => $p->date->gte($from))->count();

        $recipes = $this->pickerOpen
            ? auth()->user()->recipes()->search($this->search)
                ->orderByDesc('is_favorite')->orderBy('title')->limit(30)->get()
            : collect();

        return view('livewire.meal-planner', [
            'start' => $start,
            'end' => $end,
            'isThisWeek' => $start->isSameDay($today->copy()->startOfWeek(Carbon::MONDAY)),
            'days' => $days,
            'total' => $plans->count(),
            'upcoming' => $upcoming,
            'recipes' => $recipes,
            'pickerSlotLabel' => $this->pickerSlot ? MealSlot::from($this->pickerSlot)->label() : '',
            'pickerDateLabel' => $this->pickerDate ? Carbon::parse($this->pickerDate)->translatedFormat('l, j M') : '',
        ]);
    }
}

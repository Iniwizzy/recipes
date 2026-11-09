<?php

namespace App\Livewire;

use App\Enums\Category;
use App\Enums\MealSlot;
use App\Models\CookLog;
use App\Models\MealPlan;
use App\Models\Recipe;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Beranda')]
class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        $now = now();
        $today = $now->copy()->startOfDay();

        $recipes = $user->recipes()->get();
        $logs = $user->cookLogs()
            ->with('recipe:id,title,slug,category')
            ->where('cooked_at', '>=', $today->copy()->subDays(29))
            ->orderBy('cooked_at')
            ->get();

        $week = $logs->where('cooked_at', '>=', $today->copy()->subDays(6));
        $prevWeek = $logs->whereBetween('cooked_at', [$today->copy()->subDays(13), $today->copy()->subDays(7)->endOfDay()]);

        $plans = $user->mealPlans()->with('recipe')
            ->whereBetween('date', [$today->copy()->startOfWeek(Carbon::MONDAY)->toDateString(), $today->copy()->endOfWeek(Carbon::SUNDAY)->toDateString()])
            ->orderBy('id')
            ->get();
        $slotOrder = array_map(fn (MealSlot $s) => $s->value, MealSlot::cases());
        $todayPlans = $plans->filter(fn (MealPlan $p) => $p->date->isSameDay($today) && $p->recipe)
            ->sortBy(fn (MealPlan $p) => array_search($p->slot->value, $slotOrder, true))
            ->values();

        return view('livewire.dashboard', [
            'greeting' => $this->greeting($now),
            'firstName' => strtok($user->name, ' '),
            'kpis' => $this->kpis($recipes, $logs, $week, $prevWeek, $today),
            'chart' => $this->weekChart($logs, $plans, $today),
            'todayPlans' => $todayPlans,
            'cookedToday' => $logs->filter(fn (CookLog $l) => $l->cooked_at->isSameDay($today))->pluck('recipe_id')->all(),
            'stale' => $this->staleRecipes($recipes, $today),
            'top' => $this->topRecipes($logs),
            'donut' => $this->donut($recipes),
            'activity' => $this->activity($user),
            'weekCount' => $week->count(),
            'weekHours' => $this->hours($week->sum('minutes')),
        ]);
    }

    private function greeting(Carbon $now): string
    {
        return match (true) {
            $now->hour < 11 => 'Selamat pagi',
            $now->hour < 15 => 'Selamat siang',
            $now->hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };
    }

    private function hours(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.' menit';
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return $m ? "{$h} jam {$m} menit" : "{$h} jam";
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function kpis(Collection $recipes, Collection $logs, Collection $week, Collection $prevWeek, Carbon $today): array
    {
        $addedPerWeek = [];
        for ($w = 23; $w >= 0; $w--) {
            $from = $today->copy()->subWeeks($w + 1)->addDay();
            $to = $today->copy()->subWeeks($w)->endOfDay();
            $addedPerWeek[] = $recipes->whereBetween('created_at', [$from, $to])->count();
        }

        $cooksPerDay = [];
        $minutesPerDay = [];
        for ($d = 23; $d >= 0; $d--) {
            $day = $logs->filter(fn (CookLog $l) => $l->cooked_at->isSameDay($today->copy()->subDays($d)));
            $cooksPerDay[] = $day->count();
            $minutesPerDay[] = (int) $day->sum('minutes');
        }

        $weekMinutes = (int) $week->sum('minutes');
        $prevMinutes = (int) $prevWeek->sum('minutes');
        $favorites = $recipes->where('is_favorite', true);
        $mostCooked = $recipes->sortByDesc('cooked_count')->first();

        return [
            [
                'label' => 'Resep tersimpan',
                'icon' => 'book-open',
                'tint' => 'tint-1',
                'value' => $recipes->count(),
                'unit' => null,
                'delta' => $this->delta($recipes->where('created_at', '>=', $today->copy()->subDays(6))->count(), 0, 'minggu ini'),
                'dots' => $this->dots($addedPerWeek),
                'dotColor' => 'bg-s1',
                'aria' => 'Resep baru per minggu selama 24 minggu terakhir',
            ],
            [
                'label' => 'Dimasak 7 hari',
                'icon' => 'fire',
                'tint' => 'tint-2',
                'value' => $week->count(),
                'unit' => 'menu',
                'delta' => $this->delta($week->count(), $prevWeek->count()),
                'dots' => $this->dots($cooksPerDay),
                'dotColor' => 'bg-s2',
                'aria' => 'Menu dimasak per hari selama 24 hari terakhir',
            ],
            [
                'label' => 'Waktu di dapur',
                'icon' => 'clock',
                'tint' => 'tint-3',
                'value' => str_replace('.', ',', (string) round($weekMinutes / 60, 1)),
                'unit' => 'jam',
                'delta' => $this->delta($weekMinutes, $prevMinutes, null, true),
                'dots' => $this->dots($minutesPerDay),
                'dotColor' => 'bg-[#6250d6] dark:bg-[#9085e9]',
                'aria' => 'Menit memasak per hari selama 24 hari terakhir',
            ],
            [
                'label' => 'Resep favorit',
                'icon' => 'heart',
                'tint' => 'tint-4',
                'value' => $favorites->count(),
                'unit' => null,
                'delta' => null,
                'dots' => null,
                'note' => $mostCooked && $mostCooked->cooked_count
                    ? 'Paling sering dimasak: '.$mostCooked->title.' ('.$mostCooked->cooked_count.'×)'
                    : 'Tandai resep dengan ikon hati supaya muncul di sini.',
            ],
        ];
    }

    /**
     * @return array{text: string, up: bool}|null
     */
    private function delta(int $current, int $previous, ?string $suffix = null, bool $percent = false): ?array
    {
        if ($suffix !== null) {
            return $current > 0 ? ['text' => '+'.$current.' '.$suffix, 'up' => true] : null;
        }
        if ($previous === 0) {
            return null;
        }
        $diff = $current - $previous;
        if ($diff === 0) {
            return null;
        }
        $text = $percent
            ? round(abs($diff) / $previous * 100).'%'
            : (string) abs($diff);

        return ['text' => ($diff > 0 ? '+' : '−').$text, 'up' => $diff > 0];
    }

    /**
     * 24 columns x 5 rows; a column fills from the bottom in proportion to its value.
     *
     * @param  array<int, int>  $values
     * @return array<int, bool>
     */
    private function dots(array $values): array
    {
        $max = max($values) ?: 1;
        $cells = [];
        foreach ($values as $v) {
            $filled = $v > 0 ? max(1, (int) round($v / $max * 5)) : 0;
            for ($row = 0; $row < 5; $row++) {
                $cells[] = $row >= 5 - $filled;
            }
        }

        return $cells;
    }

    /**
     * This week (Monday to Sunday): minutes actually cooked, stacked per category,
     * plus a gray "planned" segment for menus from the meal plan that are still
     * ahead (today included, minus recipes already cooked today).
     *
     * @return array<string, mixed>
     */
    private function weekChart(Collection $logs, Collection $plans, Carbon $today): array
    {
        $start = $today->copy()->startOfWeek(Carbon::MONDAY);
        $days = [];
        $maxTotal = 0;
        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);
            $dayLogs = $logs->filter(fn (CookLog $l) => $l->cooked_at->isSameDay($date))->values();

            $byCategory = [];
            foreach (Category::cases() as $category) {
                $minutes = (int) $dayLogs->filter(fn (CookLog $l) => $l->recipe?->category === $category)->sum('minutes');
                if ($minutes > 0) {
                    $byCategory[] = ['category' => $category, 'minutes' => $minutes];
                }
            }

            $cookedIds = $dayLogs->pluck('recipe_id')->all();
            $dayPlans = $date->gte($today)
                ? $plans->filter(fn (MealPlan $p) => $p->date->isSameDay($date) && ! in_array($p->recipe_id, $cookedIds, true))->values()
                : collect();
            // Recipes without times count as 30 minutes so the plan still shows up.
            $planMinutes = fn (MealPlan $p) => $p->recipe?->totalMinutes() ?? 30;

            $total = array_sum(array_column($byCategory, 'minutes'));
            $planned = (int) $dayPlans->sum($planMinutes);
            $maxTotal = max($maxTotal, $total + $planned);

            $days[] = [
                'short' => $date->translatedFormat('D j'),
                'full' => $date->translatedFormat('l, j M'),
                'today' => $date->isSameDay($today),
                'total' => $total,
                'planned' => $planned,
                'parts' => $byCategory,
                'logs' => $dayLogs->map(fn (CookLog $l) => [
                    'title' => $l->recipe?->title ?? 'Resep dihapus',
                    'swatch' => $l->recipe?->category->swatch() ?? 'bg-s1',
                    'category' => $l->recipe?->category->label() ?? '',
                    'minutes' => (int) $l->minutes,
                ])->all(),
                'plans' => $dayPlans->map(fn (MealPlan $p) => [
                    'title' => $p->recipe?->title ?? '',
                    'slot' => $p->slot->label(),
                    'minutes' => $planMinutes($p),
                ])->all(),
            ];
        }

        $top = max(45, (int) ceil($maxTotal / 45) * 45);
        $plot = 200;
        foreach ($days as &$day) {
            $segments = [];
            foreach ($day['parts'] as $i => $part) {
                $h = (int) round($part['minutes'] / $top * $plot) - ($i > 0 ? 2 : 0);
                $segments[] = ['swatch' => $part['category']->swatch(), 'height' => max($h, 2)];
            }
            if ($day['planned'] > 0) {
                $h = (int) round($day['planned'] / $top * $plot) - ($segments ? 2 : 0);
                $segments[] = ['swatch' => 'bg-plan', 'height' => max($h, 2)];
            }
            $day['segments'] = $segments;
        }
        unset($day);

        $step = intdiv($top, 3);

        return [
            'days' => $days,
            'ticks' => [
                ['value' => $top, 'bottom' => 28 + $plot],
                ['value' => $step * 2, 'bottom' => 28 + (int) round($plot * 2 / 3)],
                ['value' => $step, 'bottom' => 28 + (int) round($plot / 3)],
                ['value' => 0, 'bottom' => 28],
            ],
            'empty' => $maxTotal === 0,
        ];
    }

    private function staleRecipes(Collection $recipes, Carbon $today): Collection
    {
        return $recipes
            ->sortBy(fn (Recipe $r) => $r->last_cooked_at?->timestamp ?? 0)
            ->take(4)
            ->map(function (Recipe $r) use ($today) {
                $days = $r->last_cooked_at ? (int) $r->last_cooked_at->copy()->startOfDay()->diffInDays($today) : null;

                return [
                    'recipe' => $r,
                    'label' => $days === null ? 'Belum pernah' : ($days === 0 ? 'Hari ini' : $days.' hari'),
                    'status' => $days === null ? 'st-warn' : ($days >= 7 ? 'st-info' : 'st-ok'),
                    'sub' => $days === null
                        ? $r->category->label().', belum pernah dimasak'
                        : $r->category->label().', terakhir '.$r->last_cooked_at->translatedFormat('j M'),
                ];
            })
            ->values();
    }

    private function topRecipes(Collection $logs): Collection
    {
        $counts = $logs->groupBy('recipe_id')->map->count()->sortDesc()->take(4);
        $max = $counts->max() ?: 1;

        return $counts->map(function (int $count, int $recipeId) use ($logs, $max) {
            $recipe = $logs->firstWhere('recipe_id', $recipeId)->recipe;

            return [
                'recipe' => $recipe,
                'count' => $count,
                'width' => round($count / $max * 100),
            ];
        })->filter(fn ($row) => $row['recipe'] !== null)->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function donut(Collection $recipes): array
    {
        $total = $recipes->count();
        $rows = [];
        foreach (Category::cases() as $category) {
            $count = $recipes->where('category', $category)->count();
            $rows[] = [
                'category' => $category,
                'count' => $count,
                'percent' => $total ? (int) round($count / $total * 100) : 0,
            ];
        }

        $stops = [];
        $angle = 0.0;
        $nonZero = array_values(array_filter($rows, fn ($r) => $r['count'] > 0));
        $gap = count($nonZero) > 1 ? 1.0 : 0.0;
        foreach ($nonZero as $row) {
            $sweep = $row['count'] / max($total, 1) * 360;
            $start = $angle + $gap;
            $end = $angle + $sweep - $gap;
            $color = 'var(--color-s'.$row['category']->slot().')';
            $stops[] = 'var(--color-card) '.round($angle, 2).'deg '.round($start, 2).'deg';
            $stops[] = $color.' '.round($start, 2).'deg '.round($end, 2).'deg';
            $stops[] = 'var(--color-card) '.round($end, 2).'deg '.round($angle + $sweep, 2).'deg';
            $angle += $sweep;
        }

        return [
            'total' => $total,
            'rows' => $rows,
            'gradient' => $stops ? 'conic-gradient('.implode(', ', $stops).')' : 'var(--color-dot)',
            'aria' => collect($rows)->map(fn ($r) => $r['category']->label().' '.$r['count'])->implode(', ').' resep',
        ];
    }

    private function activity($user): Collection
    {
        $cooked = $user->cookLogs()->with('recipe:id,title,slug,category')->latest('cooked_at')->take(6)->get()
            ->filter(fn (CookLog $l) => $l->recipe)
            ->map(fn (CookLog $l) => [
                'at' => $l->cooked_at,
                'verb' => 'selesai dimasak',
                'recipe' => $l->recipe,
                'meta' => ($l->minutes ? $l->minutes.' menit' : '').($l->servings ? ', '.$l->servings.' porsi' : ''),
                'tint' => 'tint-2',
                'icon' => 'fire',
            ]);

        $added = $user->recipes()->latest()->take(4)->get()->map(fn (Recipe $r) => [
            'at' => $r->created_at,
            'verb' => 'ditambahkan ke koleksi',
            'recipe' => $r,
            'meta' => $r->category->label(),
            'tint' => 'tint-1',
            'icon' => 'plus',
        ]);

        return $cooked->concat($added)->sortByDesc('at')->take(5)->values();
    }
}

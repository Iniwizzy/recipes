@props(['recipe', 'byline' => false])

{{--
    Recipe card used on the recipe list, inside a collection and in the public catalog.
    byline: show the cook's name and how many people saved it (catalog), instead of the owner's favorite mark.
    $slot adds controls over the cover.
--}}
<div {{ $attributes->class('group relative flex flex-col overflow-hidden rounded-2xl border border-line bg-card shadow-[0_1px_2px_rgba(16,24,40,.05)] transition hover:border-brand dark:shadow-none') }}>
    <a href="{{ route('recipes.show', $recipe) }}" wire:navigate class="flex flex-1 flex-col">
        <x-recipe-cover :recipe="$recipe" thumb class="h-[108px] lg:h-36">
            @if (! $byline && $recipe->is_favorite)
                <span class="absolute top-2 left-2 flex size-7 items-center justify-center rounded-full bg-card text-brand">
                    <flux:icon.heart variant="micro" class="fill-current" />
                    <span class="sr-only">Favorit</span>
                </span>
            @endif
        </x-recipe-cover>
        <span class="flex flex-col gap-1.5 p-3 lg:p-4">
            <b class="leading-snug font-semibold group-hover:text-brand">{{ $recipe->title }}</b>
            <span class="flex items-center gap-1.5 text-xs text-muted">
                <flux:icon.clock variant="micro" />
                {{ $recipe->totalMinutes() ? $recipe->totalMinutes().' mnt · ' : '' }}{{ $recipe->difficulty->label() }}
            </span>
            @if ($byline)
                <span class="flex items-center justify-between gap-2 text-xs text-muted">
                    <span class="truncate">oleh {{ $recipe->user?->name }}</span>
                    @if ($recipe->savers_count)
                        <span class="flex shrink-0 items-center gap-1" title="Disimpan {{ $recipe->savers_count }} orang">
                            <flux:icon.bookmark variant="micro" />{{ $recipe->savers_count }}
                        </span>
                    @endif
                </span>
            @else
                <span class="hidden text-xs text-muted sm:block">{{ $recipe->category->label() }}{{ $recipe->cooked_count ? ', dimasak '.$recipe->cooked_count.'×' : '' }}</span>
            @endif
        </span>
    </a>
    {{ $slot }}
</div>

@props(['href', 'icon', 'current' => false, 'count' => null])

<a href="{{ $href }}" wire:navigate
    @if ($current) aria-current="page" @endif
    {{ $attributes->class([
        'flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm transition',
        'bg-card font-semibold text-brand shadow-[0_1px_3px_rgba(16,24,40,.08)]' => $current,
        'font-medium text-ink-2 hover:bg-hover hover:text-ink' => ! $current,
    ]) }}>
    <flux:icon :icon="$icon" variant="outline" class="size-5" />
    <span class="flex-1">{{ $slot }}</span>
    @if ($count !== null)
        <span class="text-xs font-semibold text-brand">{{ $count }}</span>
    @endif
</a>

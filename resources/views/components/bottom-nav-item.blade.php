@props(['href', 'icon', 'current' => false])

<a href="{{ $href }}" wire:navigate
    @if ($current) aria-current="page" @endif
    {{ $attributes->class([
        'flex min-h-[52px] flex-col items-center justify-center gap-1 text-[11px]',
        'font-semibold text-brand' => $current,
        'font-medium text-muted' => ! $current,
    ]) }}>
    <flux:icon :icon="$icon" variant="outline" class="size-[22px]" />
    {{ $slot }}
</a>

@props(['href', 'icon', 'current' => false, 'badge' => null])

<a href="{{ $href }}" wire:navigate
    @if ($current) aria-current="page" @endif
    {{ $attributes->class([
        'relative flex min-h-[52px] flex-col items-center justify-center gap-1 text-[11px]',
        'font-semibold text-brand' => $current,
        'font-medium text-muted' => ! $current,
    ]) }}>
    <flux:icon :icon="$icon" variant="outline" class="size-[22px]" />
    {{ $slot }}
    @if ($badge)
        <span class="absolute top-0.5 left-1/2 ml-1.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-brand px-1 text-[10px] font-bold text-on-brand">
            {{ $badge > 99 ? '99+' : $badge }}<span class="sr-only"> barang belum dibeli</span>
        </span>
    @endif
</a>

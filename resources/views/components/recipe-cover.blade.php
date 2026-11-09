@props(['recipe', 'icon' => 'size-9'])

@if ($url = $recipe->coverUrl())
    <span {{ $attributes->class('relative block overflow-hidden') }}>
        <img src="{{ $url }}" alt="" class="size-full object-cover" loading="lazy">
        {{ $slot }}
    </span>
@else
    <span {{ $attributes->class(['relative flex items-center justify-center', $recipe->tintClass()]) }}>
        <flux:icon.bowl class="{{ $icon }}" />
        {{ $slot }}
    </span>
@endif

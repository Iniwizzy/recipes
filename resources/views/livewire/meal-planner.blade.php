<div class="flex flex-col gap-4">

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-[-0.02em] lg:text-[28px]">Rencana menu</h1>
            <p class="mt-1.5 text-[15px] text-ink-2">
                {{ $start->translatedFormat('j M') }} – {{ $end->translatedFormat('j M Y') }}.
                {{ $total ? $total.' menu direncanakan.' : 'Belum ada menu minggu ini.' }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-1 rounded-xl bg-chip p-1" role="group" aria-label="Pilih minggu">
                <button type="button" class="flex size-9 items-center justify-center rounded-[9px] text-ink-2 hover:bg-card" wire:click="shiftWeek(-1)" aria-label="Minggu sebelumnya">
                    <flux:icon.chevron-left variant="mini" />
                </button>
                <button type="button" class="h-9 rounded-[9px] px-3 text-sm font-medium {{ $isThisWeek ? 'bg-card font-semibold text-ink shadow-sm' : 'text-ink-2 hover:bg-card' }}" wire:click="thisWeek">Minggu ini</button>
                <button type="button" class="flex size-9 items-center justify-center rounded-[9px] text-ink-2 hover:bg-card" wire:click="shiftWeek(1)" aria-label="Minggu berikutnya">
                    <flux:icon.chevron-right variant="mini" />
                </button>
            </div>
            <button type="button" class="btn btn-primary" wire:click="addToShoppingList" @disabled($upcoming === 0) wire:loading.attr="disabled" wire:target="addToShoppingList">
                <flux:icon.shopping-bag variant="mini" /> Bahan ke daftar belanja
            </button>
        </div>
    </div>

    @if ($message)
        <p class="flex flex-wrap items-center gap-2 rounded-2xl bg-ok-bg px-4 py-3 text-sm font-semibold text-ok-fg" role="status">
            <flux:icon.check-circle variant="mini" /> {{ $message }}
            <a href="{{ route('shopping.index') }}" class="ml-auto underline" wire:navigate>Lihat daftar belanja</a>
        </p>
    @endif

    @foreach ($days as $day)
        <section wire:key="day-{{ $day['date']->toDateString() }}" @class(['card !p-4 sm:!p-5', '!border-brand' => $day['today'], 'opacity-70' => $day['past']])>
            <div class="flex items-center gap-2.5">
                <h2 class="text-[17px] font-semibold tracking-[-0.01em]">{{ $day['date']->translatedFormat('l') }}</h2>
                <span class="text-sm text-muted">{{ $day['date']->translatedFormat('j M') }}</span>
                @if ($day['today'])
                    <span class="status st-info">Hari ini</span>
                @endif
                <span class="ml-auto text-xs text-muted">{{ $day['count'] ? $day['count'].' menu' : '' }}</span>
            </div>

            <div class="mt-3 grid gap-2.5 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($day['slots'] as $cell)
                    <div class="flex min-w-0 flex-col gap-2 rounded-xl border border-line-soft bg-panel/60 p-2.5">
                        <p class="px-0.5 text-xs font-semibold text-muted">{{ $cell['slot']->label() }}</p>

                        @foreach ($cell['plans'] as $plan)
                            <div wire:key="plan-{{ $plan->id }}" class="flex min-w-0 items-center gap-2.5 rounded-lg border border-line bg-card p-2">
                                <x-recipe-cover :recipe="$plan->recipe" thumb icon="size-4" class="size-9 shrink-0 rounded-lg" />
                                <div class="flex min-w-0 flex-1 flex-col">
                                    <a href="{{ route('recipes.show', $plan->recipe) }}" class="truncate text-sm font-semibold hover:text-brand" wire:navigate>{{ $plan->recipe->title }}</a>
                                    <span class="flex items-center gap-1 text-xs text-muted">
                                        <button type="button" class="flex size-6 items-center justify-center rounded-md hover:bg-hover" wire:click="adjustServings({{ $plan->id }}, -1)" aria-label="Kurangi porsi {{ $plan->recipe->title }}">
                                            <flux:icon.minus variant="micro" />
                                        </button>
                                        <span class="tabular-nums">{{ $plan->servings }} porsi</span>
                                        <button type="button" class="flex size-6 items-center justify-center rounded-md hover:bg-hover" wire:click="adjustServings({{ $plan->id }}, 1)" aria-label="Tambah porsi {{ $plan->recipe->title }}">
                                            <flux:icon.plus variant="micro" />
                                        </button>
                                    </span>
                                </div>
                                @if ($day['today'])
                                    <a href="{{ route('recipes.cook', $plan->recipe) }}" class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand text-on-brand" aria-label="Mulai masak {{ $plan->recipe->title }}">
                                        <flux:icon.play variant="micro" />
                                    </a>
                                @endif
                                <button type="button" class="flex size-9 shrink-0 items-center justify-center rounded-lg text-muted hover:bg-hover hover:text-ink" wire:click="remove({{ $plan->id }})" aria-label="Hapus {{ $plan->recipe->title }} dari rencana">
                                    <flux:icon.x-mark variant="micro" />
                                </button>
                            </div>
                        @endforeach

                        <button type="button" class="flex min-h-10 items-center justify-center gap-1.5 rounded-lg border border-dashed border-line text-[13px] font-medium text-muted hover:border-brand hover:text-brand"
                            wire:click="openPicker('{{ $day['date']->toDateString() }}', '{{ $cell['slot']->value }}')"
                            aria-label="Tambah menu {{ $cell['slot']->label() }} {{ $day['date']->translatedFormat('l j M') }}">
                            <flux:icon.plus variant="micro" /> Tambah
                        </button>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    @if ($pickerOpen)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 sm:items-center sm:p-6"
            x-data x-on:keydown.escape.window="$wire.closePicker()" wire:click.self="closePicker">
            <div class="flex max-h-[85vh] w-full flex-col overflow-hidden rounded-t-2xl bg-card shadow-2xl sm:max-w-lg sm:rounded-2xl"
                role="dialog" aria-modal="true" aria-labelledby="picker-title">
                <div class="flex items-start gap-3 border-b border-line-soft px-5 pt-5 pb-4">
                    <div class="min-w-0 flex-1">
                        <h2 id="picker-title" class="card-title">Pilih resep</h2>
                        <p class="card-sub">{{ $pickerSlotLabel }}, {{ $pickerDateLabel }}</p>
                    </div>
                    <button type="button" class="ibtn !size-10" wire:click="closePicker" aria-label="Tutup">
                        <flux:icon.x-mark variant="mini" />
                    </button>
                </div>

                <div class="flex flex-col gap-3 border-b border-line-soft px-5 py-4">
                    <label class="flex h-11 items-center gap-2.5 rounded-xl border border-line bg-card px-3 text-muted">
                        <flux:icon.magnifying-glass variant="mini" />
                        <input type="search" x-init="$nextTick(() => $el.focus())" wire:model.live.debounce.250ms="search" placeholder="Cari resep atau bahan…" aria-label="Cari resep atau bahan"
                            class="min-w-0 flex-1 border-0 bg-transparent text-base text-ink outline-none placeholder:text-muted sm:text-sm">
                    </label>
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <span class="text-ink-2">Untuk berapa porsi?</span>
                        <div class="flex items-center gap-2">
                            <button type="button" class="ibtn !size-9 !rounded-[10px]" wire:click="adjustPickerServings(-1)" aria-label="Kurangi porsi"><flux:icon.minus variant="micro" /></button>
                            <output class="min-w-16 text-center font-semibold tabular-nums">{{ $pickerServings }} porsi</output>
                            <button type="button" class="ibtn !size-9 !rounded-[10px]" wire:click="adjustPickerServings(1)" aria-label="Tambah porsi"><flux:icon.plus variant="micro" /></button>
                        </div>
                    </div>
                </div>

                <ul class="min-h-0 flex-1 overflow-y-auto px-2 py-2">
                    @forelse ($recipes as $recipe)
                        <li wire:key="pick-{{ $recipe->id }}">
                            <button type="button" class="flex w-full items-center gap-3 rounded-xl p-2.5 text-left hover:bg-hover" wire:click="pick({{ $recipe->id }})">
                                <x-recipe-cover :recipe="$recipe" thumb icon="size-5" class="size-11 shrink-0 rounded-xl" />
                                <span class="flex min-w-0 flex-1 flex-col">
                                    <span class="truncate font-semibold">{{ $recipe->title }}</span>
                                    <span class="truncate text-xs text-muted">
                                        {{ $recipe->category->label() }}{{ $recipe->totalMinutes() ? ', '.$recipe->totalMinutes().' mnt' : '' }}
                                    </span>
                                </span>
                                @if ($recipe->is_favorite)
                                    <flux:icon.heart variant="micro" class="fill-current text-brand" />
                                @endif
                            </button>
                        </li>
                    @empty
                        <li class="px-4 py-8 text-center text-sm text-muted">
                            Tidak ada resep yang cocok.
                            <a href="{{ route('recipes.create') }}" class="font-semibold text-brand" wire:navigate>Tulis resep baru</a>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    @endif
</div>

<div class="flex flex-col gap-4">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-[-0.02em] lg:text-[28px]">{{ $favorites ? 'Resep favorit' : 'Resep saya' }}</h1>
            <p class="mt-1.5 text-[15px] text-ink-2">{{ $recipes->total() }} resep{{ $search !== '' ? ' cocok dengan "'.$search.'"' : '' }}</p>
        </div>
        <a href="{{ route('recipes.create') }}" class="btn btn-primary" wire:navigate>
            <flux:icon.plus variant="mini" /> Tulis resep
        </a>
    </div>

    <label class="flex h-[50px] items-center gap-2.5 rounded-[14px] border border-line bg-card px-3.5 text-muted lg:hidden">
        <flux:icon.magnifying-glass variant="mini" />
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari resep atau bahan…" aria-label="Cari resep atau bahan"
            class="min-w-0 flex-1 border-0 bg-transparent text-base text-ink outline-none placeholder:text-muted">
    </label>
    <label class="hidden h-11 max-w-md items-center gap-2.5 rounded-xl border border-line bg-card px-3.5 text-muted lg:flex">
        <flux:icon.magnifying-glass variant="mini" />
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari judul, deskripsi, atau bahan…" aria-label="Cari judul, deskripsi, atau bahan"
            class="min-w-0 flex-1 border-0 bg-transparent text-sm text-ink outline-none placeholder:text-muted">
    </label>

    <div class="-mx-4 flex gap-2 overflow-x-auto px-4 [scrollbar-width:none] lg:mx-0 lg:flex-wrap lg:px-0">
        <button type="button" class="pick" wire:click="pickCategory('')" aria-pressed="{{ $active ? 'false' : 'true' }}">Semua</button>
        @foreach ($categories as $category)
            <button type="button" class="pick" wire:click="pickCategory('{{ $category->value }}')" aria-pressed="{{ $active === $category ? 'true' : 'false' }}">{{ $category->label() }}</button>
        @endforeach
        <button type="button" class="pick" wire:click="$toggle('favorites')" aria-pressed="{{ $favorites ? 'true' : 'false' }}">
            <flux:icon.heart variant="micro" class="mr-1.5" /> Favorit
        </button>
    </div>

    @if ($recipes->isEmpty())
        <div class="rounded-[18px] border border-dashed border-line bg-card p-10 text-center">
            <p class="font-semibold">Belum ada resep di sini.</p>
            <p class="mt-1 text-sm text-muted">
                @if ($search !== '' || $active || $favorites)
                    Coba kata kunci lain atau pilih kategori "Semua".
                @else
                    Mulai dengan menulis resep andalanmu.
                @endif
            </p>
            <a href="{{ route('recipes.create') }}" class="btn btn-primary mt-4" wire:navigate><flux:icon.plus variant="mini" /> Tulis resep</a>
        </div>
    @else
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:gap-4 xl:grid-cols-4">
            @foreach ($recipes as $recipe)
                <a href="{{ route('recipes.show', $recipe) }}" wire:navigate wire:key="recipe-{{ $recipe->id }}"
                    class="group flex flex-col overflow-hidden rounded-2xl border border-line bg-card shadow-[0_1px_2px_rgba(16,24,40,.05)] transition hover:border-brand dark:shadow-none">
                    <x-recipe-cover :recipe="$recipe" thumb class="h-[108px] lg:h-36">
                        @if ($recipe->is_favorite)
                            <span class="absolute top-2 right-2 flex size-7 items-center justify-center rounded-full bg-card text-brand">
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
                        <span class="hidden text-xs text-muted sm:block">{{ $recipe->category->label() }}{{ $recipe->cooked_count ? ', dimasak '.$recipe->cooked_count.'×' : '' }}</span>
                    </span>
                </a>
            @endforeach
        </div>

        <div>{{ $recipes->links() }}</div>
    @endif
</div>

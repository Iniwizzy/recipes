<div class="flex flex-col gap-4">

    @if (session('status'))
        <div class="flex items-center gap-2.5 rounded-2xl bg-ok-bg px-4 py-3 text-sm font-semibold text-ok-fg" role="status">
            <flux:icon.check-circle variant="mini" /> {{ session('status') }}
        </div>
    @endif

    <div>
        <h1 class="text-2xl font-semibold tracking-[-0.02em] lg:text-[28px]">Koleksi</h1>
        <p class="mt-1.5 text-[15px] text-ink-2">Kelompokkan resep sesukamu, misalnya "Menu anak", "Lebaran", atau "Bekal". Satu resep boleh masuk ke beberapa koleksi.</p>
    </div>

    <form wire:submit="create" class="card flex flex-col gap-2 !p-4">
        <label for="collection-name" class="field-label !mb-0">Koleksi baru</label>
        <div class="flex gap-2">
            <input id="collection-name" type="text" class="field" wire:model="name" placeholder="Contoh: Menu anak" maxlength="60" autocomplete="off">
            <button type="submit" class="btn btn-primary shrink-0" wire:loading.attr="disabled" wire:target="create">
                <flux:icon.plus variant="mini" /> <span class="hidden sm:inline">Buat</span>
            </button>
        </div>
        @error('name') <p class="field-error !mt-0">{{ $message }}</p> @enderror
    </form>

    @if ($collections->isEmpty())
        <div class="rounded-[18px] border border-dashed border-line bg-card p-10 text-center">
            <span class="tile tint-3 mx-auto"><flux:icon.rectangle-stack variant="mini" /></span>
            <p class="mt-3 font-semibold">Belum ada koleksi</p>
            <p class="mt-1 text-sm text-muted">Buat koleksi di atas, atau buka resep lalu tekan tombol <b>Koleksi</b>.</p>
        </div>
    @else
        <div class="grid gap-3 sm:grid-cols-2 lg:gap-4 xl:grid-cols-3">
            @foreach ($collections as $collection)
                <a href="{{ route('collections.show', $collection) }}" wire:navigate wire:key="collection-{{ $collection->id }}"
                    class="group flex flex-col overflow-hidden rounded-2xl border border-line bg-card shadow-[0_1px_2px_rgba(16,24,40,.05)] transition hover:border-brand dark:shadow-none">
                    @if ($collection->recipes->isEmpty())
                        <span class="flex h-36 items-center justify-center {{ $collection->tintClass() }}">
                            <flux:icon.rectangle-stack class="size-10 opacity-70" />
                        </span>
                    @else
                        <span class="grid h-36 grid-cols-2 grid-rows-2 gap-0.5 bg-line-soft">
                            @foreach ($collection->recipes->take(4) as $i => $recipe)
                                <x-recipe-cover :recipe="$recipe" thumb icon="size-6" @class([
                                    'size-full',
                                    'row-span-2' => $collection->recipes->count() === 1 || ($collection->recipes->count() === 3 && $i === 0),
                                    'col-span-2' => $collection->recipes->count() === 1,
                                    'row-span-2 col-span-1' => $collection->recipes->count() === 2,
                                ]) />
                            @endforeach
                        </span>
                    @endif
                    <span class="flex items-center justify-between gap-3 p-4">
                        <b class="truncate font-semibold group-hover:text-brand">{{ $collection->name }}</b>
                        <span class="shrink-0 text-sm text-muted">{{ $collection->recipes_count }} resep</span>
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</div>

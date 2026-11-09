<div class="flex flex-col gap-4">

    <nav class="flex min-h-11 items-center gap-1.5 text-sm text-muted" aria-label="Lokasi">
        <a href="{{ route('collections.index') }}" class="hover:text-ink" wire:navigate>Koleksi</a>
        <flux:icon.chevron-right variant="micro" />
        <span class="truncate font-semibold text-ink" aria-current="page">{{ $collection->name }}</span>
    </nav>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            @if ($renaming)
                <form wire:submit="rename" class="flex max-w-md flex-col gap-2">
                    <label for="rename" class="field-label !mb-0">Nama koleksi</label>
                    <div class="flex gap-2">
                        <input id="rename" type="text" class="field" wire:model="name" maxlength="60" x-init="$el.focus()">
                        <button type="submit" class="btn btn-primary shrink-0">Simpan</button>
                        <button type="button" class="btn shrink-0" wire:click="$set('renaming', false)">Batal</button>
                    </div>
                    @error('name') <p class="field-error !mt-0">{{ $message }}</p> @enderror
                </form>
            @else
                <h1 class="text-2xl font-semibold tracking-[-0.02em] break-words lg:text-[28px]">{{ $collection->name }}</h1>
                <p class="mt-1.5 text-[15px] text-ink-2">{{ $recipes->count() }} resep</p>
            @endif
        </div>
        @unless ($renaming)
            <div class="flex items-center gap-2">
                <button type="button" class="btn" wire:click="$set('renaming', true)"><flux:icon.pencil-square variant="mini" /> Ubah nama</button>
                <flux:modal.trigger name="hapus-koleksi">
                    <button type="button" class="ibtn" aria-label="Hapus koleksi"><flux:icon.trash variant="mini" /></button>
                </flux:modal.trigger>
            </div>
        @endunless
    </div>

    @if ($recipes->isEmpty())
        <div class="rounded-[18px] border border-dashed border-line bg-card p-10 text-center">
            <p class="font-semibold">Koleksi ini masih kosong</p>
            <p class="mt-1 text-sm text-muted">Buka resep, tekan tombol <b>Koleksi</b>, lalu centang "{{ $collection->name }}".</p>
            <a href="{{ route('recipes.index') }}" class="btn btn-primary mt-4" wire:navigate><flux:icon.book-open variant="mini" /> Pilih resep</a>
        </div>
    @else
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:gap-4 xl:grid-cols-4">
            @foreach ($recipes as $recipe)
                <x-recipe-card :recipe="$recipe" wire:key="in-{{ $recipe->id }}">
                    <button type="button" class="absolute top-2 right-2 flex size-9 items-center justify-center rounded-full bg-card/90 text-ink-2 shadow-sm hover:text-ink"
                        wire:click="removeRecipe({{ $recipe->id }})" aria-label="Keluarkan {{ $recipe->title }} dari koleksi">
                        <flux:icon.x-mark variant="micro" />
                    </button>
                </x-recipe-card>
            @endforeach
        </div>
    @endif

    <flux:modal name="hapus-koleksi" class="max-w-md">
        <div class="flex flex-col gap-4">
            <div>
                <flux:heading size="lg">Hapus koleksi "{{ $collection->name }}"?</flux:heading>
                <flux:text class="mt-2">Hanya koleksinya yang dihapus. Resep-resep di dalamnya tetap ada di Resep saya.</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <button type="button" class="btn">Batal</button>
                </flux:modal.close>
                <button type="button" class="btn !border-crit-fg !bg-crit-fg !text-white" wire:click="delete">Hapus koleksi</button>
            </div>
        </div>
    </flux:modal>
</div>

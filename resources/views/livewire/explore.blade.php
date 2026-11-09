<div class="flex flex-col gap-4">

    <div>
        <h1 class="text-2xl font-semibold tracking-[-0.02em] lg:text-[28px]">Jelajahi resep</h1>
        <p class="mt-1.5 max-w-2xl text-[15px] text-ink-2">
            {{ $total }} resep dari komunitas {{ config('app.name') }}. Simpan yang kamu suka, salin untuk diubah, atau langsung masukkan ke rencana menu.
        </p>
    </div>

    <div class="grid gap-2.5 md:grid-cols-2">
        <label class="flex h-12 items-center gap-2.5 rounded-[14px] border border-line bg-card px-3.5 text-muted">
            <flux:icon.magnifying-glass variant="mini" />
            <input type="search" wire:model.live.debounce.350ms="search" placeholder="Cari nama resep atau bahan…" aria-label="Cari nama resep atau bahan"
                class="min-w-0 flex-1 border-0 bg-transparent text-base text-ink outline-none placeholder:text-muted sm:text-sm">
        </label>
        <label class="flex h-12 items-center gap-2.5 rounded-[14px] border border-line bg-card px-3.5 text-muted">
            <flux:icon.shopping-cart variant="mini" />
            <input type="search" wire:model.live.debounce.500ms="have" placeholder="Punya bahan apa? mis. telur, nasi" aria-label="Bahan yang kamu punya, pisahkan dengan koma"
                class="min-w-0 flex-1 border-0 bg-transparent text-base text-ink outline-none placeholder:text-muted sm:text-sm">
        </label>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <div class="-mx-4 flex flex-1 gap-2 overflow-x-auto px-4 [scrollbar-width:none] md:mx-0 md:flex-wrap md:px-0">
            <button type="button" class="pick" wire:click="pickCategory('')" aria-pressed="{{ $active ? 'false' : 'true' }}">Semua</button>
            @foreach ($categories as $category)
                <button type="button" class="pick" wire:click="pickCategory('{{ $category->value }}')" aria-pressed="{{ $active === $category ? 'true' : 'false' }}">{{ $category->label() }}</button>
            @endforeach
        </div>
        <div class="grid w-full grid-cols-2 gap-2 md:flex md:w-auto">
            <select wire:model.live="maxMinutes" class="field !h-11 !pr-8 md:!w-auto" aria-label="Lama memasak">
                <option value="0">Semua durasi</option>
                <option value="15">Maks. 15 menit</option>
                <option value="30">Maks. 30 menit</option>
                <option value="60">Maks. 1 jam</option>
            </select>
            <select wire:model.live="sort" class="field !h-11 !pr-8 md:!w-auto" aria-label="Urutkan">
                <option value="populer">Paling disimpan</option>
                <option value="terbaru">Terbaru</option>
                <option value="tercepat">Paling cepat</option>
            </select>
        </div>
    </div>

    @if ($haveTerms)
        <p class="text-sm text-ink-2">Resep yang memakai <b>{{ implode(', ', $haveTerms) }}</b>: {{ $recipes->total() }} ditemukan.</p>
    @endif

    @if ($recipes->isEmpty())
        <div class="rounded-[18px] border border-dashed border-line bg-card p-10 text-center">
            <span class="tile tint-1 mx-auto"><flux:icon.globe-alt variant="mini" /></span>
            @if ($total === 0)
                <p class="mt-3 font-semibold">Belum ada resep yang dibagikan</p>
                <p class="mt-1 text-sm text-muted">Jadilah yang pertama: buka resep andalanmu, tekan Ubah, lalu centang "Terbitkan di Jelajahi".</p>
            @else
                <p class="mt-3 font-semibold">Tidak ada resep yang cocok</p>
                <p class="mt-1 text-sm text-muted">Coba kata kunci lain, kurangi bahan, atau pilih kategori "Semua".</p>
            @endif
        </div>
    @else
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:gap-4 xl:grid-cols-4">
            @foreach ($recipes as $recipe)
                <x-recipe-card :recipe="$recipe" byline wire:key="explore-{{ $recipe->id }}" />
            @endforeach
        </div>
        <div>{{ $recipes->links() }}</div>
    @endif

    @guest
        <section class="card mt-2 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="card-title">Punya resep andalan?</h2>
                <p class="card-sub">Daftar gratis untuk menyimpan resep, membuat rencana menu, dan membagikan resepmu sendiri.</p>
            </div>
            <a href="{{ route('register') }}" class="btn btn-primary">Daftar gratis</a>
        </section>
    @endguest
</div>

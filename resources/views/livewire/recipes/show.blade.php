<div class="flex flex-col gap-4" x-data="recipeDetail(@js($scaler))">

    @if (session('status'))
        <div class="flex items-center gap-2.5 rounded-2xl bg-ok-bg px-4 py-3 text-sm font-semibold text-ok-fg" role="status">
            <flux:icon.check-circle variant="mini" /> {{ session('status') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="flex min-h-11 items-center gap-1.5 text-sm text-muted" aria-label="Lokasi">
            <a href="{{ route('recipes.index') }}" class="hover:text-ink" wire:navigate>Resep saya</a>
            <flux:icon.chevron-right variant="micro" />
            <a href="{{ route('recipes.index', ['kategori' => $recipe->category->value]) }}" class="hover:text-ink" wire:navigate>{{ $recipe->category->label() }}</a>
            <flux:icon.chevron-right variant="micro" class="hidden sm:block" />
            <span class="hidden font-semibold text-ink sm:inline" aria-current="page">{{ $recipe->title }}</span>
        </nav>
        <div class="flex flex-wrap items-center gap-2">
            @if ($canEdit)
                <button type="button" class="btn" wire:click="toggleFavorite" aria-pressed="{{ $recipe->is_favorite ? 'true' : 'false' }}"
                    @class(['!border-transparent !bg-brand-tint !text-brand' => $recipe->is_favorite])>
                    <flux:icon.heart variant="mini" @class(['fill-current' => $recipe->is_favorite]) />
                    {{ $recipe->is_favorite ? 'Favorit' : 'Jadikan favorit' }}
                </button>
                <a href="{{ route('recipes.edit', $recipe) }}" class="btn" wire:navigate><flux:icon.pencil-square variant="mini" /> Ubah</a>
                <flux:modal.trigger name="hapus-resep">
                    <button type="button" class="ibtn" aria-label="Hapus resep"><flux:icon.trash variant="mini" /></button>
                </flux:modal.trigger>
            @endif

            {{-- Save to collections: a panel under the button on desktop, a sheet near the top on phones. --}}
            <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false">
                <button type="button" class="btn" x-on:click="open = !open" x-bind:aria-expanded="open" aria-haspopup="true"
                    @class(['!border-transparent !bg-brand-tint !text-brand' => $inCollections])>
                    <flux:icon.rectangle-stack variant="mini" /> Koleksi
                    @if ($inCollections)
                        <span class="text-xs font-bold tabular-nums">{{ count($inCollections) }}</span>
                    @endif
                </button>
                <div x-cloak x-show="open" x-transition.opacity
                    class="fixed inset-x-4 top-20 z-40 rounded-2xl border border-line bg-card p-3 shadow-[0_12px_32px_rgba(16,24,40,.18)] sm:absolute sm:inset-x-auto sm:top-auto sm:right-0 sm:mt-2 sm:w-80">
                    <p class="px-1 text-sm font-semibold">Simpan ke koleksi</p>
                    <ul class="mt-1.5 max-h-64 overflow-y-auto">
                        @forelse ($collections as $collection)
                            <li wire:key="col-{{ $collection->id }}">
                                <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg px-1 hover:bg-hover">
                                    <input type="checkbox" class="size-5 shrink-0 accent-brand" @checked(in_array($collection->id, $inCollections, true)) wire:click="toggleCollection({{ $collection->id }})">
                                    <span class="min-w-0 flex-1 truncate">{{ $collection->name }}</span>
                                    <span class="text-xs text-muted tabular-nums">{{ $collection->recipes_count }}</span>
                                </label>
                            </li>
                        @empty
                            <li class="px-1 py-2 text-sm text-muted">Belum ada koleksi. Buat yang pertama di bawah.</li>
                        @endforelse
                    </ul>
                    <form wire:submit="createCollection" class="mt-2 flex gap-2 border-t border-line-soft pt-3">
                        <input type="text" class="field !h-10" wire:model="newCollection" placeholder="Koleksi baru, mis. Menu anak" maxlength="60" aria-label="Nama koleksi baru">
                        <button type="submit" class="btn btn-primary btn-sm shrink-0" aria-label="Buat koleksi dan simpan resep ini"><flux:icon.plus variant="micro" /></button>
                    </form>
                    @error('newCollection') <p class="field-error px-1">{{ $message }}</p> @enderror
                    <a href="{{ route('collections.index') }}" class="mt-2 block px-1 text-xs font-semibold text-brand hover:underline" wire:navigate>Kelola koleksi</a>
                </div>
            </div>

            <a href="{{ route('recipes.cook', $recipe) }}" class="btn btn-primary"><flux:icon.play variant="mini" /> Mulai Masak</a>
        </div>
    </div>

    <div class="flex flex-col gap-2.5">
        <div class="flex flex-wrap gap-2">
            <span class="chip !h-7 !bg-card !px-3 !text-[13px] ring-1 ring-line">{{ $recipe->category->label() }}</span>
            <span class="chip !h-7 !bg-card !px-3 !text-[13px] ring-1 ring-line">{{ $recipe->difficulty->label() }}</span>
            @if ($recipe->sourceLabel())
                <a href="{{ $recipe->source_url }}" target="_blank" rel="noopener noreferrer" class="chip !h-7 !bg-card !px-3 !text-[13px] ring-1 ring-line hover:text-ink">
                    <flux:icon.link variant="micro" /> Dari {{ $recipe->sourceLabel() }}
                </a>
            @endif
            @if ($recipe->is_public)
                <span class="status st-ok !h-7">Publik</span>
            @endif
        </div>
        <h1 class="text-[28px] leading-tight font-semibold tracking-[-0.02em] lg:text-[34px]">{{ $recipe->title }}</h1>
        @if ($recipe->description)
            <p class="max-w-3xl text-[15px] text-ink-2">{{ $recipe->description }}</p>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
        <section class="card flex flex-col gap-3 !p-4 lg:!px-5">
            <div class="flex items-center gap-3 text-sm font-medium text-ink-2"><span class="tile tint-1 !size-9"><flux:icon.clock variant="mini" /></span>Total waktu</div>
            <span class="text-2xl leading-none font-semibold tracking-[-0.02em] lg:text-[28px]">{{ $recipe->totalMinutes() ? $recipe->totalMinutes().' mnt' : '–' }}</span>
            <span class="text-[13px] text-muted">
                @if ($recipe->prep_minutes || $recipe->cook_minutes)
                    Siapkan {{ $recipe->prep_minutes ?: 0 }}, masak {{ $recipe->cook_minutes ?: 0 }}
                @else
                    Belum diisi
                @endif
            </span>
        </section>
        <section class="card flex flex-col gap-3 !p-4 lg:!px-5">
            <div class="flex items-center gap-3 text-sm font-medium text-ink-2"><span class="tile tint-2 !size-9"><flux:icon.users variant="mini" /></span>Porsi</div>
            <div class="flex items-center gap-2">
                <button type="button" class="ibtn !size-10 !rounded-[10px]" x-on:click="dec()" aria-label="Kurangi porsi"><flux:icon.minus variant="mini" /></button>
                <output class="min-w-10 text-center text-2xl leading-none font-semibold lg:text-[28px]" x-text="porsi" aria-live="polite">{{ $recipe->servings }}</output>
                <button type="button" class="ibtn !size-10 !rounded-[10px]" x-on:click="inc()" aria-label="Tambah porsi"><flux:icon.plus variant="mini" /></button>
            </div>
            <span class="text-[13px] text-muted">Takaran bahan ikut menyesuaikan</span>
        </section>
        <section class="card flex flex-col gap-3 !p-4 lg:!px-5">
            <div class="flex items-center gap-3 text-sm font-medium text-ink-2"><span class="tile tint-3 !size-9"><flux:icon.check variant="mini" /></span>Bahan siap</div>
            <span class="text-2xl leading-none font-semibold tracking-[-0.02em] lg:text-[28px]"><span x-text="checked.length">0</span> dari {{ $recipe->ingredients->count() }}</span>
            <span class="meter"><span x-bind:style="'width:' + readyPercent() + '%'" style="width: 0%"></span></span>
        </section>
        <section class="card flex flex-col gap-3 !p-4 lg:!px-5">
            <div class="flex items-center gap-3 text-sm font-medium text-ink-2"><span class="tile tint-4 !size-9"><flux:icon.cooking-pot variant="mini" /></span>Sudah dimasak</div>
            <span class="text-2xl leading-none font-semibold tracking-[-0.02em] lg:text-[28px]">{{ $recipe->cooked_count }} kali</span>
            <span class="text-[13px] text-muted">{{ $recipe->last_cooked_at ? 'Terakhir '.$recipe->last_cooked_at->translatedFormat('j M, H.i') : 'Belum pernah' }}</span>
        </section>
    </div>

    <div class="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_420px]">
        <div class="order-2 flex min-w-0 flex-col gap-4 lg:order-1">
            <section class="card">
                <h2 class="card-title">Langkah</h2>
                <p class="card-sub">{{ $recipe->steps->count() }} langkah{{ $timers ? ', '.$timers.' di antaranya pakai timer' : '' }}. Mode Masak menampilkan satu langkah per layar.</p>
                <ol class="mt-3.5 flex flex-col gap-2">
                    @forelse ($recipe->steps as $step)
                        <li class="flex gap-3.5 rounded-[14px] border border-line-soft p-3.5">
                            <span class="num">{{ $loop->iteration }}</span>
                            <div class="flex min-w-0 flex-col gap-2.5">
                                <p class="leading-relaxed">{{ $step->instruction }}</p>
                                @if ($step->timerMinutes())
                                    <span class="chip chip-timer self-start"><flux:icon.clock variant="micro" /> Timer {{ $step->timerMinutes() }} menit</span>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-muted">Belum ada langkah.</li>
                    @endforelse
                </ol>
            </section>

            @if ($recipe->notes)
                <section class="card">
                    <h2 class="card-title">Catatan saya</h2>
                    <p class="mt-3 rounded-xl border border-flag-line bg-flag-bg px-4 py-3.5 leading-relaxed whitespace-pre-line">{{ $recipe->notes }}</p>
                </section>
            @endif
        </div>

        <div class="order-1 flex min-w-0 flex-col gap-4 lg:order-2">
            @if ($recipe->coverUrl() || $youtubeId || $recipe->source_url)
                <section class="card">
                    @if ($youtubeId)
                        <div class="aspect-video overflow-hidden rounded-xl bg-chip">
                            <iframe class="size-full" src="https://www.youtube-nocookie.com/embed/{{ $youtubeId }}" title="Video tutorial {{ $recipe->title }}" loading="lazy"
                                allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    @else
                        <x-recipe-cover :recipe="$recipe" icon="size-24 opacity-40" class="h-[220px] rounded-xl" />
                    @endif
                    @if ($recipe->source_url)
                        <div class="mt-3.5 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <b class="block font-semibold">Sumber: {{ $recipe->sourceLabel() }}</b>
                                <span class="block truncate text-[13px] text-muted">{{ $recipe->source_url }}</span>
                            </div>
                            <a href="{{ $recipe->source_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm"><flux:icon.arrow-top-right-on-square variant="micro" /> Buka</a>
                        </div>
                    @endif
                </section>
            @endif

            <section class="card">
                <h2 class="card-title">Bahan</h2>
                <p class="card-sub">Untuk <span x-text="porsi">{{ $recipe->servings }}</span> porsi. Centang yang sudah siap.</p>
                <ul class="mt-2.5">
                    @foreach ($recipe->ingredients as $ingredient)
                        <li class="border-t border-line-soft first:border-t-0">
                            <label class="flex min-h-11 cursor-pointer items-center gap-3 py-1.5">
                                <input type="checkbox" value="{{ $loop->index }}" x-model.number="checked" class="size-5 shrink-0 accent-brand">
                                <span class="w-[104px] shrink-0 font-semibold tabular-nums" x-bind:class="checked.includes({{ $loop->index }}) && 'line-through text-muted'"
                                    x-text="amount({{ $loop->index }})">{{ $ingredient->amount() }}</span>
                                <span x-bind:class="checked.includes({{ $loop->index }}) && 'line-through text-muted'">
                                    {{ $ingredient->name }}@if ($ingredient->note)<span class="text-muted">, {{ $ingredient->note }}</span>@endif
                                </span>
                            </label>
                        </li>
                    @endforeach
                </ul>
                @if ($recipe->ingredients->isNotEmpty())
                    <div class="mt-3 flex flex-col gap-2">
                        <button type="button" class="btn w-full" x-on:click="$wire.addToShoppingList(porsi, uncheckedIndexes())"
                            x-bind:disabled="checked.length === items.length" wire:loading.attr="disabled" wire:target="addToShoppingList">
                            <flux:icon.shopping-bag variant="mini" />
                            <span x-text="checked.length === items.length ? 'Semua bahan sudah siap' : 'Masukkan ' + (items.length - checked.length) + ' bahan ke daftar belanja'">Masukkan bahan ke daftar belanja</span>
                        </button>
                        @if ($addedMessage)
                            <p class="flex flex-wrap items-center gap-2 rounded-xl bg-ok-bg px-3 py-2 text-sm font-medium text-ok-fg" role="status">
                                <flux:icon.check-circle variant="micro" /> {{ $addedMessage }}
                                <a href="{{ route('shopping.index') }}" class="ml-auto font-semibold underline" wire:navigate>Lihat daftar</a>
                            </p>
                        @endif
                    </div>
                @endif
            </section>
        </div>
    </div>

    @if ($canEdit)
        <flux:modal name="hapus-resep" class="max-w-md">
            <div class="flex flex-col gap-4">
                <div>
                    <flux:heading size="lg">Hapus "{{ $recipe->title }}"?</flux:heading>
                    <flux:text class="mt-2">Resep, bahan, langkah, dan catatan masaknya akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.</flux:text>
                </div>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <button type="button" class="btn">Batal</button>
                    </flux:modal.close>
                    <button type="button" class="btn !border-crit-fg !bg-crit-fg !text-white" wire:click="delete">Hapus resep</button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>

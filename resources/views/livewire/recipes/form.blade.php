<form wire:submit="save" class="mx-auto flex max-w-4xl flex-col gap-4">

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-[-0.02em] lg:text-[28px]">{{ $recipe ? 'Ubah resep' : 'Resep baru' }}</h1>
            <p class="mt-1.5 text-[15px] text-ink-2">Satu langkah, satu tindakan. Bahan yang kosong tidak ikut disimpan.</p>
        </div>
        <div class="hidden items-center gap-2 sm:flex">
            <a href="{{ $recipe ? route('recipes.show', $recipe) : route('recipes.index') }}" class="btn" wire:navigate>Batal</a>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save,cover">
                <flux:icon.check variant="mini" /> Simpan resep
            </button>
        </div>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-crit-fg/30 bg-crit-bg px-4 py-3 text-sm text-crit-fg" role="alert">
            <b class="font-semibold">Ada {{ $errors->count() }} isian yang perlu diperbaiki.</b> Bagian yang bermasalah ditandai merah di bawah.
        </div>
    @endif

    <section class="card">
        <h2 class="card-title">Info dasar</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="field-label" for="title">Nama resep</label>
                <input id="title" type="text" class="field" wire:model="title" placeholder="Contoh: Nasi Goreng Kampung" maxlength="150" required>
                @error('title') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="field-label" for="description">Deskripsi singkat</label>
                <textarea id="description" rows="2" class="field" wire:model="description" placeholder="Apa yang membuat resep ini spesial?" maxlength="1000"></textarea>
                @error('description') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <p class="field-label">Kategori</p>
                <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Kategori">
                    @foreach ($categories as $option)
                        <button type="button" class="pick" role="radio" wire:click="$set('category', '{{ $option->value }}')"
                            aria-checked="{{ $category === $option->value ? 'true' : 'false' }}" aria-pressed="{{ $category === $option->value ? 'true' : 'false' }}">
                            {{ $option->label() }}
                        </button>
                    @endforeach
                </div>
                @error('category') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-3 gap-3 sm:col-span-2 sm:grid-cols-4">
                <div>
                    <label class="field-label" for="servings">Porsi</label>
                    <input id="servings" type="number" min="1" max="50" class="field" wire:model="servings" required>
                    @error('servings') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label" for="prep">Siapkan (mnt)</label>
                    <input id="prep" type="number" min="0" max="1440" class="field" wire:model="prep_minutes" placeholder="10">
                    @error('prep_minutes') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label" for="cook">Masak (mnt)</label>
                    <input id="cook" type="number" min="0" max="1440" class="field" wire:model="cook_minutes" placeholder="20">
                    @error('cook_minutes') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="col-span-3 sm:col-span-1">
                    <label class="field-label" for="difficulty">Tingkat</label>
                    <select id="difficulty" class="field" wire:model="difficulty">
                        @foreach ($difficulties as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </section>

    <section class="card">
        <h2 class="card-title">Foto sampul</h2>
        <p class="card-sub">Opsional. JPG, PNG, atau WebP sampai 10 MB; otomatis dikecilkan.</p>
        <div class="mt-4 flex flex-wrap items-center gap-4">
            @if ($cover && ! $errors->has('cover'))
                <img src="{{ $cover->temporaryUrl() }}" alt="Pratinjau sampul" class="h-28 w-40 rounded-xl object-cover">
            @elseif ($recipe?->coverUrl() && ! $removeCover)
                <img src="{{ $recipe->coverUrl() }}" alt="Sampul sekarang" class="h-28 w-40 rounded-xl object-cover">
            @endif
            <label class="flex h-28 min-w-48 flex-1 cursor-pointer flex-col items-center justify-center gap-1.5 rounded-[14px] border-[1.5px] border-dashed border-line bg-panel text-muted hover:border-brand">
                <flux:icon.photo class="size-7" />
                <b class="text-sm font-semibold text-ink">{{ $cover || ($recipe?->cover_path && ! $removeCover) ? 'Ganti foto' : 'Pilih foto' }}</b>
                <span class="text-xs" wire:loading wire:target="cover">Mengunggah…</span>
                <input type="file" accept="image/*" class="sr-only" wire:model="cover">
            </label>
            @if ($recipe?->cover_path && ! $cover)
                <label class="flex min-h-11 items-center gap-2.5 text-sm">
                    <input type="checkbox" class="size-5 accent-brand" wire:model.live="removeCover"> Hapus sampul
                </label>
            @endif
        </div>
        @error('cover') <p class="field-error">{{ $message }}</p> @enderror
    </section>

    <section class="card">
        <h2 class="card-title">Bahan</h2>
        <p class="card-sub">Jumlah boleh ditulis 2, 1,5, atau 1/2. Kosongkan jumlah untuk "secukupnya".</p>
        <datalist id="units">
            @foreach ($units as $unit)
                <option value="{{ $unit }}"></option>
            @endforeach
        </datalist>
        <div class="mt-4 hidden grid-cols-[72px_120px_minmax(0,1fr)_minmax(0,0.8fr)_44px] gap-2 text-xs font-semibold text-muted sm:grid">
            <span>Jumlah</span><span>Satuan</span><span>Nama bahan</span><span>Catatan</span><span></span>
        </div>
        <div class="mt-3 flex flex-col gap-3 sm:mt-2 sm:gap-2">
            @foreach ($ingredients as $i => $row)
                <div wire:key="ingredient-{{ $i }}" class="rounded-xl border border-line-soft p-2.5 sm:border-0 sm:p-0">
                    <div class="grid grid-cols-[64px_96px_minmax(0,1fr)_44px] gap-2 sm:grid-cols-[72px_120px_minmax(0,1fr)_minmax(0,0.8fr)_44px]">
                        <input type="text" inputmode="decimal" class="field !px-2 text-center" wire:model="ingredients.{{ $i }}.quantity" placeholder="2" aria-label="Jumlah bahan {{ $i + 1 }}">
                        <input type="text" list="units" class="field !px-2" wire:model="ingredients.{{ $i }}.unit" placeholder="siung" aria-label="Satuan bahan {{ $i + 1 }}">
                        <input type="text" class="field" wire:model="ingredients.{{ $i }}.name" placeholder="Nama bahan" aria-label="Nama bahan {{ $i + 1 }}">
                        <input type="text" class="field col-span-3 row-start-2 sm:col-span-1 sm:row-start-auto" wire:model="ingredients.{{ $i }}.note" placeholder="Catatan, mis. iris tipis" aria-label="Catatan bahan {{ $i + 1 }}">
                        <button type="button" class="ibtn col-start-4 row-start-1 !border-transparent !bg-transparent !shadow-none sm:col-start-auto" wire:click="removeIngredient({{ $i }})" aria-label="Hapus bahan {{ $i + 1 }}">
                            <flux:icon.trash variant="mini" />
                        </button>
                    </div>
                    @error('ingredients.'.$i.'.quantity') <p class="field-error">{{ $message }}</p> @enderror
                    @error('ingredients.'.$i.'.name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>
        @error('ingredients') <p class="field-error">{{ $message }}</p> @enderror
        <button type="button" class="btn mt-3 w-full" wire:click="addIngredient"><flux:icon.plus variant="mini" /> Tambah bahan</button>
    </section>

    <section class="card">
        <h2 class="card-title">Langkah</h2>
        <p class="card-sub">Isi timer (menit) untuk langkah yang perlu ditunggu. Timer-nya muncul di Mode Masak.</p>
        <div class="mt-4 flex flex-col gap-3">
            @foreach ($steps as $i => $row)
                <div wire:key="step-{{ $i }}" class="flex flex-col gap-2.5 rounded-[14px] border border-line-soft bg-panel p-3">
                    <div class="flex items-center gap-2.5">
                        <span class="num !size-7 !text-[13px]">{{ $i + 1 }}</span>
                        <span class="text-[13px] font-semibold text-ink-2">Langkah {{ $i + 1 }}</span>
                        <div class="ml-auto flex items-center">
                            <button type="button" class="ibtn !border-transparent !bg-transparent !shadow-none disabled:opacity-30" wire:click="moveStep({{ $i }}, -1)" @disabled($i === 0) aria-label="Naikkan langkah {{ $i + 1 }}">
                                <flux:icon.arrow-up variant="mini" />
                            </button>
                            <button type="button" class="ibtn !border-transparent !bg-transparent !shadow-none disabled:opacity-30" wire:click="moveStep({{ $i }}, 1)" @disabled($i === count($steps) - 1) aria-label="Turunkan langkah {{ $i + 1 }}">
                                <flux:icon.arrow-down variant="mini" />
                            </button>
                            <button type="button" class="ibtn !border-transparent !bg-transparent !shadow-none" wire:click="removeStep({{ $i }})" aria-label="Hapus langkah {{ $i + 1 }}">
                                <flux:icon.trash variant="mini" />
                            </button>
                        </div>
                    </div>
                    <textarea rows="3" class="field" wire:model="steps.{{ $i }}.instruction" placeholder="Tulis langkahnya…" aria-label="Isi langkah {{ $i + 1 }}"></textarea>
                    @error('steps.'.$i.'.instruction') <p class="field-error !mt-0">{{ $message }}</p> @enderror
                    <label class="flex items-center gap-2.5 text-[13px] font-medium text-ink-2">
                        <flux:icon.clock variant="mini" class="text-muted" />
                        Timer
                        <input type="number" min="0" max="600" class="field !h-10 !w-20 text-center" wire:model="steps.{{ $i }}.timer" placeholder="–" aria-label="Timer langkah {{ $i + 1 }} dalam menit">
                        menit
                    </label>
                    @error('steps.'.$i.'.timer') <p class="field-error !mt-0">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>
        @error('steps') <p class="field-error">{{ $message }}</p> @enderror
        <button type="button" class="btn mt-3 w-full" wire:click="addStep"><flux:icon.plus variant="mini" /> Tambah langkah</button>
    </section>

    <section class="card">
        <h2 class="card-title">Lainnya</h2>
        <div class="mt-4 grid gap-4">
            <div>
                <label class="field-label" for="source">Link sumber atau video (opsional)</label>
                <input id="source" type="url" class="field" wire:model="source_url" placeholder="https://www.youtube.com/watch?v=…">
                @error('source_url') <p class="field-error">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-muted">Video YouTube langsung tampil di halaman resep. Nama kreator tetap tercantum lewat link ini.</p>
            </div>
            <div>
                <label class="field-label" for="notes">Catatan saya</label>
                <textarea id="notes" rows="3" class="field" wire:model="notes" placeholder="Tips, takaran yang cocok untuk keluarga, dll." maxlength="2000"></textarea>
                @error('notes') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <label class="flex cursor-pointer items-start gap-3">
                <input type="checkbox" class="mt-0.5 size-5 shrink-0 accent-brand" wire:model="is_public">
                <span>
                    <b class="block font-semibold">Terbitkan di Jelajahi</b>
                    <span class="block text-[13px] text-muted">Siapa saja bisa menemukan, menyimpan, dan menyalin resep ini. Catatan pribadimu tetap tidak terlihat. Bisa dibatalkan kapan saja.</span>
                    <span class="mt-1 block text-[13px] text-warn-fg">Terbitkan hanya resep tulisanmu sendiri, atau yang sudah diizinkan penulisnya. Resep salinan dari sosmed atau blog orang lain sebaiknya tetap pribadi.</span>
                </span>
            </label>
        </div>
    </section>

    <div class="sticky bottom-[84px] z-20 flex gap-2.5 rounded-2xl border border-line bg-frame p-3 shadow-[0_12px_32px_rgba(16,24,40,.14)] sm:static sm:justify-end sm:border-0 sm:bg-transparent sm:p-0 sm:shadow-none">
        <a href="{{ $recipe ? route('recipes.show', $recipe) : route('recipes.index') }}" class="btn flex-1 sm:flex-none" wire:navigate>Batal</a>
        <button type="submit" class="btn btn-primary flex-1 sm:flex-none" wire:loading.attr="disabled" wire:target="save,cover">
            <span wire:loading.remove wire:target="save">Simpan resep</span>
            <span wire:loading wire:target="save">Menyimpan…</span>
        </button>
    </div>
</form>

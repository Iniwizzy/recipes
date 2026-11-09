<div class="mx-auto flex max-w-3xl flex-col gap-4"
    x-data="{
        copied: false,
        async copy(text) {
            try {
                await navigator.clipboard.writeText(text);
            } catch (e) {
                const area = Object.assign(document.createElement('textarea'), { value: text });
                document.body.appendChild(area);
                area.select();
                document.execCommand('copy');
                area.remove();
            }
            this.copied = true;
            setTimeout(() => (this.copied = false), 2000);
        },
    }">

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-[-0.02em] lg:text-[28px]">Daftar belanja</h1>
            <p class="mt-1.5 text-[15px] text-ink-2">
                @if ($open->isEmpty() && $done->isEmpty())
                    Belum ada yang perlu dibeli.
                @else
                    {{ $open->count() }} barang belum dibeli{{ $done->isNotEmpty() ? ', '.$done->count().' sudah' : '' }}.
                @endif
            </p>
        </div>
        @if ($open->isNotEmpty())
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="btn" x-on:click="copy(@js($shareText))">
                    <flux:icon.clipboard-document variant="mini" />
                    <span x-text="copied ? 'Tersalin' : 'Salin daftar'">Salin daftar</span>
                </button>
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="btn">
                    <flux:icon.paper-airplane variant="mini" /> Kirim ke WhatsApp
                </a>
            </div>
        @endif
    </div>

    <form wire:submit="add" class="card flex flex-col gap-2 !p-4">
        <label for="line" class="field-label !mb-0">Tambah barang</label>
        <div class="flex gap-2">
            <input id="line" type="text" class="field" wire:model="line" placeholder="Contoh: 2 butir telur, 1 kg beras, garam" maxlength="150" autocomplete="off">
            <button type="submit" class="btn btn-primary shrink-0" wire:loading.attr="disabled" wire:target="add">
                <flux:icon.plus variant="mini" /> <span class="hidden sm:inline">Tambah</span>
            </button>
        </div>
        @error('line') <p class="field-error !mt-0">{{ $message }}</p> @enderror
        <p class="text-xs text-muted">Barang yang sama dengan satuan yang sama otomatis digabung, misalnya 2 siung + 3 siung bawang merah jadi 5 siung.</p>
    </form>

    @if ($open->isEmpty() && $done->isEmpty())
        <div class="rounded-[18px] border border-dashed border-line bg-card p-10 text-center">
            <span class="tile tint-2 mx-auto"><flux:icon.shopping-bag variant="mini" /></span>
            <p class="mt-3 font-semibold">Daftar belanja masih kosong</p>
            <p class="mt-1 text-sm text-muted">Buka resep, centang bahan yang sudah ada di dapur, lalu masukkan sisanya ke sini.</p>
            <a href="{{ route('recipes.index') }}" class="btn btn-primary mt-4" wire:navigate><flux:icon.book-open variant="mini" /> Pilih resep</a>
        </div>
    @endif

    @if ($open->isNotEmpty())
        <section class="card !px-4 !py-2 sm:!px-5">
            <h2 class="sr-only">Belum dibeli</h2>
            <ul>
                @foreach ($open as $item)
                    <li wire:key="open-{{ $item->id }}" class="flex items-center gap-3 border-t border-line-soft py-1 first:border-t-0">
                        <label class="flex min-h-12 min-w-0 flex-1 cursor-pointer items-center gap-3">
                            <input type="checkbox" class="size-5 shrink-0 accent-brand" wire:click="toggle({{ $item->id }})">
                            <span class="flex min-w-0 flex-col">
                                <span><b class="font-semibold tabular-nums">{{ $item->amount() }}</b> {{ $item->name }}</span>
                                @if ($item->sources)
                                    <span class="truncate text-xs text-muted">Untuk {{ implode(', ', $item->sources) }}</span>
                                @endif
                            </span>
                        </label>
                        <button type="button" class="ibtn !border-transparent !bg-transparent !shadow-none" wire:click="remove({{ $item->id }})" aria-label="Hapus {{ $item->name }} dari daftar">
                            <flux:icon.x-mark variant="mini" />
                        </button>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($done->isNotEmpty())
        <section class="card !px-4 !py-3 sm:!px-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-ink-2">Sudah dibeli ({{ $done->count() }})</h2>
                <button type="button" class="btn btn-sm" wire:click="clearChecked">Hapus yang sudah dibeli</button>
            </div>
            <ul class="mt-1">
                @foreach ($done as $item)
                    <li wire:key="done-{{ $item->id }}" class="border-t border-line-soft first:border-t-0">
                        <label class="flex min-h-11 cursor-pointer items-center gap-3 text-muted">
                            <input type="checkbox" class="size-5 shrink-0 accent-brand" checked wire:click="toggle({{ $item->id }})">
                            <span class="line-through"><span class="tabular-nums">{{ $item->amount() }}</span> {{ $item->name }}</span>
                        </label>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>

<div class="flex flex-col gap-4">
    <div>
        <h1 class="text-2xl font-semibold tracking-[-0.02em] lg:text-[28px]">Laporan resep</h1>
        <p class="mt-1.5 max-w-2xl text-[15px] text-ink-2">Resep yang dilaporkan pengguna. "Sembunyikan" mengeluarkan resep dari Jelajahi tanpa menghapusnya dari pemiliknya.</p>
    </div>

    @forelse ($open as $recipe)
        <section wire:key="report-{{ $recipe->id }}" class="card flex flex-col gap-3">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <a href="{{ route('recipes.show', $recipe) }}" class="card-title hover:text-brand" wire:navigate>{{ $recipe->title }}</a>
                    <p class="card-sub">oleh {{ $recipe->user?->name }} · {{ $recipe->reports->count() }} laporan terbuka</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-sm" wire:click="dismiss({{ $recipe->id }})">Abaikan</button>
                    <button type="button" class="btn btn-sm !border-crit-fg !bg-crit-fg !text-white" wire:click="hide({{ $recipe->id }})">
                        <flux:icon.eye-slash variant="micro" /> Sembunyikan
                    </button>
                </div>
            </div>
            <ul class="flex flex-col divide-y divide-line-soft rounded-xl border border-line-soft">
                @foreach ($recipe->reports as $report)
                    <li class="px-3 py-2.5 text-sm">
                        <span class="status st-warn">{{ $report->reason->label() }}</span>
                        <span class="ml-2 text-muted">{{ $report->user?->name }}, {{ $report->created_at->translatedFormat('j M, H.i') }}</span>
                        @if ($report->note)
                            <p class="mt-1.5 text-ink-2">{{ $report->note }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        <div class="rounded-[18px] border border-dashed border-line bg-card p-10 text-center">
            <span class="tile tint-3 mx-auto"><flux:icon.check variant="mini" /></span>
            <p class="mt-3 font-semibold">Tidak ada laporan terbuka</p>
        </div>
    @endforelse

    @if ($hidden->isNotEmpty())
        <section class="card">
            <h2 class="card-title">Sedang disembunyikan</h2>
            <ul class="mt-2 flex flex-col">
                @foreach ($hidden as $recipe)
                    <li wire:key="hidden-{{ $recipe->id }}" class="flex items-center justify-between gap-3 border-t border-line-soft py-2.5 first:border-t-0">
                        <span class="min-w-0">
                            <a href="{{ route('recipes.show', $recipe) }}" class="font-semibold hover:text-brand" wire:navigate>{{ $recipe->title }}</a>
                            <span class="block text-xs text-muted">oleh {{ $recipe->user?->name }}, sejak {{ $recipe->hidden_at->translatedFormat('j M') }}</span>
                        </span>
                        <button type="button" class="btn btn-sm shrink-0" wire:click="unhide({{ $recipe->id }})"><flux:icon.eye variant="micro" /> Tampilkan lagi</button>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>

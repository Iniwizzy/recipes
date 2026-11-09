<div class="mx-auto flex min-h-screen max-w-3xl flex-col" x-data="cookMode({ steps: @js($steps) })">

    <header class="sticky top-0 z-10 flex min-h-16 items-center gap-3 border-b border-line bg-frame px-4 py-2.5">
        <a href="{{ route('recipes.show', $recipe) }}" class="ibtn" aria-label="Keluar dari Mode Masak">
            <flux:icon.x-mark variant="mini" />
        </a>
        <div class="flex min-w-0 flex-1 flex-col">
            <b class="truncate text-[17px] font-semibold">{{ $recipe->title }}</b>
            <span class="text-xs text-muted">Mode Masak</span>
        </div>
        <span class="pr-1 text-[13px] font-semibold whitespace-nowrap text-ink-2" x-text="done ? 'Selesai' : (index + 1) + '/' + steps.length"></span>
    </header>

    @if (count($steps) === 0)
        <main class="flex flex-1 flex-col items-center justify-center gap-4 p-8 text-center">
            <p class="text-lg font-semibold">Resep ini belum punya langkah.</p>
            <a href="{{ route('recipes.edit', $recipe) }}" class="btn btn-primary">Tambah langkah</a>
        </main>
    @else
        <template x-if="!done">
            <div class="flex flex-1 flex-col">
                <main class="flex flex-1 flex-col gap-5 px-4 py-5 sm:px-8 sm:py-8">
                    <div class="grid gap-1" x-bind:style="'grid-template-columns: repeat(' + steps.length + ', minmax(0, 1fr))'" aria-hidden="true">
                        <template x-for="(s, i) in steps" :key="i">
                            <i class="h-[5px] rounded-full" x-bind:class="i <= index ? 'bg-brand' : 'bg-dot'"></i>
                        </template>
                    </div>

                    <p class="text-[13px] font-semibold tracking-[0.05em] text-brand uppercase" x-text="'Langkah ' + (index + 1) + ' dari ' + steps.length"></p>
                    <p class="text-2xl leading-snug font-medium tracking-[-0.01em] sm:text-[32px]" x-text="step.text" aria-live="polite"></p>

                    <div x-show="step.chips.length">
                        <p class="field-label">Siapkan</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="chip in step.chips" :key="chip">
                                <span class="chip !h-[34px] !px-3 !text-sm" x-text="chip"></span>
                            </template>
                        </div>
                    </div>

                    <section class="card flex flex-col items-center gap-3.5 !p-5" x-show="step.timer" aria-label="Timer langkah ini">
                        <span class="flex items-center gap-1.5 text-[13px] font-semibold text-warn-fg"><flux:icon.clock variant="micro" /> Timer langkah ini</span>
                        <span class="text-[56px] leading-none font-semibold tracking-[-0.02em] tabular-nums" role="timer" x-text="clock"></span>
                        <button type="button" class="btn btn-primary w-full max-w-sm" x-on:click="toggleTimer()">
                            <flux:icon.play variant="mini" x-show="!running" />
                            <flux:icon.pause variant="mini" x-show="running" />
                            <span x-text="timerLabel"></span>
                        </button>
                        <p class="text-center text-[13px] text-muted" x-text="timerHint"></p>
                    </section>

                    <p class="flex items-center gap-2 text-[13px] text-ink-2"><flux:icon.sun variant="micro" class="text-muted" /> Layar dijaga tetap menyala selama Mode Masak.</p>
                </main>

                <div class="sticky bottom-0 flex gap-2.5 border-t border-line bg-frame px-4 pt-3 pb-4">
                    <button type="button" class="btn flex-1" x-on:click="prev()" x-bind:disabled="isFirst">
                        <flux:icon.chevron-left variant="mini" /> Kembali
                    </button>
                    <button type="button" class="btn btn-primary flex-1" x-on:click="next()" x-bind:disabled="saving">
                        <span x-text="isLast ? 'Selesai' : 'Lanjut'"></span>
                        <flux:icon.chevron-right variant="mini" />
                    </button>
                </div>
            </div>
        </template>

        <template x-if="done">
            <main class="flex flex-1 flex-col items-center justify-center gap-3.5 px-6 py-10 text-center">
                <span class="flex size-[72px] items-center justify-center rounded-[22px] bg-ok-bg text-ok-fg"><flux:icon.check class="size-9" /></span>
                <h1 class="text-2xl font-semibold tracking-[-0.02em]">Selamat menikmati!</h1>
                <p class="max-w-sm text-ink-2">
                    {{ $recipe->title }} selesai dimasak.
                    <span x-show="timesCooked" x-text="'Tercatat sudah ' + timesCooked + ' kali dimasak.'"></span>
                </p>
                <div class="mt-2.5 flex w-full max-w-sm flex-col gap-2.5">
                    <a href="{{ route('recipes.show', $recipe) }}" class="btn btn-primary w-full">Kembali ke resep</a>
                    <a href="{{ route('dashboard') }}" class="btn w-full">Ke beranda</a>
                    <button type="button" class="btn w-full" x-on:click="restart()">Ulangi dari awal</button>
                </div>
            </main>
        </template>
    @endif
</div>

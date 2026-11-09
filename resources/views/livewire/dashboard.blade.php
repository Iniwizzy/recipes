<div class="flex flex-col gap-4">

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-[-0.02em] lg:text-[28px]">{{ $greeting }}, {{ $firstName }}</h1>
            <p class="mt-1.5 max-w-2xl text-[15px] text-ink-2">
                {{ now()->translatedFormat('l, j F') }}.
                @if ($weekCount)
                    {{ $weekCount }} menu dimasak dalam 7 hari terakhir, total {{ $weekHours }} di dapur.
                @else
                    Belum ada yang dimasak minggu ini. Pilih resep lalu tekan Mulai Masak.
                @endif
            </p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('meal-plan.index') }}" class="btn" wire:navigate>
                <flux:icon.calendar-days variant="mini" /> Rencana menu
            </a>
            <a href="{{ route('recipes.create') }}" class="btn btn-primary" wire:navigate>
                <flux:icon.plus variant="mini" /> Tulis resep
            </a>
        </div>
    </div>

    {{-- Today's menu from the meal plan --}}
    @if ($todayPlans->isNotEmpty())
        <section class="card !p-4 sm:!px-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="card-title">Menu hari ini</h2>
                <a href="{{ route('meal-plan.index') }}" class="text-sm font-semibold text-brand hover:underline" wire:navigate>Lihat rencana minggu ini</a>
            </div>
            <ul class="mt-3 grid gap-2.5 md:grid-cols-2 2xl:grid-cols-3">
                @foreach ($todayPlans as $plan)
                    @php $done = in_array($plan->recipe_id, $cookedToday, true); @endphp
                    <li class="flex min-w-0 items-center gap-3 rounded-xl border border-line-soft p-2.5">
                        <x-recipe-cover :recipe="$plan->recipe" thumb icon="size-5" class="size-11 shrink-0 rounded-xl" />
                        <a href="{{ route('recipes.show', $plan->recipe) }}" class="flex min-w-0 flex-1 flex-col" wire:navigate>
                            <span class="text-xs text-muted">{{ $plan->slot->label() }}, {{ $plan->servings }} porsi</span>
                            <span class="line-clamp-2 leading-snug font-semibold hover:text-brand">{{ $plan->recipe->title }}</span>
                        </a>
                        @if ($done)
                            <span class="status st-ok">Sudah</span>
                        @else
                            <a href="{{ route('recipes.cook', $plan->recipe) }}" class="btn btn-primary btn-sm !h-9 shrink-0" aria-label="Mulai masak {{ $plan->recipe->title }}">
                                <flux:icon.play variant="micro" /> Masak
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
        @foreach ($kpis as $kpi)
            <section class="card flex flex-col gap-3.5 !p-4 lg:!px-5 lg:!pt-[18px] lg:!pb-5">
                <div class="flex items-center gap-3 text-sm font-medium text-ink-2">
                    <span class="tile {{ $kpi['tint'] }} !size-9 lg:!size-10"><flux:icon :icon="$kpi['icon']" variant="outline" class="size-5" /></span>
                    <span class="leading-tight">{{ $kpi['label'] }}</span>
                </div>
                <div class="flex flex-wrap items-baseline justify-between gap-x-2 gap-y-1">
                    <span class="text-[26px] leading-none font-semibold tracking-[-0.02em] lg:text-[32px]">
                        {{ $kpi['value'] }}@if ($kpi['unit'])<small class="ml-1 text-[15px] font-medium tracking-normal text-muted">{{ $kpi['unit'] }}</small>@endif
                    </span>
                    @if ($kpi['delta'])
                        <span class="inline-flex items-center gap-1 text-[13px] font-semibold whitespace-nowrap {{ $kpi['delta']['up'] ? 'text-good' : 'text-muted' }}">
                            <flux:icon :icon="$kpi['delta']['up'] ? 'arrow-trending-up' : 'arrow-trending-down'" variant="micro" />
                            {{ $kpi['delta']['text'] }}
                        </span>
                    @endif
                </div>
                @if ($kpi['dots'])
                    <div class="hidden grid-flow-col grid-cols-[repeat(24,minmax(0,1fr))] grid-rows-[repeat(5,6px)] gap-y-1 sm:grid" role="img" aria-label="{{ $kpi['aria'] }}">
                        @foreach ($kpi['dots'] as $on)
                            <i class="size-1.5 justify-self-center rounded-full {{ $on ? $kpi['dotColor'] : 'bg-dot' }}"></i>
                        @endforeach
                    </div>
                @else
                    <p class="hidden text-[13px] leading-snug text-muted sm:block">{{ $kpi['note'] }}</p>
                @endif
            </section>
        @endforeach
    </div>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_400px]">

        {{-- Week chart --}}
        <section class="card" x-data="{ sel: null, table: false }">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="card-title">Waktu masak minggu ini</h2>
                    <p class="card-sub">Menit di dapur per hari, dipisah per kategori. Abu-abu adalah menu yang direncanakan. Arahkan kursor atau ketuk batang untuk detail.</p>
                </div>
                <button type="button" class="ibtn !size-10 !rounded-[10px]" x-on:click="table = !table" x-bind:aria-pressed="table" aria-label="Tampilkan sebagai tabel">
                    <flux:icon.table-cells variant="mini" />
                </button>
            </div>

            <div class="mt-3.5 flex flex-wrap gap-x-[18px] gap-y-1.5 text-[13px] text-ink-2">
                @foreach (\App\Enums\Category::cases() as $category)
                    <span class="inline-flex items-center gap-[7px]"><i class="size-2.5 rounded-[3px] {{ $category->swatch() }}"></i>{{ $category->label() }}</span>
                @endforeach
                <span class="inline-flex items-center gap-[7px]"><i class="size-2.5 rounded-[3px] bg-plan"></i>Direncanakan</span>
            </div>

            <div x-show="!table">
                @if ($chart['empty'])
                    <p class="mt-5 rounded-xl border border-dashed border-line p-8 text-center text-sm text-muted">
                        Belum ada yang dimasak atau direncanakan minggu ini.
                        <a href="{{ route('meal-plan.index') }}" class="font-semibold text-brand" wire:navigate>Buat rencana menu</a>
                    </p>
                @else
                    <div class="relative" x-on:mouseleave="sel = null">
                    <div class="relative mt-[18px] h-[236px]">
                        @foreach ($chart['ticks'] as $tick)
                            <div class="absolute right-0 left-9 h-0 border-t {{ $tick['value'] === 0 ? 'border-axis' : 'border-dashed border-grid' }}" style="bottom: {{ $tick['bottom'] }}px">
                                <span class="absolute top-[-9px] -left-9 w-7 text-right text-xs text-muted tabular-nums">{{ $tick['value'] }}</span>
                            </div>
                        @endforeach

                        <div class="absolute inset-y-0 right-0 left-9 grid grid-cols-7">
                            @foreach ($chart['days'] as $i => $day)
                                <button type="button"
                                    class="relative mx-0.5 flex h-full flex-col items-center justify-end rounded-[10px] sm:mx-1"
                                    x-bind:class="sel === {{ $i }} && 'bg-brand-tint'"
                                    x-on:mouseenter="sel = {{ $i }}" x-on:focus="sel = {{ $i }}" x-on:click="sel = {{ $i }}"
                                    aria-label="{{ $day['full'] }}: {{ $day['total'] }} menit dimasak{{ $day['planned'] ? ', '.$day['planned'].' menit direncanakan' : '' }}">
                                    @if ($day['today'])
                                        <span class="absolute top-1.5 left-1/2 flex h-6 -translate-x-1/2 items-center rounded-md bg-ink px-2 text-xs font-semibold whitespace-nowrap text-card">Hari ini</span>
                                    @endif
                                    <span class="flex w-[22px] flex-col-reverse gap-0.5 overflow-hidden rounded-t-[5px] sm:w-[38px]">
                                        @foreach ($day['segments'] as $segment)
                                            <i class="block w-full {{ $segment['swatch'] }}" style="height: {{ $segment['height'] }}px"></i>
                                        @endforeach
                                    </span>
                                    <span class="flex h-7 items-center text-xs {{ $day['today'] ? 'font-bold text-ink' : 'text-muted' }}">{{ $day['short'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                            {{-- Day detail: below the chart on phones, floating beside the bar from sm up --}}
                            @foreach ($chart['days'] as $i => $day)
                                <div x-cloak x-show="sel === {{ $i }}" role="status"
                                    class="pointer-events-none relative z-10 mt-3 rounded-xl border border-line bg-card px-3.5 py-3 sm:absolute sm:top-[24px] sm:mt-0 sm:w-[236px] sm:shadow-[0_12px_32px_rgba(16,24,40,.14)] sm:[left:var(--tip-left)] sm:[right:var(--tip-right)]"
                                    style="{{ $i <= 3
                                        ? '--tip-left: calc(42px + (100% - 36px) * '.round(($i + 1) / 7, 4).')'
                                        : '--tip-right: calc(6px + (100% - 36px) * '.round((7 - $i) / 7, 4).')' }}">
                                    <p class="mb-1.5 text-[13px] font-semibold">{{ $day['full'] }}{{ $day['total'] ? ', '.$day['total'].' menit' : '' }}</p>
                                    @foreach ($day['logs'] as $log)
                                        <div class="flex items-start gap-2 py-1 text-xs">
                                            <i class="mt-[3px] size-2.5 shrink-0 rounded-[3px] {{ $log['swatch'] }}"></i>
                                            <span class="flex min-w-0 flex-1 flex-col text-ink-2">{{ $log['category'] }}<small class="text-xs text-muted">{{ $log['title'] }}</small></span>
                                            <b class="font-semibold whitespace-nowrap tabular-nums">{{ $log['minutes'] }} mnt</b>
                                        </div>
                                    @endforeach
                                    @foreach ($day['plans'] as $plan)
                                        <div class="flex items-start gap-2 py-1 text-xs">
                                            <i class="mt-[3px] size-2.5 shrink-0 rounded-[3px] bg-plan"></i>
                                            <span class="flex min-w-0 flex-1 flex-col text-ink-2">Direncanakan, {{ strtolower($plan['slot']) }}<small class="text-xs text-muted">{{ $plan['title'] }}</small></span>
                                            <b class="font-semibold whitespace-nowrap tabular-nums">± {{ $plan['minutes'] }} mnt</b>
                                        </div>
                                    @endforeach
                                    @if (! $day['logs'] && ! $day['plans'])
                                        <p class="text-xs text-muted">Belum ada yang dimasak atau direncanakan.</p>
                                    @endif
                                </div>
                            @endforeach
                    </div>
                @endif
            </div>

            <div x-cloak x-show="table" class="mt-4 overflow-x-auto rounded-xl border border-line-soft text-[13px]" role="table" aria-label="Menit memasak per hari">
                <div class="grid min-w-[600px] grid-cols-[1.6fr_repeat(7,minmax(0,1fr))] bg-panel font-semibold text-ink-2" role="row">
                    <span class="px-2.5 py-2" role="columnheader">Hari</span>
                    @foreach (\App\Enums\Category::cases() as $category)
                        <span class="px-2.5 py-2 text-right" role="columnheader">{{ $category->label() }}</span>
                    @endforeach
                    <span class="px-2.5 py-2 text-right" role="columnheader">Total</span>
                    <span class="px-2.5 py-2 text-right" role="columnheader">Rencana</span>
                </div>
                @foreach ($chart['days'] as $day)
                    <div class="grid min-w-[600px] grid-cols-[1.6fr_repeat(7,minmax(0,1fr))] border-t border-line-soft tabular-nums" role="row">
                        <span class="px-2.5 py-2" role="cell">{{ $day['full'] }}</span>
                        @foreach (\App\Enums\Category::cases() as $category)
                            @php $part = collect($day['parts'])->firstWhere('category', $category); @endphp
                            <span class="px-2.5 py-2 text-right" role="cell">{{ $part['minutes'] ?? '–' }}</span>
                        @endforeach
                        <span class="px-2.5 py-2 text-right font-semibold" role="cell">{{ $day['total'] ?: '–' }}</span>
                        <span class="px-2.5 py-2 text-right text-muted" role="cell">{{ $day['planned'] ? '± '.$day['planned'] : '–' }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Recipes not cooked in a while --}}
        <section class="card">
            <h2 class="card-title">Lama tidak dimasak</h2>
            <p class="card-sub">Ide menu dari koleksimu sendiri</p>
            <div class="mt-2 flex flex-col">
                @forelse ($stale as $row)
                    <a href="{{ route('recipes.show', $row['recipe']) }}" wire:navigate class="group flex items-center gap-3.5 border-t border-line-soft py-3.5 first:border-t-0">
                        <x-recipe-cover :recipe="$row['recipe']" thumb icon="size-5" class="size-10 shrink-0 rounded-xl" />
                        <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                            <span class="truncate font-semibold group-hover:text-brand">{{ $row['recipe']->title }}</span>
                            <span class="truncate text-[13px] text-muted">{{ $row['sub'] }}</span>
                        </span>
                        <span class="status {{ $row['status'] }}">{{ $row['label'] }}</span>
                        <flux:icon.chevron-right variant="mini" class="text-muted" />
                    </a>
                @empty
                    <p class="py-6 text-center text-sm text-muted">Belum ada resep. <a href="{{ route('recipes.create') }}" class="font-semibold text-brand" wire:navigate>Tulis resep pertama</a></p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">

        {{-- Most cooked --}}
        <section class="card">
            <h2 class="card-title">Paling sering dimasak</h2>
            <p class="card-sub">30 hari terakhir</p>
            <ul class="mt-2.5 flex flex-col">
                @forelse ($top as $row)
                    <li>
                        <a href="{{ route('recipes.show', $row['recipe']) }}" wire:navigate class="group flex items-center gap-3 py-2.5">
                            <span class="tile tint-{{ $row['recipe']->category->slot() }} !rounded-full"><flux:icon.fire variant="mini" /></span>
                            <span class="flex min-w-0 flex-1 flex-col gap-2">
                                <span class="flex justify-between gap-2">
                                    <span class="truncate font-semibold group-hover:text-brand">{{ $row['recipe']->title }}</span>
                                    <span class="whitespace-nowrap text-ink-2 tabular-nums">{{ $row['count'] }}×</span>
                                </span>
                                <span class="meter"><span style="width: {{ $row['width'] }}%"></span></span>
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-muted">Belum ada catatan masak.</li>
                @endforelse
            </ul>
        </section>

        {{-- Collection per category --}}
        <section class="card">
            <h2 class="card-title">Koleksi per kategori</h2>
            <p class="card-sub">{{ $donut['total'] }} resep tersimpan</p>
            <div class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-center xl:flex-col xl:items-stretch">
                <div class="relative size-[140px] shrink-0 self-center rounded-full" role="img" aria-label="{{ $donut['aria'] }}" style="background: {{ $donut['gradient'] }}">
                    <div class="absolute inset-[22px] rounded-full bg-card"></div>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <b class="text-[26px] leading-tight font-semibold tracking-[-0.02em]">{{ $donut['total'] }}</b>
                        <span class="text-xs text-muted">resep</span>
                    </div>
                </div>
                <ul class="flex min-w-0 flex-1 flex-col">
                    @foreach ($donut['rows'] as $row)
                        <li class="border-t border-line-soft first:border-t-0">
                            <a href="{{ route('recipes.index', ['kategori' => $row['category']->value]) }}" wire:navigate
                                class="flex min-h-9 items-center gap-2.5 text-[13px] hover:text-brand">
                                <i class="size-2.5 shrink-0 rounded-[3px] {{ $row['category']->swatch() }}"></i>
                                <span class="min-w-0 flex-1 truncate text-ink-2">{{ $row['category']->label() }}</span>
                                <b class="font-semibold tabular-nums">{{ $row['count'] }}</b>
                                <span class="w-10 text-right text-xs text-muted tabular-nums">{{ $row['percent'] }}%</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- Activity --}}
        <section class="card md:col-span-2 xl:col-span-1">
            <h2 class="card-title">Aktivitas terbaru</h2>
            <p class="card-sub">Dari dapurmu</p>
            <ul class="mt-2.5 flex flex-col">
                @forelse ($activity as $item)
                    <li class="flex gap-3 py-2">
                        <span class="tile {{ $item['tint'] }} !size-9 !rounded-full"><flux:icon :icon="$item['icon']" variant="micro" /></span>
                        <div class="min-w-0">
                            <p class="leading-snug"><a href="{{ route('recipes.show', $item['recipe']) }}" wire:navigate class="font-semibold hover:underline">{{ $item['recipe']->title }}</a> {{ $item['verb'] }}</p>
                            <span class="mt-0.5 block text-xs text-muted">{{ $item['at']->translatedFormat('j M, H.i') }}{{ $item['meta'] ? ', '.$item['meta'] : '' }}</span>
                        </div>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-muted">Belum ada aktivitas.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>

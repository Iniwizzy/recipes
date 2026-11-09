@php
    $user = auth()->user();
    $recipeCount = $user->recipes()->count();
    $favoriteCount = $user->recipes()->where('is_favorite', true)->count();
    $shoppingCount = $user->shoppingListItems()->whereNull('checked_at')->count();
    $onFavorites = request()->routeIs('recipes.index') && request()->boolean('favorit');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-panel font-sans text-[15px] text-ink antialiased lg:bg-frame">
        <div class="mx-auto flex min-h-screen max-w-[1600px] flex-col lg:gap-4 lg:px-5 lg:pb-5">

            {{-- Top bar: stays at the top while the page scrolls (phone and desktop). --}}
            <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-line bg-frame px-4 lg:h-[76px] lg:border-0 lg:px-0 lg:pt-5">
                <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3 lg:w-[268px]" wire:navigate>
                    <x-app-logo />
                </a>

                <span class="hidden h-11 items-center gap-2.5 rounded-xl border border-line bg-card px-3.5 text-sm font-semibold lg:inline-flex">
                    <flux:icon.calendar-days variant="mini" class="text-muted" />
                    {{ now()->translatedFormat('D, j M Y') }}
                </span>

                <div class="flex-1"></div>

                <div class="hidden items-center gap-0.5 rounded-xl bg-chip p-[3px] sm:flex" role="group" aria-label="Tema tampilan" x-data>
                    <button type="button" class="flex size-[38px] items-center justify-center rounded-[9px] text-muted aria-pressed:bg-card aria-pressed:text-ink aria-pressed:shadow-sm"
                        aria-label="Tema terang" x-on:click="$flux.appearance = 'light'" x-bind:aria-pressed="!$flux.dark">
                        <flux:icon.sun variant="mini" />
                    </button>
                    <button type="button" class="flex size-[38px] items-center justify-center rounded-[9px] text-muted aria-pressed:bg-card aria-pressed:text-ink aria-pressed:shadow-sm"
                        aria-label="Tema gelap" x-on:click="$flux.appearance = 'dark'" x-bind:aria-pressed="$flux.dark">
                        <flux:icon.moon variant="mini" />
                    </button>
                </div>

                <flux:dropdown position="bottom" align="end">
                    <button type="button" class="flex size-11 items-center justify-center rounded-full bg-t3 text-sm font-bold text-i3" aria-label="Menu akun {{ $user->name }}">
                        {{ $user->initials() }}
                    </button>
                    <flux:menu class="w-[240px]">
                        <div class="px-2 py-1.5">
                            <p class="truncate text-sm font-semibold">{{ $user->name }}</p>
                            <p class="truncate text-xs text-muted">{{ $user->email }}</p>
                        </div>
                        <flux:menu.separator />
                        <flux:menu.item :href="route('settings.profile')" icon="cog-6-tooth" wire:navigate>Pengaturan</flux:menu.item>
                        <flux:menu.item x-on:click="$flux.appearance = $flux.dark ? 'light' : 'dark'" icon="moon" class="sm:hidden">Ganti tema</flux:menu.item>
                        <flux:menu.separator />
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">Keluar</flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </header>

            <div class="flex flex-1 lg:gap-4">

                {{-- Sidebar (desktop) --}}
                {{-- Sticks below the top bar: 76px bar + 16px gap, 20px bottom margin. --}}
                <nav class="hidden w-[268px] shrink-0 flex-col gap-0.5 overflow-y-auto rounded-[20px] bg-panel px-3.5 py-4 lg:sticky lg:top-[92px] lg:flex lg:h-[calc(100vh-112px)] lg:self-start" aria-label="Menu utama">
                    <form action="{{ route('recipes.index') }}" method="GET" role="search">
                        <label class="mb-1.5 flex h-11 items-center gap-2 rounded-xl border border-line bg-card pr-2 pl-3 text-muted">
                            <flux:icon.magnifying-glass variant="mini" />
                            <input type="search" name="q" value="{{ request()->routeIs('recipes.index') ? request('q') : '' }}" placeholder="Cari resep, bahan…" aria-label="Cari resep atau bahan"
                                class="min-w-0 flex-1 border-0 bg-transparent text-sm text-ink outline-none placeholder:text-muted">
                        </label>
                    </form>

                    <p class="mx-3 mt-3.5 mb-1 text-xs font-medium text-muted">Dapur</p>
                    <x-nav-item :href="route('dashboard')" icon="squares-2x2" :current="request()->routeIs('dashboard')">Beranda</x-nav-item>
                    <x-nav-item :href="route('recipes.index')" icon="book-open" :current="request()->routeIs('recipes.index', 'recipes.show', 'recipes.edit') && ! $onFavorites" :count="$recipeCount">Resep saya</x-nav-item>
                    <x-nav-item :href="route('recipes.index', ['favorit' => 1])" icon="heart" :current="$onFavorites" :count="$favoriteCount ?: null">Favorit</x-nav-item>
                    <x-nav-item :href="route('meal-plan.index')" icon="calendar-days" :current="request()->routeIs('meal-plan.*')">Rencana menu</x-nav-item>
                    <x-nav-item :href="route('shopping.index')" icon="shopping-bag" :current="request()->routeIs('shopping.*')" :count="$shoppingCount ?: null">Daftar belanja</x-nav-item>

                    <p class="mx-3 mt-3.5 mb-1 text-xs font-medium text-muted">Tambah resep</p>
                    <x-nav-item :href="route('recipes.create')" icon="pencil-square" :current="request()->routeIs('recipes.create')">Tulis resep</x-nav-item>

                    <div class="mt-auto flex flex-col gap-0.5 pt-3">
                        <x-nav-item :href="route('settings.profile')" icon="cog-6-tooth" :current="request()->routeIs('settings.*')">Pengaturan</x-nav-item>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex min-h-11 w-full items-center gap-3 rounded-xl px-3 text-left text-sm font-medium text-ink-2 hover:bg-hover hover:text-ink">
                                <flux:icon.arrow-right-start-on-rectangle variant="outline" class="size-5" />
                                Keluar
                            </button>
                        </form>
                    </div>
                </nav>

                <main class="min-w-0 flex-1 bg-panel px-4 pt-4 pb-28 lg:rounded-[20px] lg:p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>

        {{-- Bottom navigation (phone) --}}
        <nav class="fixed inset-x-0 bottom-0 z-30 grid h-[76px] grid-cols-5 items-center border-t border-line bg-frame px-2 pt-1.5 pb-3 lg:hidden" aria-label="Menu utama">
            <x-bottom-nav-item :href="route('dashboard')" icon="squares-2x2" :current="request()->routeIs('dashboard')">Beranda</x-bottom-nav-item>
            <x-bottom-nav-item :href="route('recipes.index')" icon="book-open" :current="request()->routeIs('recipes.index') && ! $onFavorites">Resep</x-bottom-nav-item>
            <a href="{{ route('recipes.create') }}" wire:navigate aria-label="Tulis resep baru"
                class="flex size-[52px] items-center justify-center justify-self-center rounded-2xl bg-brand text-on-brand shadow-[0_6px_16px_rgba(16,24,40,.2)]">
                <flux:icon.plus class="size-6" />
            </a>
            {{-- Favorites are a filter chip on the recipe list; the account lives in the avatar menu up top. --}}
            <x-bottom-nav-item :href="route('meal-plan.index')" icon="calendar-days" :current="request()->routeIs('meal-plan.*')">Rencana</x-bottom-nav-item>
            <x-bottom-nav-item :href="route('shopping.index')" icon="shopping-bag" :current="request()->routeIs('shopping.*')" :badge="$shoppingCount ?: null">Belanja</x-bottom-nav-item>
        </nav>

        @fluxScripts
    </body>
</html>

{{-- Layout for visitors who are not signed in: catalog, public recipes, cook profiles. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-panel font-sans text-[15px] text-ink antialiased">
        <header class="sticky top-0 z-30 border-b border-line bg-frame">
            <div class="mx-auto flex h-16 max-w-6xl items-center gap-3 px-4">
                <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3" wire:navigate>
                    <x-app-logo />
                </a>
                <nav class="ml-4 hidden items-center gap-1 sm:flex" aria-label="Menu utama">
                    <a href="{{ route('explore') }}" wire:navigate
                        @class(['rounded-lg px-3 py-2 text-sm font-semibold', 'text-brand' => request()->routeIs('explore'), 'text-ink-2 hover:text-ink' => ! request()->routeIs('explore')])>
                        Jelajahi resep
                    </a>
                </nav>
                <div class="flex-1"></div>
                <a href="{{ route('login') }}" class="btn btn-sm">Masuk</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Daftar</a>
                @endif
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 pt-5 pb-16">
            {{ $slot }}
        </main>

        <footer class="mx-auto max-w-6xl border-t border-line px-4 py-6 text-[13px] text-muted">
            {{ config('app.name') }}. Resep ditulis dan dibagikan oleh penggunanya.
        </footer>

        @fluxScripts
    </body>
</html>

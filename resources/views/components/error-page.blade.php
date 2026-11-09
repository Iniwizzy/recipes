@props(['code', 'title', 'message', 'icon' => 'bowl', 'action' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ $title }} · {{ config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css'])
        {{-- Follows the theme the user picked in the app (stored by Flux) or the device setting. --}}
        <script>
            (() => {
                const pick = localStorage.getItem('flux.appearance');
                const dark = pick === 'dark' || ((!pick || pick === 'system') && matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
            })();
        </script>
    </head>
    <body class="min-h-screen bg-panel font-sans text-ink antialiased">
        <main class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 text-center">
            <a href="{{ url('/') }}" class="flex items-center gap-3"><x-app-logo /></a>
            <div class="card flex w-full max-w-md flex-col items-center gap-3 !p-8">
                <span class="tile tint-2 !size-14 !rounded-2xl"><flux:icon :icon="$icon" class="size-7" /></span>
                <p class="text-[13px] font-semibold tracking-[0.05em] text-muted uppercase">Kode {{ $code }}</p>
                <h1 class="text-2xl font-semibold tracking-[-0.02em]">{{ $title }}</h1>
                <p class="text-ink-2">{{ $message }}</p>
                <div class="mt-3 flex flex-wrap justify-center gap-2.5">
                    @if ($action)
                        {{ $action }}
                    @endif
                    <a href="{{ auth()->check() ? route('dashboard') : url('/') }}" class="btn btn-primary">Ke beranda</a>
                </div>
            </div>
        </main>
    </body>
</html>

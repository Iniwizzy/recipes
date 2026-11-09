<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-panel font-sans text-ink antialiased">
        <div class="mx-auto flex min-h-screen max-w-5xl flex-col px-5 py-6">
            <header class="flex items-center justify-between gap-4">
                <span class="flex items-center gap-3"><x-app-logo /></span>
                <nav class="flex items-center gap-2">
                    <a href="{{ route('login') }}" class="btn">Masuk</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-primary">Daftar</a>
                    @endif
                </nav>
            </header>

            <main class="flex flex-1 flex-col justify-center gap-10 py-12">
                <div class="max-w-2xl">
                    <h1 class="text-4xl leading-tight font-semibold tracking-[-0.02em] sm:text-5xl">Semua resep andalanmu, rapi di satu tempat.</h1>
                    <p class="mt-4 text-lg text-ink-2">Simpan resep lengkap dengan bahan dan langkahnya, ubah porsi sekali tekan, lalu masak dengan Mode Masak: satu langkah per layar, timer jalan sendiri, layar tidak mati.</p>
                    <div class="mt-7 flex flex-wrap gap-3">
                        <a href="{{ route('register') }}" class="btn btn-primary !h-12 !px-6">Mulai gratis</a>
                        <a href="{{ route('explore') }}" class="btn !h-12 !px-6"><flux:icon.globe-alt variant="mini" /> Jelajahi resep</a>
                        <a href="{{ route('login') }}" class="btn !h-12 !px-6">Sudah punya akun</a>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="card">
                        <span class="tile tint-1"><flux:icon.book-open variant="mini" /></span>
                        <h2 class="card-title mt-3">Resep tersusun</h2>
                        <p class="card-sub">Bahan, langkah, catatan, dan video sumber dalam satu halaman.</p>
                    </div>
                    <div class="card">
                        <span class="tile tint-2"><flux:icon.users variant="mini" /></span>
                        <h2 class="card-title mt-3">Porsi otomatis</h2>
                        <p class="card-sub">Masak untuk 6 orang? Takaran bahan langsung menyesuaikan.</p>
                    </div>
                    <div class="card">
                        <span class="tile tint-3"><flux:icon.clock variant="mini" /></span>
                        <h2 class="card-title mt-3">Mode Masak</h2>
                        <p class="card-sub">Huruf besar, timer per langkah, dan catatan berapa kali sudah dimasak.</p>
                    </div>
                </div>
            </main>
        </div>
        @fluxScripts
    </body>
</html>

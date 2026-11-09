<div class="flex flex-col gap-4">
    <section class="card flex flex-wrap items-center gap-4">
        <span class="flex size-16 shrink-0 items-center justify-center rounded-full bg-t3 text-xl font-bold text-i3" aria-hidden="true">{{ $user->initials() }}</span>
        <div class="min-w-0 flex-1">
            <h1 class="text-2xl font-semibold tracking-[-0.02em]">{{ $user->name }}</h1>
            <p class="mt-0.5 text-sm text-muted">
                {{ '@'.$user->username }} · {{ $recipes->total() }} resep di Jelajahi · bergabung {{ $user->created_at->translatedFormat('F Y') }}
            </p>
        </div>
        @if ($isMe)
            <a href="{{ route('recipes.index') }}" class="btn" wire:navigate>Kelola resepku</a>
        @endif
    </section>

    @if ($recipes->isEmpty())
        <div class="rounded-[18px] border border-dashed border-line bg-card p-10 text-center">
            <p class="font-semibold">Belum ada resep yang diterbitkan</p>
            <p class="mt-1 text-sm text-muted">
                @if ($isMe)
                    Buka resepmu, tekan Ubah, lalu centang "Terbitkan di Jelajahi".
                @else
                    {{ $user->name }} belum membagikan resep.
                @endif
            </p>
        </div>
    @else
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:gap-4 xl:grid-cols-4">
            @foreach ($recipes as $recipe)
                <x-recipe-card :recipe="$recipe" byline wire:key="profile-{{ $recipe->id }}" />
            @endforeach
        </div>
        <div>{{ $recipes->links() }}</div>
    @endif
</div>

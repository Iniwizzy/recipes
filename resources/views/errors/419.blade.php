<x-error-page code="419" icon="clock" title="Sesi sudah habis"
    message="Halaman ini dibiarkan terbuka terlalu lama. Muat ulang halaman lalu coba lagi.">
    <x-slot:action><a href="{{ url()->previous() }}" class="btn">Muat ulang</a></x-slot:action>
</x-error-page>

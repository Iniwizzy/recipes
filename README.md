# Buku Resep

Aplikasi web untuk menyimpan resep dan tutorial masak. Dibangun dengan Laravel 12, Livewire 4, Flux, Tailwind CSS 4, dan MySQL.

## Fitur MVP

- **Akun**: daftar, masuk, lupa kata sandi, dan pengaturan profil.
- **Beranda (dashboard)**:
  - kartu ringkasan
  - grafik waktu masak 7 hari per kategori, dengan tampilan tabel
  - resep yang lama tidak dimasak
  - resep paling sering dimasak
  - koleksi per kategori
  - aktivitas terbaru
- **Resep saya**: cari berdasarkan judul, deskripsi, atau bahan, lalu saring per kategori atau favorit.
- **Detail resep**:
  - porsi bisa diubah dan takaran bahan ikut menyesuaikan (½, ⅓, ¼, …)
  - centang bahan yang sudah siap
  - catatan pribadi
  - link sumber, dengan video YouTube yang tampil langsung
  - tombol untuk memasukkan bahan yang belum dicentang ke daftar belanja, sesuai porsi
- **Daftar belanja**:
  - bahan yang sama digabung otomatis (2 siung + 3 siung bawang merah jadi 5 siung)
  - tambah barang dengan mengetik bebas, mis. `2 butir telur` atau `1kg beras`
  - centang saat belanja, lalu salin daftarnya atau kirim ke WhatsApp
- **Tulis/ubah resep**:
  - bahan dengan jumlah bebas tulis (`2`, `1,5`, `1/2`, `1 1/2`)
  - langkah yang bisa diurutkan ulang, masing-masing dengan timer opsional
  - foto sampul
- **Mode Masak**:
  - satu langkah per layar
  - timer per langkah dengan bunyi dan getar
  - layar dijaga tetap menyala
  - setiap kali selesai, sesi masak tercatat ke dashboard
- Tema terang dan gelap.

Tampilan mengikuti mockup "gaya dashboard" (desktop dan HP).

## Menjalankan di Laragon

Prasyarat: PHP 8.2+, Composer, Node 20+, dan MySQL (bawaan Laragon).

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Buat database `recipes` (dan `recipes_test` untuk tes) di MySQL, lalu:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
```

Buka `http://recipes.test`. Kalau Laragon membuat virtual host sebelum folder `public/` ada, klik **Reload** di Laragon supaya root-nya pindah ke `public/`. Bisa juga pakai server bawaan:

```bash
php artisan serve
```

Akun demo dari seeder: `demo@bukuresep.test` dengan kata sandi `password`. Akun ini hanya untuk lokal.

Saat mengubah tampilan, jalankan `npm run dev` supaya CSS dan JS terbarui otomatis.

## Tes

```bash
php artisan test
```

Tes memakai database MySQL `recipes_test` (lihat `phpunit.xml`).

## Struktur singkat

| Bagian | Lokasi |
|---|---|
| Halaman (Livewire) | `app/Livewire/Dashboard.php`, `app/Livewire/Recipes/*` |
| Tampilan | `resources/views/livewire/**`, layout di `resources/views/components/layouts/` |
| Model | `Recipe`, `Ingredient`, `Step`, `CookLog` di `app/Models` |
| Kategori dan tingkat | `app/Enums` |
| Format takaran | `app/Support/Quantity.php`, versi JS di `resources/js/quantity.js` |
| Mode Masak (timer, wake lock) | `resources/js/cook-mode.js` |
| Warna dan komponen UI | `resources/css/app.css` |

## Rencana berikutnya

- Rencana menu mingguan
- Koleksi resep
- Import resep dari teks atau link (TikTok, YouTube, blog)
- PWA dan offline untuk Mode Masak

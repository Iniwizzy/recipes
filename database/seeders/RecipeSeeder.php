<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    public function run(User $user): void
    {
        $recipes = [];
        foreach ($this->recipes() as $data) {
            $recipe = $user->recipes()->create([
                'title' => $data['title'],
                'description' => $data['description'],
                'category' => $data['category'],
                'difficulty' => $data['difficulty'],
                'servings' => $data['servings'],
                'prep_minutes' => $data['prep'],
                'cook_minutes' => $data['cook'],
                'source_url' => $data['source'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_favorite' => $data['favorite'] ?? false,
            ]);

            foreach ($data['ingredients'] as $i => $row) {
                $recipe->ingredients()->create([
                    'position' => $i,
                    'quantity' => $row[0],
                    'unit' => $row[1],
                    'name' => $row[2],
                    'note' => $row[3] ?? null,
                ]);
            }

            foreach ($data['steps'] as $i => $row) {
                $recipe->steps()->create([
                    'position' => $i,
                    'instruction' => $row[0],
                    'timer_seconds' => isset($row[1]) ? $row[1] * 60 : null,
                ]);
            }

            $recipes[] = $recipe;
        }

        $this->seedCookLogs($user, $recipes);
    }

    /**
     * About four weeks of cooking history so the dashboard has something to show.
     *
     * @param  array<int, Recipe>  $recipes
     */
    private function seedCookLogs(User $user, array $recipes): void
    {
        mt_srand(7);
        $byCategory = collect($recipes)->groupBy(fn (Recipe $r) => $r->category->value);
        $plan = [
            ['sarapan', 7, 70],
            ['makan_siang', 12, 35],
            ['makan_malam', 18, 85],
            ['camilan', 16, 30],
            ['minuman', 15, 15],
        ];

        for ($daysAgo = 27; $daysAgo >= 0; $daysAgo--) {
            foreach ($plan as [$category, $hour, $chance]) {
                if (mt_rand(1, 100) > $chance || ! $byCategory->has($category)) {
                    continue;
                }
                $cookedAt = now()->subDays($daysAgo)->setTime($hour, mt_rand(0, 50));
                if ($cookedAt->isFuture()) {
                    continue;
                }
                /** @var Recipe $recipe */
                $recipe = $byCategory[$category]->random();
                $minutes = max(5, (int) $recipe->totalMinutes() + mt_rand(-5, 5));

                $user->cookLogs()->create([
                    'recipe_id' => $recipe->id,
                    'cooked_at' => $cookedAt,
                    'minutes' => $minutes,
                    'servings' => $recipe->servings,
                ]);
            }
        }

        foreach ($recipes as $recipe) {
            $logs = $recipe->cookLogs();
            $recipe->forceFill([
                'cooked_count' => $logs->count(),
                'last_cooked_at' => $logs->max('cooked_at'),
            ])->save();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recipes(): array
    {
        return [
            [
                'title' => 'Nasi Goreng Kampung',
                'category' => 'sarapan',
                'difficulty' => 'mudah',
                'servings' => 2,
                'prep' => 10,
                'cook' => 15,
                'favorite' => true,
                'source' => 'https://www.tiktok.com/@namakreator/video/123456',
                'description' => 'Nasi goreng rumahan dengan bumbu ulek bawang dan cabai, kecap manis, dan telur orak-arik. Paling enak pakai nasi sisa semalam.',
                'notes' => 'Pakai nasi dingin dari kulkas, jangan nasi baru. Untuk anak-anak, kecap cukup 1 sdm dan cabainya dikurangi.',
                'ingredients' => [
                    [2, 'piring', 'nasi putih dingin'],
                    [2, 'butir', 'telur ayam'],
                    [4, 'siung', 'bawang merah'],
                    [2, 'siung', 'bawang putih'],
                    [3, 'buah', 'cabai merah keriting'],
                    [1.5, 'sdm', 'kecap manis'],
                    [0.5, 'sdt', 'garam'],
                    [2, 'sdm', 'minyak goreng'],
                    [null, null, 'daun bawang', 'iris tipis'],
                ],
                'steps' => [
                    ['Haluskan bawang merah, bawang putih, dan cabai merah dengan ulekan atau blender.'],
                    ['Panaskan minyak dengan api sedang. Tumis bumbu halus sampai harum dan warnanya lebih gelap.', 2],
                    ['Pinggirkan bumbu, masukkan telur, lalu orak-arik sampai setengah matang.'],
                    ['Masukkan nasi. Aduk sambil ditekan-tekan supaya tidak menggumpal dan bumbu merata.'],
                    ['Tambahkan kecap manis dan garam. Masak dengan api besar sambil terus diaduk.', 3],
                    ['Cicipi dan sesuaikan rasa. Taburi daun bawang, angkat, dan sajikan selagi hangat.'],
                ],
            ],
            [
                'title' => 'Telur Dadar Padang',
                'category' => 'sarapan',
                'difficulty' => 'mudah',
                'servings' => 3,
                'prep' => 10,
                'cook' => 10,
                'description' => 'Telur dadar tebal dan berongga ala rumah makan Padang, wangi daun bawang dan cabai.',
                'ingredients' => [
                    [4, 'butir', 'telur ayam'],
                    [2, 'sdm', 'tepung beras'],
                    [3, 'siung', 'bawang merah', 'iris'],
                    [2, 'buah', 'cabai merah', 'iris'],
                    [1, 'batang', 'daun bawang', 'iris'],
                    [0.5, 'sdt', 'garam'],
                    [200, 'ml', 'minyak goreng', 'untuk menggoreng'],
                ],
                'steps' => [
                    ['Kocok telur bersama tepung beras dan garam sampai berbusa.'],
                    ['Masukkan bawang merah, cabai, dan daun bawang. Aduk rata.'],
                    ['Panaskan minyak cukup banyak di wajan cekung sampai benar-benar panas.'],
                    ['Tuang adonan dari tengah. Goreng dengan api sedang sampai bagian bawah kokoh.', 4],
                    ['Balik pelan-pelan dan goreng sisi satunya sampai kecokelatan.', 3],
                ],
            ],
            [
                'title' => 'Sayur Asem',
                'category' => 'makan_siang',
                'difficulty' => 'mudah',
                'servings' => 4,
                'prep' => 15,
                'cook' => 25,
                'source' => 'https://www.tiktok.com/@dapurkreator/video/987654',
                'description' => 'Sayur berkuah asam segar dengan jagung, labu siam, kacang panjang, dan melinjo.',
                'ingredients' => [
                    [1, 'liter', 'air'],
                    [1, 'buah', 'jagung manis', 'potong-potong'],
                    [1, 'buah', 'labu siam', 'potong dadu'],
                    [1, 'ikat', 'kacang panjang', 'potong 4 cm'],
                    [50, 'gram', 'melinjo'],
                    [3, 'sdm', 'air asam jawa'],
                    [2, 'sdm', 'gula merah', 'sisir'],
                    [5, 'siung', 'bawang merah'],
                    [3, 'siung', 'bawang putih'],
                    [1, 'sdt', 'garam'],
                ],
                'steps' => [
                    ['Haluskan bawang merah dan bawang putih.'],
                    ['Didihkan air, masukkan bumbu halus, jagung, dan melinjo.', 10],
                    ['Masukkan labu siam dan kacang panjang. Masak sampai empuk.', 8],
                    ['Tambahkan air asam, gula merah, dan garam. Koreksi rasa, lalu angkat.'],
                ],
            ],
            [
                'title' => 'Gado-gado',
                'category' => 'makan_siang',
                'difficulty' => 'sedang',
                'servings' => 3,
                'prep' => 25,
                'cook' => 15,
                'description' => 'Sayuran rebus, tahu, tempe, dan telur dengan saus kacang yang gurih manis.',
                'ingredients' => [
                    [150, 'gram', 'kacang tanah goreng'],
                    [2, 'buah', 'cabai rawit'],
                    [2, 'siung', 'bawang putih'],
                    [2, 'sdm', 'gula merah'],
                    [1, 'sdm', 'air asam jawa'],
                    [200, 'gram', 'kol, tauge, dan kacang panjang'],
                    [2, 'potong', 'tahu goreng'],
                    [2, 'potong', 'tempe goreng'],
                    [2, 'butir', 'telur rebus'],
                ],
                'steps' => [
                    ['Rebus sayuran sebentar sampai matang tapi masih renyah, lalu tiriskan.', 3],
                    ['Ulek kacang, cabai, bawang putih, dan gula merah. Encerkan dengan air hangat dan air asam.'],
                    ['Tata sayuran, tahu, tempe, dan telur di piring.'],
                    ['Siram dengan saus kacang dan sajikan dengan kerupuk.'],
                ],
            ],
            [
                'title' => 'Ayam Bakar Kecap',
                'category' => 'makan_malam',
                'difficulty' => 'sedang',
                'servings' => 4,
                'prep' => 40,
                'cook' => 30,
                'favorite' => true,
                'description' => 'Ayam diungkep bumbu kuning lalu dibakar dengan olesan kecap manis dan mentega.',
                'notes' => 'Rendam ayam minimal 30 menit sebelum diungkep supaya bumbunya meresap.',
                'ingredients' => [
                    [1, 'ekor', 'ayam', 'potong 8'],
                    [6, 'siung', 'bawang merah'],
                    [4, 'siung', 'bawang putih'],
                    [2, 'cm', 'kunyit'],
                    [2, 'lembar', 'daun salam'],
                    [5, 'sdm', 'kecap manis'],
                    [1, 'buah', 'jeruk nipis'],
                    [1, 'sdm', 'mentega', 'untuk olesan'],
                    [300, 'ml', 'air'],
                ],
                'steps' => [
                    ['Lumuri ayam dengan air jeruk nipis, diamkan sebentar, lalu bilas.'],
                    ['Haluskan bawang merah, bawang putih, dan kunyit. Lumurkan ke ayam dan rendam.', 30],
                    ['Ungkep ayam dengan air dan daun salam sampai bumbu meresap dan air menyusut.', 25],
                    ['Campur kecap manis dan mentega untuk olesan.'],
                    ['Bakar ayam sambil dibolak-balik dan dioles bumbu kecap sampai harum.', 10],
                ],
            ],
            [
                'title' => 'Sop Ayam Bening',
                'category' => 'makan_malam',
                'difficulty' => 'mudah',
                'servings' => 4,
                'prep' => 15,
                'cook' => 30,
                'description' => 'Sop ayam kuah bening dengan wortel, kentang, dan seledri. Hangat untuk makan malam.',
                'ingredients' => [
                    [300, 'gram', 'ayam', 'potong kecil'],
                    [1.5, 'liter', 'air'],
                    [2, 'buah', 'wortel'],
                    [2, 'buah', 'kentang'],
                    [3, 'siung', 'bawang putih', 'geprek'],
                    [1, 'batang', 'daun bawang'],
                    [1, 'batang', 'seledri'],
                    [0.25, 'sdt', 'merica'],
                    [1, 'sdt', 'garam'],
                ],
                'steps' => [
                    ['Rebus ayam sampai mendidih, buang air pertama, lalu rebus lagi dengan air baru.', 15],
                    ['Tumis bawang putih sampai harum lalu masukkan ke rebusan.'],
                    ['Masukkan wortel dan kentang. Masak sampai empuk.', 12],
                    ['Bumbui dengan garam dan merica. Taburi daun bawang dan seledri, lalu angkat.'],
                ],
            ],
            [
                'title' => 'Pisang Goreng Kriuk',
                'category' => 'camilan',
                'difficulty' => 'mudah',
                'servings' => 4,
                'prep' => 10,
                'cook' => 10,
                'description' => 'Pisang kepok dengan balutan tepung renyah yang tetap kriuk walau sudah dingin.',
                'ingredients' => [
                    [6, 'buah', 'pisang kepok matang'],
                    [100, 'gram', 'tepung terigu'],
                    [2, 'sdm', 'tepung beras'],
                    [1, 'sdm', 'gula pasir'],
                    [150, 'ml', 'air es'],
                    [0.25, 'sdt', 'garam'],
                ],
                'steps' => [
                    ['Campur kedua tepung, gula, garam, dan air es sampai jadi adonan kental.'],
                    ['Belah pisang tipis lalu celupkan ke adonan.'],
                    ['Goreng dalam minyak panas dengan api sedang sampai kuning keemasan.', 5],
                ],
            ],
            [
                'title' => 'Es Cendol Gula Aren',
                'category' => 'minuman',
                'difficulty' => 'sedang',
                'servings' => 4,
                'prep' => 20,
                'cook' => 10,
                'description' => 'Cendol hijau pandan dengan santan gurih dan sirup gula aren.',
                'ingredients' => [
                    [100, 'gram', 'tepung hunkwe'],
                    [500, 'ml', 'air daun suji dan pandan'],
                    [200, 'gram', 'gula aren', 'sisir'],
                    [400, 'ml', 'santan'],
                    [0.5, 'sdt', 'garam'],
                    [null, null, 'es batu'],
                ],
                'steps' => [
                    ['Masak tepung hunkwe dengan air pandan sambil diaduk sampai mengental dan bening.', 6],
                    ['Cetak adonan dengan saringan cendol ke dalam air es.'],
                    ['Rebus gula aren dengan sedikit air sampai jadi sirup.', 5],
                    ['Rebus santan dengan garam sambil diaduk supaya tidak pecah.'],
                    ['Tata cendol, sirup gula aren, santan, dan es batu di gelas.'],
                ],
            ],
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Demo account for local development: demo@bukuresep.test / password
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Nadia Putri',
            'email' => 'demo@bukuresep.test',
        ]);

        $this->callWith(RecipeSeeder::class, ['user' => $user]);
    }
}

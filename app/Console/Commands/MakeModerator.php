<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeModerator extends Command
{
    protected $signature = 'resep:admin {email : Email akun} {--cabut : Cabut hak moderator}';

    protected $description = 'Jadikan akun sebagai moderator (meninjau laporan resep di Jelajahi), atau cabut haknya';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('Tidak ada akun dengan email itu.');

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => ! $this->option('cabut')])->save();
        $this->info($user->is_admin
            ? "{$user->name} sekarang moderator. Menu Laporan muncul di sidebar."
            : "Hak moderator {$user->name} dicabut.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Moderators review reported recipes. Grant with: php artisan resep:admin email@contoh.com
        Gate::define('moderate', fn (User $user) => $user->is_admin);
    }
}

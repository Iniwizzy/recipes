<?php

use App\Livewire\Admin;
use App\Livewire\Collections;
use App\Livewire\Dashboard;
use App\Livewire\Explore;
use App\Livewire\MealPlanner;
use App\Livewire\Profile;
use App\Livewire\Recipes;
use App\Livewire\ShoppingList;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('welcome');
})->name('home');

// Registered before the public routes so "resep/baru" is never read as a recipe slug.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('beranda', Dashboard::class)->name('dashboard');

    Route::get('resep', Recipes\Index::class)->name('recipes.index');
    Route::get('resep/baru', Recipes\Form::class)->name('recipes.create');
    Route::get('resep/{recipe}/ubah', Recipes\Form::class)->name('recipes.edit');
    Route::get('resep/{recipe}/masak', Recipes\Cook::class)->name('recipes.cook');

    Route::get('belanja', ShoppingList::class)->name('shopping.index');
    Route::get('rencana', MealPlanner::class)->name('meal-plan.index');

    Route::get('koleksi', Collections\Index::class)->name('collections.index');
    Route::get('koleksi/{collection}', Collections\Show::class)->name('collections.show');

    Route::get('admin/laporan', Admin\Reports::class)->middleware('can:moderate')->name('admin.reports');
});

// Public catalog: open to guests. Private recipes still need their owner to be signed in.
Route::get('jelajahi', Explore::class)->name('explore');
Route::get('resep/{recipe}', Recipes\Show::class)->name('recipes.show');
Route::get('u/{user}', Profile::class)->name('profile');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';

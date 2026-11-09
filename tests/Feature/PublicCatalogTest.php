<?php

namespace Tests\Feature;

use App\Enums\MealSlot;
use App\Livewire\Admin\Reports;
use App\Livewire\Explore;
use App\Livewire\MealPlanner;
use App\Livewire\Recipes\Show;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function publicRecipe(array $attributes = []): Recipe
    {
        return Recipe::factory()->withContent()->create(array_merge(['is_public' => true], $attributes));
    }

    public function test_guests_can_browse_the_catalog_and_read_public_recipes(): void
    {
        $author = User::factory()->create(['name' => 'Bu Sari']);
        $public = $this->publicRecipe(['user_id' => $author->id, 'title' => 'Soto Lamongan', 'notes' => 'Rahasia dapur saya']);
        $private = Recipe::factory()->for($author)->create(['title' => 'Resep Pribadi']);
        $hidden = $this->publicRecipe(['title' => 'Resep Disembunyikan', 'hidden_at' => now()]);

        $this->get('/jelajahi')->assertOk()
            ->assertSee('Soto Lamongan')->assertSee('oleh Bu Sari')
            ->assertDontSee('Resep Pribadi')->assertDontSee('Resep Disembunyikan');

        $this->get('/resep/'.$public->slug)->assertOk()
            ->assertSee('Soto Lamongan')
            ->assertSee('Masuk untuk menyimpan')
            ->assertDontSee('Rahasia dapur saya');

        $this->get('/resep/'.$private->slug)->assertRedirect('/login');
        $this->get('/resep/'.$hidden->slug)->assertRedirect('/login');
        $this->get('/u/'.$author->username)->assertOk()->assertSee('Soto Lamongan')->assertDontSee('Resep Pribadi');
    }

    public function test_publishing_sets_the_publish_date_and_every_user_gets_a_username(): void
    {
        $user = User::factory()->create(['name' => 'Nadia Putri']);
        $twin = User::factory()->create(['name' => 'Nadia Putri']);
        $this->assertSame('nadiaputri', $user->username);
        $this->assertSame('nadiaputri2', $twin->username);

        $recipe = Recipe::factory()->for($user)->create();
        $this->assertNull($recipe->published_at);
        $recipe->update(['is_public' => true]);
        $this->assertNotNull($recipe->fresh()->published_at);
    }

    public function test_catalog_filters_by_ingredients_category_and_time(): void
    {
        $egg = $this->publicRecipe(['title' => 'Telur Balado', 'category' => 'sarapan', 'prep_minutes' => 5, 'cook_minutes' => 10]);
        $egg->ingredients()->create(['position' => 9, 'name' => 'telur ayam']);
        $soup = $this->publicRecipe(['title' => 'Sop Iga', 'category' => 'makan_malam', 'prep_minutes' => 30, 'cook_minutes' => 90]);
        $soup->ingredients()->create(['position' => 9, 'name' => 'iga sapi']);

        Livewire::test(Explore::class)->set('have', 'telur')->assertSee('Telur Balado')->assertDontSee('Sop Iga');
        Livewire::test(Explore::class)->call('pickCategory', 'makan_malam')->assertSee('Sop Iga')->assertDontSee('Telur Balado');
        Livewire::test(Explore::class)->set('maxMinutes', 30)->assertSee('Telur Balado')->assertDontSee('Sop Iga');
    }

    public function test_a_public_recipe_can_be_saved_and_shows_up_in_saved_and_the_planner(): void
    {
        $recipe = $this->publicRecipe(['title' => 'Rendang Padang']);
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Show::class, ['recipe' => $recipe])->call('toggleSave');
        $this->assertTrue($user->savedRecipes()->whereKey($recipe->id)->exists());

        $this->get('/resep?disimpan=1')->assertOk()->assertSee('Rendang Padang');

        Livewire::test(MealPlanner::class)->call('openPicker', now()->toDateString(), 'makan_malam')->assertSee('Rendang Padang');

        Livewire::test(Show::class, ['recipe' => $recipe])->call('toggleSave');
        $this->assertFalse($user->savedRecipes()->whereKey($recipe->id)->exists());
    }

    public function test_owners_cannot_save_copy_or_report_their_own_recipe(): void
    {
        $recipe = $this->publicRecipe();
        $this->actingAs($recipe->user);

        Livewire::test(Show::class, ['recipe' => $recipe])->call('toggleSave')->assertForbidden();
        Livewire::test(Show::class, ['recipe' => $recipe])->call('fork')->assertForbidden();
        Livewire::test(Show::class, ['recipe' => $recipe])->call('report')->assertForbidden();
    }

    public function test_salin_and_ubah_makes_a_private_copy_with_credit(): void
    {
        $original = $this->publicRecipe(['title' => 'Gudeg Jogja']);
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Show::class, ['recipe' => $original])->call('fork');

        $copy = $user->recipes()->with('ingredients', 'steps')->sole();
        $this->assertSame('Gudeg Jogja', $copy->title);
        $this->assertSame($original->id, $copy->forked_from_id);
        $this->assertFalse($copy->is_public);
        $this->assertCount($original->ingredients()->count(), $copy->ingredients);
        $this->assertCount($original->steps()->count(), $copy->steps);

        $this->get('/resep/'.$copy->slug)->assertOk()->assertSee('Disalin dari');
    }

    public function test_a_recipe_can_be_planned_from_its_page(): void
    {
        $recipe = $this->publicRecipe();
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Show::class, ['recipe' => $recipe])
            ->set('planDate', now()->addDay()->toDateString())
            ->set('planSlot', 'makan_siang')
            ->set('planServings', 4)
            ->call('planRecipe')
            ->assertHasNoErrors();

        $plan = $user->mealPlans()->sole();
        $this->assertSame(MealSlot::MakanSiang, $plan->slot);
        $this->assertSame(4, $plan->servings);

        Livewire::test(Show::class, ['recipe' => $recipe])
            ->set('planDate', now()->subDay()->toDateString())
            ->call('planRecipe')
            ->assertHasErrors('planDate');
    }

    public function test_reports_reach_moderators_who_can_hide_the_recipe(): void
    {
        $recipe = $this->publicRecipe(['title' => 'Resep Bermasalah']);
        $reporter = User::factory()->create();
        $this->actingAs($reporter);

        Livewire::test(Show::class, ['recipe' => $recipe])
            ->set('reportReason', 'hak_cipta')->set('reportNote', 'Disalin dari blog')
            ->call('report')->assertSet('reportSent', true);
        $this->assertSame(1, $recipe->reports()->whereNull('resolved_at')->count());

        $this->get('/admin/laporan')->assertForbidden();

        $moderator = User::factory()->create(['is_admin' => true]);
        $this->actingAs($moderator);
        $this->get('/admin/laporan')->assertOk()->assertSee('Resep Bermasalah')->assertSee('Disalin dari blog');

        Livewire::test(Reports::class)->call('hide', $recipe->id);
        $this->assertNotNull($recipe->fresh()->hidden_at);
        $this->assertSame(0, $recipe->reports()->whereNull('resolved_at')->count());

        $this->actingAs($reporter);
        $this->get('/resep/'.$recipe->slug)->assertForbidden();
        $this->actingAs($recipe->user);
        $this->get('/resep/'.$recipe->slug)->assertOk()->assertSee('disembunyikan dari Jelajahi');
    }

    public function test_moderator_command_grants_and_revokes(): void
    {
        $user = User::factory()->create(['email' => 'mod@contoh.test']);

        $this->artisan('resep:admin', ['email' => 'mod@contoh.test'])->assertSuccessful();
        $this->assertTrue($user->fresh()->is_admin);

        $this->artisan('resep:admin', ['email' => 'mod@contoh.test', '--cabut' => true])->assertSuccessful();
        $this->assertFalse($user->fresh()->is_admin);

        $this->artisan('resep:admin', ['email' => 'tidak@ada.test'])->assertFailed();
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Recipes\Cook;
use App\Livewire\Recipes\Form;
use App\Livewire\Recipes\Index;
use App\Livewire\Recipes\Show;
use App\Models\Recipe;
use App\Models\User;
use App\Support\Quantity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecipeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_see_recipes(): void
    {
        $this->get('/resep')->assertRedirect('/login');
    }

    public function test_pages_render_for_the_owner(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->withContent()->for($user)->create(['title' => 'Sayur Asem']);
        $this->actingAs($user);

        $this->get('/beranda')->assertOk()->assertSee('Sayur Asem');
        $this->get('/resep')->assertOk()->assertSee('Sayur Asem');
        $this->get('/resep/baru')->assertOk();
        $this->get('/resep/'.$recipe->slug)->assertOk()->assertSee('Mulai Masak');
        $this->get('/resep/'.$recipe->slug.'/ubah')->assertOk();
        $this->get('/resep/'.$recipe->slug.'/masak')->assertOk()->assertSee('Mode Masak');
    }

    public function test_other_users_cannot_open_a_private_recipe(): void
    {
        $recipe = Recipe::factory()->withContent()->create();
        $this->actingAs(User::factory()->create());

        $this->get('/resep/'.$recipe->slug)->assertForbidden();
        $this->get('/resep/'.$recipe->slug.'/ubah')->assertForbidden();
    }

    public function test_public_recipes_are_readable_but_not_editable_by_others(): void
    {
        $recipe = Recipe::factory()->withContent()->create(['is_public' => true]);
        $this->actingAs(User::factory()->create());

        $this->get('/resep/'.$recipe->slug)->assertOk()->assertDontSee('Jadikan favorit');
        $this->get('/resep/'.$recipe->slug.'/ubah')->assertForbidden();
    }

    public function test_a_recipe_can_be_created_with_ingredients_and_steps(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->set('title', 'Nasi Goreng Kampung')
            ->set('category', 'sarapan')
            ->set('servings', 2)
            ->set('ingredients', [
                ['quantity' => '2', 'unit' => 'piring', 'name' => 'nasi putih', 'note' => ''],
                ['quantity' => '1,5', 'unit' => 'sdm', 'name' => 'kecap manis', 'note' => ''],
                ['quantity' => '', 'unit' => '', 'name' => 'daun bawang', 'note' => 'iris'],
                ['quantity' => '', 'unit' => '', 'name' => '', 'note' => ''],
            ])
            ->set('steps', [
                ['instruction' => 'Tumis bumbu sampai harum.', 'timer' => '2'],
                ['instruction' => '', 'timer' => ''],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('recipes.show', 'nasi-goreng-kampung'));

        $recipe = $user->recipes()->with('ingredients', 'steps')->sole();
        $this->assertSame('nasi-goreng-kampung', $recipe->slug);
        $this->assertCount(3, $recipe->ingredients);
        $this->assertSame(1.5, $recipe->ingredients[1]->quantity);
        $this->assertNull($recipe->ingredients[2]->quantity);
        $this->assertCount(1, $recipe->steps);
        $this->assertSame(120, $recipe->steps[0]->timer_seconds);
    }

    public function test_validation_blocks_missing_title_and_unreadable_amounts(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Form::class)
            ->set('title', '')
            ->set('ingredients', [['quantity' => 'banyak', 'unit' => '', 'name' => 'garam', 'note' => '']])
            ->set('steps', [['instruction' => 'Aduk.', 'timer' => '']])
            ->call('save')
            ->assertHasErrors(['title', 'ingredients.0.quantity']);
    }

    public function test_editing_keeps_the_slug_unique(): void
    {
        $user = User::factory()->create();
        Recipe::factory()->for($user)->create(['title' => 'Soto Ayam']);
        $recipe = Recipe::factory()->withContent()->for($user)->create(['title' => 'Soto Betawi']);
        $this->actingAs($user);

        Livewire::test(Form::class, ['recipe' => $recipe])
            ->set('title', 'Soto Ayam')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('soto-ayam-2', $recipe->fresh()->slug);
    }

    public function test_owner_can_favorite_and_delete(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->withContent()->for($user)->create();
        $this->actingAs($user);

        Livewire::test(Show::class, ['recipe' => $recipe])->call('toggleFavorite');
        $this->assertTrue($recipe->fresh()->is_favorite);

        Livewire::test(Show::class, ['recipe' => $recipe])->call('delete')->assertRedirect(route('recipes.index'));
        $this->assertModelMissing($recipe);
    }

    public function test_finishing_cook_mode_logs_the_session(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->withContent()->for($user)->create();
        $this->actingAs($user);

        Livewire::test(Cook::class, ['recipe' => $recipe])
            ->call('finish', 27)
            ->assertSet('timesCooked', 1);

        $this->assertSame(1, $recipe->fresh()->cooked_count);
        $this->assertDatabaseHas('cook_logs', ['recipe_id' => $recipe->id, 'user_id' => $user->id, 'minutes' => 27]);
    }

    public function test_search_matches_ingredients(): void
    {
        $user = User::factory()->create();
        $match = Recipe::factory()->for($user)->create(['title' => 'Tumis Kangkung']);
        $match->ingredients()->create(['position' => 0, 'name' => 'terasi bakar']);
        Recipe::factory()->for($user)->create(['title' => 'Es Teh']);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('search', 'terasi')
            ->assertSee('Tumis Kangkung')
            ->assertDontSee('Es Teh');
    }

    public function test_quantities_parse_and_format_like_people_write_them(): void
    {
        $this->assertSame(1.5, Quantity::parse('1,5'));
        $this->assertSame(0.5, Quantity::parse('1/2'));
        $this->assertSame(1.5, Quantity::parse('1 1/2'));
        $this->assertNull(Quantity::parse('banyak'));
        $this->assertSame('1 ½', Quantity::format(1.5));
        $this->assertSame('⅓', Quantity::format(1 / 3));
        $this->assertSame('2,4', Quantity::format(2.4));
        $this->assertSame('3', Quantity::format(3.0));
    }
}

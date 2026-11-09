<?php

namespace Tests\Feature;

use App\Livewire\Collections\Index;
use App\Livewire\Collections\Show;
use App\Livewire\Recipes\Show as RecipeShow;
use App\Models\Recipe;
use App\Models\RecipeCollection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_require_login_and_render(): void
    {
        $this->get('/koleksi')->assertRedirect('/login');

        $user = User::factory()->create();
        $collection = $user->recipeCollections()->create(['name' => 'Menu anak']);
        $collection->recipes()->attach(Recipe::factory()->for($user)->create(['title' => 'Telur Dadar'])->id);
        $this->actingAs($user);

        $this->get('/koleksi')->assertOk()->assertSee('Menu anak')->assertSee('1 resep');
        $this->get('/koleksi/'.$collection->id)->assertOk()->assertSee('Telur Dadar');
    }

    public function test_a_collection_is_created_with_a_unique_name_per_user(): void
    {
        $user = User::factory()->create();
        User::factory()->create()->recipeCollections()->create(['name' => 'Bekal']);
        $this->actingAs($user);

        Livewire::test(Index::class)->set('name', '  Bekal ')->call('create')->assertHasNoErrors();
        $this->assertSame('Bekal', $user->recipeCollections()->sole()->name);

        Livewire::test(Index::class)->set('name', 'Bekal')->call('create')->assertHasErrors(['name' => 'unique']);
        Livewire::test(Index::class)->set('name', '')->call('create')->assertHasErrors(['name' => 'required']);
    }

    public function test_recipes_are_saved_to_collections_from_the_recipe_page(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for($user)->create();
        $collection = $user->recipeCollections()->create(['name' => 'Lebaran']);
        $this->actingAs($user);

        $page = Livewire::test(RecipeShow::class, ['recipe' => $recipe])->call('toggleCollection', $collection->id);
        $this->assertTrue($collection->recipes()->whereKey($recipe->id)->exists());

        $page->call('toggleCollection', $collection->id);
        $this->assertFalse($collection->recipes()->whereKey($recipe->id)->exists());

        $page->set('newCollection', 'Menu anak')->call('createCollection')->assertHasNoErrors();
        $this->assertSame([$recipe->id], $user->recipeCollections()->where('name', 'Menu anak')->sole()->recipes->pluck('id')->all());
    }

    public function test_a_public_recipe_from_someone_else_can_be_saved_but_a_private_one_cannot(): void
    {
        $user = User::factory()->create();
        $collection = $user->recipeCollections()->create(['name' => 'Ide']);
        $public = Recipe::factory()->create(['is_public' => true]);
        $private = Recipe::factory()->create();
        $this->actingAs($user);

        Livewire::test(RecipeShow::class, ['recipe' => $public])->call('toggleCollection', $collection->id);
        $this->assertTrue($collection->recipes()->whereKey($public->id)->exists());

        $this->get('/resep/'.$private->slug)->assertForbidden();
    }

    public function test_collections_belong_to_their_owner(): void
    {
        $owner = User::factory()->create();
        $collection = $owner->recipeCollections()->create(['name' => 'Rahasia']);
        $intruder = User::factory()->create();
        $this->actingAs($intruder);

        $this->get('/koleksi/'.$collection->id)->assertNotFound();

        $recipe = Recipe::factory()->for($intruder)->create();
        Livewire::test(RecipeShow::class, ['recipe' => $recipe])->call('toggleCollection', $collection->id)->assertNotFound();
        $this->assertSame(0, $collection->recipes()->count());
    }

    public function test_rename_remove_and_delete_keep_the_recipes(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for($user)->create();
        $collection = $user->recipeCollections()->create(['name' => 'Lama']);
        $collection->recipes()->attach($recipe->id);
        $this->actingAs($user);

        Livewire::test(Show::class, ['collection' => $collection])
            ->set('name', 'Baru')->call('rename')->assertHasNoErrors()
            ->call('removeRecipe', $recipe->id);

        $this->assertSame('Baru', $collection->fresh()->name);
        $this->assertSame(0, $collection->recipes()->count());

        Livewire::test(Show::class, ['collection' => $collection->fresh()])->call('delete')->assertRedirect(route('collections.index'));
        $this->assertModelMissing($collection);
        $this->assertModelExists($recipe);
    }

    public function test_deleting_a_recipe_removes_it_from_collections(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for($user)->create();
        $collection = $user->recipeCollections()->create(['name' => 'Favorit keluarga']);
        $collection->recipes()->attach($recipe->id);
        $this->actingAs($user);

        Livewire::test(RecipeShow::class, ['recipe' => $recipe])->call('delete');

        $this->assertModelExists($collection);
        $this->assertSame(0, RecipeCollection::find($collection->id)->recipes()->count());
    }
}

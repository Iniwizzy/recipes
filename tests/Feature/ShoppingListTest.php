<?php

namespace Tests\Feature;

use App\Livewire\Recipes\Show;
use App\Livewire\ShoppingList;
use App\Models\Recipe;
use App\Models\User;
use App\Support\ShoppingList as ShoppingListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShoppingListTest extends TestCase
{
    use RefreshDatabase;

    private function recipeWithIngredients(User $user, string $title = 'Nasi Goreng'): Recipe
    {
        $recipe = Recipe::factory()->for($user)->create(['title' => $title, 'servings' => 2]);
        $recipe->ingredients()->createMany([
            ['position' => 0, 'quantity' => 2, 'unit' => 'piring', 'name' => 'nasi putih'],
            ['position' => 1, 'quantity' => 4, 'unit' => 'siung', 'name' => 'bawang merah'],
            ['position' => 2, 'quantity' => 0.5, 'unit' => 'sdt', 'name' => 'garam'],
            ['position' => 3, 'quantity' => null, 'unit' => null, 'name' => 'daun bawang'],
        ]);

        return $recipe;
    }

    public function test_page_requires_login_and_renders_empty_state(): void
    {
        $this->get('/belanja')->assertRedirect('/login');

        $this->actingAs(User::factory()->create());
        $this->get('/belanja')->assertOk()->assertSee('Daftar belanja masih kosong');
    }

    public function test_unticked_ingredients_are_added_scaled_to_the_chosen_portions(): void
    {
        $user = User::factory()->create();
        $recipe = $this->recipeWithIngredients($user);
        $this->actingAs($user);

        // 3 portions instead of 2; "nasi putih" (index 0) is already in the kitchen.
        Livewire::test(Show::class, ['recipe' => $recipe])
            ->call('addToShoppingList', 3, [1, 2, 3])
            ->assertSet('addedMessage', '3 bahan masuk daftar belanja.');

        $items = $user->shoppingListItems()->orderBy('id')->get();
        $this->assertSame(['bawang merah', 'garam', 'daun bawang'], $items->pluck('name')->all());
        $this->assertSame('6 siung', $items[0]->amount());
        $this->assertSame('¾ sdt', $items[1]->amount());
        $this->assertNull($items[2]->quantity);
        $this->assertSame(['Nasi Goreng'], $items[0]->sources);
    }

    public function test_same_ingredient_from_two_recipes_is_merged(): void
    {
        $user = User::factory()->create();
        $first = $this->recipeWithIngredients($user, 'Nasi Goreng');
        $second = $this->recipeWithIngredients($user, 'Sayur Asem');
        $this->actingAs($user);

        Livewire::test(Show::class, ['recipe' => $first])->call('addToShoppingList', 2, [1]);
        Livewire::test(Show::class, ['recipe' => $second])->call('addToShoppingList', 2, [1]);

        $item = $user->shoppingListItems()->sole();
        $this->assertSame('8 siung', $item->amount());
        $this->assertSame(['Nasi Goreng', 'Sayur Asem'], $item->sources);
    }

    public function test_items_only_merge_when_unit_and_amount_kind_match(): void
    {
        $user = User::factory()->create();

        ShoppingListService::add($user, [
            ['name' => 'Telur', 'quantity' => 2, 'unit' => 'butir'],
            ['name' => 'telur', 'quantity' => 3, 'unit' => 'butir'],
            ['name' => 'telur', 'quantity' => 1, 'unit' => 'kg'],
            ['name' => 'telur', 'quantity' => null, 'unit' => null],
        ]);

        $this->assertSame(
            ['5 butir', '1 kg', ''],
            $user->shoppingListItems()->orderBy('id')->get()->map->amount()->all()
        );
    }

    public function test_typed_lines_are_read_as_amount_unit_and_name(): void
    {
        $this->assertSame(['name' => 'telur', 'quantity' => 2.0, 'unit' => 'butir'], ShoppingListService::parseLine('2 butir telur'));
        $this->assertSame(['name' => 'ayam', 'quantity' => 1.5, 'unit' => 'kg'], ShoppingListService::parseLine('1,5 Kg ayam'));
        $this->assertSame(['name' => 'garam', 'quantity' => 0.5, 'unit' => 'sdt'], ShoppingListService::parseLine('1/2 sdt garam'));
        $this->assertSame(['name' => 'tomat', 'quantity' => 3.0, 'unit' => null], ShoppingListService::parseLine('3 tomat'));
        $this->assertSame(['name' => 'garam', 'quantity' => null, 'unit' => null], ShoppingListService::parseLine('  garam '));
        $this->assertSame(['name' => '7up', 'quantity' => null, 'unit' => null], ShoppingListService::parseLine('7up'));
        $this->assertSame(['name' => 'ayam', 'quantity' => 2.0, 'unit' => 'kg'], ShoppingListService::parseLine('2kg ayam'));
        $this->assertSame(['name' => 'gula', 'quantity' => 1.5, 'unit' => 'sdm'], ShoppingListService::parseLine('1 1/2 sdm gula'));
    }

    public function test_items_can_be_added_ticked_and_cleared(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = Livewire::test(ShoppingList::class)
            ->set('line', '2 butir telur')
            ->call('add')
            ->assertHasNoErrors()
            ->assertSet('line', '')
            ->assertSee('telur');

        $item = $user->shoppingListItems()->sole();
        $page->call('toggle', $item->id);
        $this->assertNotNull($item->fresh()->checked_at);

        $page->call('clearChecked');
        $this->assertSame(0, $user->shoppingListItems()->count());

        Livewire::test(ShoppingList::class)->set('line', '')->call('add')->assertHasErrors('line');
    }

    public function test_users_cannot_touch_someone_elses_items(): void
    {
        $owner = User::factory()->create();
        ShoppingListService::add($owner, [['name' => 'gula', 'quantity' => 1, 'unit' => 'kg']]);
        $item = $owner->shoppingListItems()->sole();

        $this->actingAs(User::factory()->create());
        Livewire::test(ShoppingList::class)->call('remove', $item->id)->assertNotFound();
        Livewire::test(ShoppingList::class)->call('toggle', $item->id)->assertNotFound();

        $this->assertModelExists($item);
        $this->assertNull($item->fresh()->checked_at);
    }
}

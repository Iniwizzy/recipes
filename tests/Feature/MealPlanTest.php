<?php

namespace Tests\Feature;

use App\Enums\MealSlot;
use App\Livewire\MealPlanner;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class MealPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Wednesday; the week runs Monday 9 Nov to Sunday 15 Nov.
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function recipe(User $user, string $title, array $ingredients = [], int $servings = 2): Recipe
    {
        $recipe = Recipe::factory()->for($user)->create(['title' => $title, 'servings' => $servings, 'prep_minutes' => 10, 'cook_minutes' => 20]);
        foreach ($ingredients as $i => [$quantity, $unit, $name]) {
            $recipe->ingredients()->create(['position' => $i, 'quantity' => $quantity, 'unit' => $unit, 'name' => $name]);
        }

        return $recipe;
    }

    public function test_page_requires_login_and_shows_the_week(): void
    {
        $this->get('/rencana')->assertRedirect('/login');

        $user = User::factory()->create();
        $recipe = $this->recipe($user, 'Sayur Asem');
        $user->mealPlans()->create(['recipe_id' => $recipe->id, 'date' => '2026-11-12', 'slot' => MealSlot::MakanSiang, 'servings' => 4]);
        $this->actingAs($user);

        $this->get('/rencana')->assertOk()
            ->assertSee('9 Nov – 15 Nov 2026')
            ->assertSee('Sayur Asem')
            ->assertSee('4 porsi');
    }

    public function test_a_recipe_can_be_planned_from_the_picker(): void
    {
        $user = User::factory()->create();
        $recipe = $this->recipe($user, 'Nasi Goreng');
        $this->actingAs($user);

        Livewire::test(MealPlanner::class)
            ->call('openPicker', '2026-11-13', 'sarapan')
            ->assertSet('pickerOpen', true)
            ->assertSee('Nasi Goreng')
            ->call('adjustPickerServings', 1)
            ->call('pick', $recipe->id)
            ->assertSet('pickerOpen', false);

        $plan = $user->mealPlans()->sole();
        $this->assertSame('2026-11-13', $plan->date->toDateString());
        $this->assertSame(MealSlot::Sarapan, $plan->slot);
        $this->assertSame(3, $plan->servings);
    }

    public function test_someone_elses_private_recipe_cannot_be_planned(): void
    {
        $other = $this->recipe(User::factory()->create(), 'Rahasia');
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(MealPlanner::class)
            ->call('openPicker', '2026-11-13', 'sarapan')
            ->call('pick', $other->id)
            ->assertForbidden();

        $this->assertSame(0, $user->mealPlans()->count());
    }

    public function test_portions_change_and_plans_are_removed_only_by_their_owner(): void
    {
        $owner = User::factory()->create();
        $plan = $owner->mealPlans()->create(['recipe_id' => $this->recipe($owner, 'Soto')->id, 'date' => '2026-11-12', 'slot' => MealSlot::MakanMalam, 'servings' => 2]);

        $this->actingAs(User::factory()->create());
        Livewire::test(MealPlanner::class)->call('remove', $plan->id)->assertNotFound();

        $this->actingAs($owner);
        Livewire::test(MealPlanner::class)->call('adjustServings', $plan->id, 2)->call('adjustServings', $plan->id, -10);
        $this->assertSame(1, $plan->fresh()->servings);

        Livewire::test(MealPlanner::class)->call('remove', $plan->id);
        $this->assertModelMissing($plan);
    }

    public function test_week_navigation_uses_the_url(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(MealPlanner::class)
            ->call('shiftWeek', 1)
            ->assertSet('week', '2026-11-16')
            ->assertSee('16 Nov – 22 Nov 2026')
            ->call('thisWeek')
            ->assertSet('week', '');
    }

    public function test_upcoming_menus_go_to_the_shopping_list_merged_and_scaled(): void
    {
        $user = User::factory()->create();
        $nasi = $this->recipe($user, 'Nasi Goreng', [[4, 'siung', 'bawang merah'], [2, 'butir', 'telur']]);
        $sayur = $this->recipe($user, 'Sayur Asem', [[5, 'siung', 'bawang merah']], servings: 4);
        $this->actingAs($user);

        // Yesterday is skipped; today and Friday count.
        $user->mealPlans()->create(['recipe_id' => $nasi->id, 'date' => '2026-11-10', 'slot' => MealSlot::Sarapan, 'servings' => 2]);
        $user->mealPlans()->create(['recipe_id' => $nasi->id, 'date' => '2026-11-11', 'slot' => MealSlot::Sarapan, 'servings' => 4]);
        $user->mealPlans()->create(['recipe_id' => $sayur->id, 'date' => '2026-11-13', 'slot' => MealSlot::MakanSiang, 'servings' => 4]);

        Livewire::test(MealPlanner::class)
            ->call('addToShoppingList')
            ->assertSet('message', '3 bahan dari 2 menu masuk daftar belanja.');

        $items = $user->shoppingListItems()->orderBy('id')->get();
        $this->assertSame(['13 siung bawang merah', '4 butir telur'], $items->map->line()->all());
        $this->assertSame(['Nasi Goreng', 'Sayur Asem'], $items[0]->sources);
    }

    public function test_dashboard_shows_todays_menu_and_planned_minutes(): void
    {
        $user = User::factory()->create();
        $recipe = $this->recipe($user, 'Ayam Bakar Kecap');
        $user->mealPlans()->create(['recipe_id' => $recipe->id, 'date' => '2026-11-11', 'slot' => MealSlot::MakanMalam, 'servings' => 4]);
        $user->mealPlans()->create(['recipe_id' => $recipe->id, 'date' => '2026-11-14', 'slot' => MealSlot::MakanSiang, 'servings' => 2]);
        $this->actingAs($user);

        $this->get('/beranda')->assertOk()
            ->assertSee('Menu hari ini')
            ->assertSee('Ayam Bakar Kecap')
            ->assertSee('Waktu masak minggu ini')
            ->assertSee('Direncanakan, makan siang')
            ->assertSee('30 menit direncanakan');
    }
}

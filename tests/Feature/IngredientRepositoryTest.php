<?php

namespace Tests\Feature;

use App\Contracts\IngredientRepositoryInterface;
use App\Models\Ingredient;
use App\Models\Product;
use App\Repositories\IngredientRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngredientRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected IngredientRepository $ingredientRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(IngredientRepositoryInterface::class);
    }

    public function test_it_finds_ingredients_by_ids_with_lock()
    {
        $ingredients = Ingredient::factory()->count(3)->create();

        $ids = [$ingredients[0]->id, $ingredients[2]->id];

        $result = $this->repository->findManyByIdsWithLock($ids);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $ingredients[0]->id));
        $this->assertTrue($result->contains('id', $ingredients[2]->id));
        $this->assertFalse($result->contains('id', $ingredients[1]->id));
    }

    public function test_it_updates_an_ingredient()
    {
        $ingredient = Ingredient::factory()->create([
            'name' => 'Old Name',
        ]);

        $ingredient->name       = 'Updated Name';
        $ingredient->stock      = 5000;
        $ingredient->alert_sent = true;

        $result = $this->repository->update($ingredient);

        $this->assertTrue($result);
        $this->assertDatabaseHas('ingredients', [
            'id'         => $ingredient->id,
            'name'       => 'Updated Name',
            'stock'      => 5000,
            'alert_sent' => true,
        ]);
    }
}

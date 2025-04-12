<?php

namespace Tests\Feature;

use App\Contracts\OrderRepositoryInterface;
use App\Contracts\ProductRepositoryInterface;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Repositories\ProductRepository;
use Database\Seeders\ProductWithIngredientsSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected ProductRepository $productRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(ProductRepositoryInterface::class);
    }

    public function test_it_finds_products_by_ids()
    {
        $products = Product::factory()->count(3)->create();

        $ids = [$products[0]->id, $products[2]->id];

        $found = $this->repository->findManyByIds($ids);

        $this->assertInstanceOf(Collection::class, $found);
        $this->assertCount(2, $found);
        $this->assertTrue($found->contains('id', $products[0]->id));
        $this->assertTrue($found->contains('id', $products[2]->id));
        $this->assertFalse($found->contains('id', $products[1]->id));
    }
}

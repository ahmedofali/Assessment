<?php

namespace Tests\Feature;

use App\Contracts\OrderRepositoryInterface;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\ProductWithIngredientsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProductWithIngredientsSeeder::class);

        User::factory()->create();

        $this->product    = Product::with('ingredients')->first();
        $this->repository = app(OrderRepositoryInterface::class);
    }

    public function test_it_creates_an_order()
    {
        $total = 5000;
        $data  = [
            'user_id' => User::query()->first()->id,
            'total'   => $total,
        ];

        $order = $this->repository->create($data);

        $this->assertDatabaseHas('orders', $data);
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($total, $order->total);
    }

    public function test_it_creates_line_items_for_order()
    {
        /** @var Order $order */
        $order    = Order::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $lineItems = [
            ['product_id' => $product1->id, 'quantity' => 2, 'total' => 100, 'unit_price' => 50],
            ['product_id' => $product2->id, 'quantity' => 1, 'total' => 50, 'unit_price' => 50],
        ];

        $this->repository->createLineItems($order->id, $lineItems);

        $this->assertCount(2, $order->lineItems()->get());
        $this->assertDatabaseHas('line_items', ['order_id' => $order->id, 'quantity' => 2]);
    }

    public function test_it_gets_order_with_line_items_and_ingredients()
    {
        /** @var Order $order */
        $ingredient = Ingredient::factory()->create();
        $product    = Product::factory()->create();
        $order      = Order::factory()->create();

        $product->ingredients()->attach([$ingredient->id => ['ingredient_quantity' => 150]]);

        $order->lineItems()->create([
            'product_id' => $product->id,
            'quantity'   => 1,
            'unit_price' => 100,
            'total'      => 100
        ]);

        $fetched = $this->repository->getWithLineItemsAndIngredients($order->id);

        $this->assertEquals($order->id, $fetched->id);
        $this->assertTrue($fetched->relationLoaded('lineItems'));
        $this->assertTrue($fetched->lineItems->first()->relationLoaded('product'));
        $this->assertTrue($fetched->lineItems->first()->product->relationLoaded('ingredients'));
        $this->assertEquals($ingredient->id, $fetched->lineItems->first()->product->ingredients->first()->id);
    }
}

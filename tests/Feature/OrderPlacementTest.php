<?php

namespace Tests\Feature;

use App\Enums\IngredientStockChangeReason;
use App\Jobs\IngredientStockLevelLowJob;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\ProductWithIngredientsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class OrderPlacementTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProductWithIngredientsSeeder::class);

        User::factory()->create();

        $this->product = Product::with('ingredients')->first();
    }

    public function test_order_creation_and_low_stock_job_dispatched()
    {
        Queue::fake();

        $this->createOrder($this->product->id, $this->getProductQuantityBelowThreshold())->assertStatus(200);

        // Check order was created
        $this->assertDatabaseCount('orders', 1);

        // Check order has line items
        $order = Order::query()->first();

        $this->assertEquals(1, $order->lineItems()->count());

        $this->assertGreaterThan(0, $this->getIngredientsSentAlertCount());

        // Check IngredientStockLevelLowJob was dispatched after commit
        Queue::assertPushed(IngredientStockLevelLowJob::class);
    }


    public function test_it_can_create_an_order_and_update_stock()
    {
        Queue::fake();

        $this->createOrder($this->product->id, 2)->assertStatus(200);

        // Check order was created
        $this->assertDatabaseCount('orders', 1);

        $this->assertDatabaseHas('line_items', [
            'product_id' => $this->product->id,
            'quantity'   => 2
        ]);

        $this->product->refresh();

        foreach ($this->product->ingredients as $ingredient) {
            $this->assertNotEquals($ingredient->stock, $ingredient->initial_stock);
        }

        Queue::assertNotPushed(IngredientStockLevelLowJob::class);
    }

    public function test_it_only_sends_alert_once_when_stock_is_below_threshold()
    {
        Queue::fake();

        // First order - triggers alert
        $this->createOrder($this->product->id, $this->getProductQuantityBelowThreshold())->assertStatus(200);

        // Second order - should not trigger alert again
        $this->createOrder($this->product->id, 1)->assertStatus(200);

        // ✅ Assert the alert was only sent once
        $this->assertGreaterThan(0, $this->getIngredientsSentAlertCount());

        Queue::assertPushed(IngredientStockLevelLowJob::class, 1);
    }

    public function test_it_records_ingredients_stock_movements()
    {
        $response = $this->createOrder($this->product->id, 2)->assertStatus(200);

        // Check order was created
        $this->assertDatabaseCount('orders', 1);

        $this->assertDatabaseHas('ingredient_stock_movements', [
            'ingredient_id'   => $this->product->ingredients->first()->id,
            'order_id'        => $response->json('id'),
            'movement_reason' => IngredientStockChangeReason::ORDER->value
        ]);
    }

    private function getIngredientsSentAlertCount(): int
    {
        return Ingredient::query()->where('alert_sent', true)->count();
    }

    private function getProductQuantityBelowThreshold(): int
    {
        $ingredient = $this->product->ingredients->sortBy('initial_stock')->first();

        $initialStock   = $ingredient->initial_stock;
        $threshold      = $initialStock * (config('ingredients.low_stock_threshold_percentage') / 100);
        $requiredAmount = ceil($initialStock - $threshold + 1) + 1; // Add small buffer

        return (int)ceil($requiredAmount / $ingredient->pivot->ingredient_quantity);
    }

    private function createOrder(int $productId, int $quantity): TestResponse
    {
        return $this->postJson('/api/orders', [
            'products' => [
                [
                    'product_id' => $productId,
                    'quantity'   => $quantity,
                ]
            ]
        ]);
    }
}

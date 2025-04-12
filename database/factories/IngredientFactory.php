<?php

namespace Database\Factories;

use App\Models\Ingredient;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $stock = $this->faker->numberBetween(100, 20000);

        return [
            'merchant_id'   => Merchant::factory(),
            'name'          => $this->faker->name(),
            'stock'         => $stock,
            'initial_stock' => $stock,
            'alert_sent'    => false,
        ];
    }

    /**
     * Indicate that the model is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'stock' => 0,
        ]);
    }
}

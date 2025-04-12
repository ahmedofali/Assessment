<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductWithIngredientsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create and index the ingredients by name
        $ingredients = Ingredient::factory()
            ->count(3)
            ->sequence(
                ['name' => 'Beef', 'stock' => 20000, 'initial_stock' => 20000],
                ['name' => 'Cheese', 'stock' => 5000, 'initial_stock' => 5000],
                ['name' => 'Onion', 'stock' => 1000, 'initial_stock' => 1000],
            )
            ->create()
            ->keyBy('name');

        // Create the product first
        $product = Product::factory()
            ->active()
            ->create(['name' => 'Burger']);

        // Attach the ingredients with individual pivot data
        $product->ingredients()->attach([
            $ingredients['Beef']->id   => ['ingredient_quantity' => 150],
            $ingredients['Cheese']->id => ['ingredient_quantity' => 30],
            $ingredients['Onion']->id  => ['ingredient_quantity' => 20],
        ]);
    }
}

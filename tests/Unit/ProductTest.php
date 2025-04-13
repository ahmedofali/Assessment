<?php

namespace Tests\Unit;

use App\Jobs\IngredientStockLevelLowJob;
use App\Mail\IngredientLowStockAlertMail;
use App\Models\Ingredient;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_total_price_correctly_for_given_quantity(): void
    {
        // not a factory so we don't on the database or any third-party
        $product = new Product(['price' => 19.99]);

        $total = $product->calculatePrice(3);

        $this->assertEquals(59.97, $total);
    }
}

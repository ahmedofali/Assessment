<?php

use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ingredient_product', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Product::class);
            $table->foreignIdFor(Ingredient::class);
            // The system will operate exclusively in "grams" to ensure a consistent and reliable foundation at the database level.
            // Ingredient quantity is the quantity consumed for each unit of product purchased
            $table->decimal('ingredient_quantity', 10);
            $table->timestamps();

            // only one record for product id, and ingredient id
            $table->unique(['product_id', 'ingredient_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_ingredient');
    }
};

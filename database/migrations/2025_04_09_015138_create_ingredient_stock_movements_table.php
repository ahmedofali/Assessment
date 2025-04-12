<?php

use App\Models\Ingredient;
use App\Models\Order;
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
        Schema::create('ingredient_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Ingredient::class);
            $table->foreignIdFor(Order::class)->nullable();

            // While using a database enum is possible here, it introduces friction when adding new types in the future,
            // as you'd need to alter the enum in the database. Instead, we enforce valid movement types at the application level,
            // which offers more flexibility and simplifies future changes.
            $table->string('movement_reason');

            // TODO: In the future, support manual stock adjustments by admins or other actors.
            // Authentication is not currently part of the system scope, so we're omitting the morphs relation for now.
            // $table->morphs('performed_by');

            $table->decimal('quantity', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_stock_movements');
    }
};

<?php

use App\Models\Merchant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Merchant::class);
            $table->string('name');

            // The system will operate exclusively in "grams" to ensure a consistent and reliable foundation at the database level.
            // This design decision enforces uniformity across all stock-related operations. Later, when an admin manually updates
            // the stock, a corresponding entry will be inserted into the stock_movements table for proper tracking and auditing.
            $table->decimal('stock', 10)->check('stock >= 0');

            $table->decimal('initial_stock', 10);

            // Indicates whether a low-stock alert has already been sent for this ingredient.
            $table->boolean('alert_sent')->default(false);

            $table->timestamps();
        });

        // I want to add extra layer of validation on the database level that the stock can't be less than 0
        DB::statement('ALTER TABLE ingredients ADD CONSTRAINT chk_stock_non_negative CHECK (stock >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};

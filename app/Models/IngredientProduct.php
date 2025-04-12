<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int product_id
 * @property int ingredient_id
 * @property float ingredient_quantity
 * @property Ingredient ingredient
 * @property Product product
 */
class IngredientProduct extends Pivot
{
    protected $fillable = [
        'product_id',
        'ingredient_id',
        'ingredient_quantity',
    ];

    protected $casts = [
        'ingredient_quantity' => 'float',
    ];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

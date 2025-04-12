<?php

namespace App\Models;

use App\Enums\IngredientStockChangeReason;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class IngredientStockMovement
 *
 * @property int $id
 * @property int $ingredient_id
 * @property float $quantity
 * @property IngredientStockChangeReason $movement_reason
 * @property int|null $order_id
 *
 * @property Ingredient $ingredient
 * @property \Illuminate\Database\Eloquent\Model $performedBy
 */
class IngredientStockMovement extends Model
{
    protected $fillable = [
        'ingredient_id',
        'order_id',
        'quantity',
        'movement_reason',
    ];

    protected $casts = [
        'quantity'        => 'float',
        'movement_reason' => IngredientStockChangeReason::class,
    ];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}

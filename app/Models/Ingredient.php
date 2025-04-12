<?php

namespace App\Models;

use Database\Factories\IngredientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property IngredientProduct $pivot
 * @property int $id
 * @property float $stock
 * @property float $initial_stock
 * @property string $name
 * @property boolean $alert_sent
 * @property Merchant $merchant
 */
class Ingredient extends Model
{
    /** @use HasFactory<IngredientFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'stock',
        'initial_stock',
        'alert_sent',
    ];

    protected $casts = [
        'initial_stock' => 'float',
        'stock'         => 'float',
        'alert_sent'    => 'boolean',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(IngredientStockMovement::class);
    }
}

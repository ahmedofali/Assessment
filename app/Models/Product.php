<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int id
 * @property string name
 * @property string description
 * @property float price
 * @property bool is_active
 * @property Collection|Ingredient[] $ingredients
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'float',
    ];

    public function ingredients(): BelongsToMany
    {
        return $this
            ->belongsToMany(Ingredient::class)
            ->using(IngredientProduct::class)
            ->withPivot(['ingredient_quantity'])
            ->withTimestamps();
    }
}

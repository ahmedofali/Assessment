<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property float total
 * @property Collection|OrderProduct[] $lineItems
 * @property int $id
 */
class Order extends Model
{
    protected $fillable = [
        'user_id',
        'total',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * It was seeded and named originally products but I thought it's better to change the name to line items
     * Of course if it's a rela project i will update the table to be lineItems instead of order products
     *
     * @return HasMany
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }
}

<?php

namespace App\Data\Storefront;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class StoreOrderProductData extends Data
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {
    }
}

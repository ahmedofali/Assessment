<?php

namespace App\Data\Storefront;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class StoreOrderProductsData extends Data
{
    public function __construct(
        /** @var Collection<int, StoreOrderProductData> */
        public array $products,
    ) {
    }
}

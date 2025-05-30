<?php

namespace App\Repositories;

use App\Contracts\ProductRepositoryInterface;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    public function findManyByIds(array $ids): Collection
    {
        return Product::query()->whereIn('id', $ids)->get();
    }
}

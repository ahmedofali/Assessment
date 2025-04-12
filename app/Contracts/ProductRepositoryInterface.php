<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    public function findManyByIds(array $ids): Collection;
}

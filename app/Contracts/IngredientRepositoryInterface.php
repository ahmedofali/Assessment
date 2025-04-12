<?php

namespace App\Contracts;

use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Collection;

interface IngredientRepositoryInterface
{
    public function findManyByIdsWithLock(array $ids): Collection;

    public function update(Ingredient $ingredient): bool;
}

<?php

namespace App\Repositories;

use App\Contracts\IngredientRepositoryInterface;
use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Collection;

class IngredientRepository implements IngredientRepositoryInterface
{
    /**
     * Find many ingredients by their IDs with lock for update
     *
     * @param array $ids
     * @return Collection
     */
    public function findManyByIdsWithLock(array $ids): Collection
    {
        return Ingredient::query()
            ->whereIn('id', $ids)
            ->lockForUpdate()
            ->get();
    }

    /**
     * Update an ingredient
     *
     * @param mixed $ingredient
     * @return bool
     */
    public function update($ingredient): bool
    {
        return $ingredient->save();
    }
}

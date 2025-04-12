<?php

namespace App\Services;

use App\Data\Storefront\StoreOrderProductsData;
use App\Enums\IngredientStockChangeReason;
use App\Exceptions\LogicalException;
use App\Jobs\IngredientStockLevelLowJob;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * I choose not to introduce a full REPOSITORY LAYER to avoid over-architecting the task
 * But I have structured my service in a way where the data access layer could be extracted int o a repository
 * With minimal effort if the project scales.
 */
class OrderService
{
    /**
     * @throws LogicalException|\Throwable
     */
    public function store(StoreOrderProductsData $data): Order
    {
        try {
            DB::beginTransaction();

            $order = $this->createOrder($data);

            $this->processIngredientsStockManagement($order);

            DB::commit();

            return $order;
        } catch (LogicalException $exception) {
            DB::rollBack();

            throw $exception;
        } catch (\Throwable $exception) {
            DB::rollBack();

            // Report to APM since this is an unexpected exception.
            // It may be caused by a stock-related issue (e.g., negative inventory from the database).
            // Later, we might catch this specific exception (QueryException) and prevent it from being reported to the APM.
            Log::debug($exception->getMessage());

            throw new LogicalException("Error happened please try again later");
        }
    }

    /**
     * @throws LogicalException
     */
    private function processIngredientsStockManagement(Order $order): void
    {
        $ingredientUsage = [];

        // Eager load everything in one go
        $order->load('lineItems.product.ingredients');

        foreach ($order->lineItems as $lineItem) {
            foreach ($lineItem->product->ingredients as $ingredient) {
                $amountNeeded = $ingredient->pivot->ingredient_quantity * $lineItem->quantity;

                $ingredientUsage[$ingredient->id] = ($ingredientUsage[$ingredient->id] ?? 0) + $amountNeeded;
            }
        }

        // Lock
        /** @var Collection|Ingredient[] $ingredients */
        $ingredients = Ingredient::query()->whereIn('id', array_keys($ingredientUsage))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($ingredientUsage as $ingredientId => $amount) {
            $ingredient                   = $ingredients[$ingredientId] ?? throw new LogicalException("Ingredient not found.");
            $ingredientStockOriginalValue = $ingredient->stock;

            if ($ingredient->stock < $amount) {
                throw new LogicalException("Not enough {$ingredient->name} in stock.");
            }

            // 1 - Check low stock threshold and dispatch event before save (after commit)
            $thresholdPercentage = config('ingredients.low_stock_threshold_percentage', 50);
            $thresholdValue      = $ingredient->initial_stock * ($thresholdPercentage / 100);
            $ingredient->stock   = $ingredient->stock - $amount;

            if (
                $ingredient->stock < $thresholdValue
                && ! $ingredient->alert_sent
                && $ingredientStockOriginalValue >= $thresholdValue
            ) {
                $ingredient->alert_sent = true;

                // Dispatch job AFTER flag is set to avoid race condition
                IngredientStockLevelLowJob::dispatch($ingredient->id)->afterCommit();
            }

            $ingredient->save();

            // 2 - Add stock movement record
            $ingredient->stockMovements()->create([
                'order_id'        => $order->id,
                'quantity'        => $amount,
                'movement_reason' => IngredientStockChangeReason::ORDER->value,
            ]);
        }
    }

    private function createOrder(StoreOrderProductsData $data): Order
    {
        // Use a fake user here in order to test the logic of the order creation process
        $user = User::query()->first();

        $productIds = collect($data->products)->pluck('productId');
        $products   = Product::query()->whereIn('id', $productIds->toArray())->get();
        $lineItems  = collect();

        foreach ($data->products as $item) {
            /** @var Product $product */
            $product  = $products->where('id', $item->productId)->first();
            $quantity = $item->quantity;

            $lineItems->push([
                'product_id' => $product->id,
                'quantity'   => $quantity,
                'unit_price' => $product->price,
                'total'      => $product->price * $quantity,
            ]);
        }

        /** @var Order $order */
        $order = $user->orders()->create([
            'total' => $lineItems->sum('total')
        ]);

        $order->lineItems()->createMany($lineItems);

        return $order;
    }
}

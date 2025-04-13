<?php

namespace App\Services;

use App\Contracts\IngredientRepositoryInterface;
use App\Contracts\OrderRepositoryInterface;
use App\Enums\IngredientStockChangeReason;
use App\Exceptions\LogicalException;
use App\Jobs\IngredientStockLevelLowJob;
use App\Models\Ingredient;
use App\Models\Order;

class StockManagementService implements StockManagementServiceInterface
{
    public function __construct(
        private readonly IngredientRepositoryInterface $ingredientRepository,
        private readonly OrderRepositoryInterface      $orderRepository,
    )
    {
    }

    /**
     * Process stock reductions for an order
     *
     * @param Order $order
     * @throws LogicalException
     */
    public function processOrderStock(Order $order): void
    {
        // Calculate ingredient usage
        $ingredientUsage = $this->calculateIngredientUsage($order);

        if (empty($ingredientUsage)) {
            return;
        }

        // Reduce stock
        $this->reduceIngredientsStock($order->id, $ingredientUsage);
    }

    /**
     * Calculate how much of each ingredient is needed for the order
     *
     * @param Order $order
     * @return array
     */
    private function calculateIngredientUsage(Order $order): array
    {
        $order           = $this->orderRepository->getWithLineItemsAndIngredients($order->id);
        $ingredientUsage = [];

        foreach ($order->lineItems as $lineItem) {
            foreach ($lineItem->product->ingredients as $ingredient) {
                $amountNeeded                     = $ingredient->pivot->ingredient_quantity * $lineItem->quantity;
                $ingredientUsage[$ingredient->id] = ($ingredientUsage[$ingredient->id] ?? 0) + $amountNeeded;
            }
        }

        return $ingredientUsage;
    }

    /**
     * Reduce ingredients stock based on usage
     *
     * @param int $orderId
     * @param array $ingredientUsage
     * @throws LogicalException
     */
    private function reduceIngredientsStock(int $orderId, array $ingredientUsage): void
    {
        // Lock and fetch ingredients
        $ingredients = $this->ingredientRepository->findManyByIdsWithLock(array_keys($ingredientUsage));

        foreach ($ingredientUsage as $ingredientId => $amount) {
            /** @var Ingredient $ingredient */
            $ingredient    = $ingredients->find($ingredientId) ?? throw new LogicalException("Ingredient not found.");
            $originalStock = $ingredient->stock;

            // Check stock availability
            $this->validateStockAvailability($ingredient, $amount);

            // Update stock
            $ingredient->stock -= $amount;

            // Check if stock is below threshold
            $this->checkLowStockThreshold($ingredient, $originalStock);

            // Save ingredient
            $this->ingredientRepository->update($ingredient);

            // Record stock movement
            $this->recordStockMovement($ingredient, $orderId, $amount);
        }
    }

    /**
     * Validate that enough stock is available
     *
     * @param mixed $ingredient
     * @param float $amount
     * @throws LogicalException
     */
    private function validateStockAvailability(Ingredient $ingredient, float $amount): void
    {
        if ($ingredient->stock < $amount) {
            throw new LogicalException("Not enough {$ingredient->name} in stock.");
        }
    }

    /**
     * Check if stock is below threshold and handle alerts
     * @TODO we can move this to observer
     *
     * @param mixed $ingredient
     * @param float $originalStock
     */
    private function checkLowStockThreshold(Ingredient $ingredient, float $originalStock): void
    {
        $thresholdValue = $this->calculateThresholdValue($ingredient);

        if (
            $ingredient->stock < $thresholdValue &&
            ! $ingredient->alert_sent &&
            $originalStock >= $thresholdValue
        ) {
            $ingredient->alert_sent = true;

            // Dispatch stock low event
            IngredientStockLevelLowJob::dispatch($ingredient->id)->afterCommit();
        }
    }

    /**
     * Calculate threshold value for an ingredient
     */
    private function calculateThresholdValue(Ingredient $ingredient): float
    {
        return $ingredient->initial_stock * getLowStockThresholdPercentage();
    }

    /**
     * Record a stock movement
     * @TODO we could move this to observer but observer will not know what is the movement reason we can depend on event driven architecture by dispatching event like IngredientStockReduction after commit
     */
    private function recordStockMovement(Ingredient $ingredient, int $orderId, float $amount): void
    {
        // we can create a repository here for stock movements
        $ingredient->stockMovements()->create([
            'order_id'        => $orderId,
            'quantity'        => $amount,
            'movement_reason' => IngredientStockChangeReason::ORDER->value,
        ]);
    }
}

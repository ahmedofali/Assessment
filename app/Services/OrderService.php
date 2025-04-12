<?php

namespace App\Services;

use App\Data\Storefront\StoreOrderData;
use App\Enums\IngredientStockChangeReason;
use App\Exceptions\LogicalException;
use App\Jobs\IngredientStockLevelLowJob;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Repositories\OrderRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * I choose not to introduce a full REPOSITORY LAYER to avoid over-architecting the task
 * But I have structured my service in a way where the data access layer could be extracted int o a repository
 * With minimal effort if the project scales.
 */
class OrderService
{
    public function __construct(private readonly OrderRepository $orderRepository)
    {
    }

    /**
     * @throws LogicalException|\Throwable
     */
    public function store(StoreOrderData $data): Order
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

            // should be translated
            throw new LogicalException("Error happened please try again later");
        }
    }

    /**
     * @throws LogicalException
     */
    private function processIngredientsStockManagement(Order $order): void
    {
        // Eager load everything in one go
        $order = $this->orderRepository->getWithLineItemsAndIngredients($order->id);

        // Get the ingredients and their usages in order to update them
        $ingredientUsage = $this->calculateOrderIngredientsUsage($order);

        // Lock
        /** @var Collection|Ingredient[] $ingredients */
        $ingredients = Ingredient::query()->whereIn('id', array_keys($ingredientUsage))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($ingredientUsage as $ingredientId => $amount) {
            $ingredient                   = $ingredients[$ingredientId] ?? throw new LogicalException("Ingredient not found.");
            $ingredientStockOriginalValue = $ingredient->stock;

            // Does the ingredient have enough stock?
            if ($ingredient->stock < $amount) {
                throw new LogicalException("Not enough {$ingredient->name} in stock.");
            }

            // Update the stock
            $ingredient->stock -= $amount;

            // 1 - Check low stock threshold and dispatch event before save (after commit)
            $this->checkAndHandleLowStockAlert($ingredient, $ingredientStockOriginalValue);

            $ingredient->save();

            // 2 - Add stock movement record
            $this->createStockMovementRecord($ingredient, $order, $amount);
        }
    }

    private function calculateOrderIngredientsUsage(Order $order): array
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

        return $ingredientUsage;
    }

    /**
     * Check if ingredient stock is below threshold and handle alert if needed.
     *
     * @param Ingredient $ingredient
     * @param float $originalStock
     * @return void
     */
    private function checkAndHandleLowStockAlert(Ingredient $ingredient, float $originalStock): void
    {
        $thresholdPercentage = config('ingredients.low_stock_threshold_percentage', 50);
        $thresholdValue      = $ingredient->initial_stock * ($thresholdPercentage / 100);

        if (
            $ingredient->stock < $thresholdValue &&
            ! $ingredient->alert_sent &&
            $originalStock >= $thresholdValue
        ) {
            $ingredient->alert_sent = true;

            // Queue job to be dispatched after transaction commits
            IngredientStockLevelLowJob::dispatch($ingredient->id)->afterCommit();
        }
    }

    private function createOrder(StoreOrderData $data): Order
    {
        // Use a fake user here in order to test the logic of the order creation process
        $user = User::query()->first();

        // Prepare Line items
        $lineItems = $this->prepareOrderLineItems($data);

        $order = $this->orderRepository->create([
            'user_id' => $user->id,
            'total'   => $lineItems->sum('total')
        ]);

        // Create line items
        $this->orderRepository->createLineItems(
            $order->id, $lineItems->toArray()
        );

        return $order;
    }

    private function prepareOrderLineItems(StoreOrderData $data): SupportCollection
    {
        $requestedProducts = collect($data->products);
        $products          = Product::query()->whereIn('id', $requestedProducts->pluck('productId'))->get();

        return collect($requestedProducts)->map(function ($item) use ($products) {
            /** @var Product $product */
            $product = $products->where('id', $item->productId)->first();

            return [
                'product_id' => $product->id,
                'quantity'   => $item->quantity,
                'unit_price' => $product->price,
                'total'      => $product->calculatePrice($item->quantity),
            ];
        });
    }

    private function createStockMovementRecord(Ingredient $ingredient, Order $order, float $amount): void
    {
        $ingredient->stockMovements()->create([
            'order_id'        => $order->id,
            'quantity'        => $amount,
            'movement_reason' => IngredientStockChangeReason::ORDER->value,
        ]);
    }
}

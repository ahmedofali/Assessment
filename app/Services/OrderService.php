<?php

namespace App\Services;

use App\Contracts\OrderRepositoryInterface;
use App\Contracts\ProductRepositoryInterface;
use App\Data\Storefront\StoreOrderData;
use App\Exceptions\LogicalException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * I choose not to introduce a full REPOSITORY LAYER to avoid over-architecting the task
 * But I have structured my service in a way where the data access layer could be extracted int o a repository
 * With minimal effort if the project scales.
 */
class OrderService implements OrderServiceInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface      $orderRepository,
        private readonly ProductRepositoryInterface    $productRepository,
        private readonly StockManagementServiceInterface $stockManagementService,
    )
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

            $this->stockManagementService->processOrderStock($order);

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
            Log::error($exception->getMessage());

            // should be translated
            throw new LogicalException("Error happened please try again later");
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

        // Here we may dispatch an event after commit OrderCreated and pass to it the order id

        return $order;
    }

    private function prepareOrderLineItems(StoreOrderData $data): SupportCollection
    {
        $requestedProducts = collect($data->products);
        $products          = $this->productRepository->findManyByIds($requestedProducts->pluck('productId')->toArray());

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
}

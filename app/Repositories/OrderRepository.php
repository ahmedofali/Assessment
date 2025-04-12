<?php

namespace App\Repositories;

use App\Contracts\OrderRepositoryInterface;
use App\Models\Order;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * Create a new order
     */
    public function create(array $data): Order
    {
        return Order::query()->create($data);
    }

    /**
     * Create line items for an order
     */
    public function createLineItems(int $orderId, array $lineItems): void
    {
        $order = Order::query()->findOrFail($orderId);

        $order->lineItems()->createMany($lineItems);
    }

    /**
     * Get order with line items and their product ingredients
     */
    public function getWithLineItemsAndIngredients(int $orderId): Order
    {
        return Order::with('lineItems.product.ingredients')->findOrFail($orderId);
    }
}

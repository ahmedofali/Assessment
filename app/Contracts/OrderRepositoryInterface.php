<?php

namespace App\Contracts;

use App\Models\Order;

interface OrderRepositoryInterface
{
    /**
     * Create a new order
     */
    public function create(array $data): Order;

    /**
     * Create line items for an order
     */
    public function createLineItems(int $orderId, array $lineItems): void;

    /**
     * Get order with line items and their product ingredients
     */
    public function getWithLineItemsAndIngredients(int $orderId);
}

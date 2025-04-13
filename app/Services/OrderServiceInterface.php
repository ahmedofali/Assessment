<?php

namespace App\Services;

use App\Data\Storefront\StoreOrderData;
use App\Models\Order;

/**
 * Since we might have many types of orders later we will rather depend on interface rather than concrete implementation
 */
interface OrderServiceInterface
{
    public function store(StoreOrderData $data): Order;
}

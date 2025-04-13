<?php

namespace App\Services;

use App\Models\Order;

interface StockManagementServiceInterface
{
    public function processOrderStock(Order $order): void;
}

<?php

namespace App\Http\Controllers\Storefront;

use App\Data\Storefront\StoreOrderData;
use App\Exceptions\LogicalException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(public OrderService $orderService)
    {
    }

    /**
     * @throws \Throwable
     * @throws LogicalException
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $data = StoreOrderData::from($request->validated());

        $order = $this->orderService->store($data);

        return response()->json($order);
    }
}

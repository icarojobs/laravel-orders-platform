<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Orders\Queries\OrderSearch;
use App\Domain\Orders\Services\PlaceOrderService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Orders\ListOrdersRequest;
use App\Http\Requests\Api\Orders\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function index(ListOrdersRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Order::class);

        $orders = OrderSearch::fromArray($request->validated())
            ->paginate($request->integer('per_page', 15));

        return OrderResource::collection($orders);
    }

    public function store(StoreOrderRequest $request, PlaceOrderService $placeOrder): JsonResponse
    {
        $order = $placeOrder->handle($request->toData());

        return OrderResource::make($order)->response()->setStatusCode(201);
    }

    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        return OrderResource::make($order);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Orders\Services\OrderStatusService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Orders\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Support\Facades\Gate;

class OrderStatusController extends Controller
{
    public function __invoke(UpdateOrderStatusRequest $request, Order $order, OrderStatusService $statuses): OrderResource
    {
        Gate::authorize('updateStatus', [$order, $request->target()]);

        return OrderResource::make($statuses->apply($order, $request->target()));
    }
}

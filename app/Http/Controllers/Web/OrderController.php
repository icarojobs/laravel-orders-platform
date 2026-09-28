<?php

namespace App\Http\Controllers\Web;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Queries\OrderSearch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Orders\ListOrdersRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(ListOrdersRequest $request): Response
    {
        Gate::authorize('viewAny', Order::class);

        $orders = OrderSearch::fromArray($request->validated())->paginate(20);

        return Inertia::render('orders/index', [
            'orders' => OrderResource::collection($orders),
            'filters' => $request->only(['status', 'search', 'from', 'to']),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        Gate::authorize('view', $order);

        $user = $request->user();
        $transitions = array_values(array_filter(
            $order->status->allowedTransitions(),
            fn (OrderStatus $target) => $user?->can('updateStatus', [$order, $target]) ?? false,
        ));

        return Inertia::render('orders/show', [
            'order' => OrderResource::make($order->load(['customer', 'items.product'])),
            'transitions' => array_map(fn (OrderStatus $status) => ['value' => $status->value, 'label' => $status->label()], $transitions),
        ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (OrderStatus $status) => ['value' => $status->value, 'label' => $status->label()],
            OrderStatus::cases(),
        );
    }
}

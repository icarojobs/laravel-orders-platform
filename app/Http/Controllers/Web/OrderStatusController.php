<?php

namespace App\Http\Controllers\Web;

use App\Domain\Orders\Exceptions\InvalidOrderTransition;
use App\Domain\Orders\Services\OrderStatusService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Orders\UpdateOrderStatusRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class OrderStatusController extends Controller
{
    public function __invoke(UpdateOrderStatusRequest $request, Order $order, OrderStatusService $statuses): RedirectResponse
    {
        Gate::authorize('updateStatus', [$order, $request->target()]);

        try {
            $statuses->apply($order, $request->target());
            Inertia::flash('toast', ['type' => 'success', 'message' => "Pedido {$order->number} atualizado para {$order->status->label()}."]);
        } catch (InvalidOrderTransition) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Essa mudança de status não é permitida.']);
        }

        return back();
    }
}

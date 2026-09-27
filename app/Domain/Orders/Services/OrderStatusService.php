<?php

namespace App\Domain\Orders\Services;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Events\OrderShipped;
use App\Domain\Orders\Exceptions\InvalidOrderTransition;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\DatabaseManager;

class OrderStatusService
{
    public function __construct(private readonly DatabaseManager $db) {}

    public function pay(Order $order): Order
    {
        $order->transitionTo(OrderStatus::Paid);

        return $order;
    }

    public function ship(Order $order): Order
    {
        $order->transitionTo(OrderStatus::Shipped);

        OrderShipped::dispatch($order);

        return $order;
    }

    public function deliver(Order $order): Order
    {
        $order->transitionTo(OrderStatus::Delivered);

        return $order;
    }

    public function cancel(Order $order): Order
    {
        $this->db->transaction(function () use ($order) {
            $order->transitionTo(OrderStatus::Cancelled);

            foreach ($order->items()->get(['product_id', 'quantity']) as $item) {
                Product::query()->whereKey($item->product_id)->increment('stock', $item->quantity);
            }
        });

        return $order;
    }

    public function apply(Order $order, OrderStatus $target): Order
    {
        return match ($target) {
            OrderStatus::Paid => $this->pay($order),
            OrderStatus::Shipped => $this->ship($order),
            OrderStatus::Delivered => $this->deliver($order),
            OrderStatus::Cancelled => $this->cancel($order),
            OrderStatus::Pending => throw InvalidOrderTransition::between($order->status, $target),
        };
    }
}

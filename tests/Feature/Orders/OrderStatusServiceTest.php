<?php

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Events\OrderShipped;
use App\Domain\Orders\Exceptions\InvalidOrderTransition;
use App\Domain\Orders\Services\OrderStatusService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake([OrderShipped::class]);
    $this->service = app(OrderStatusService::class);
});

it('pays, ships and delivers an order', function () {
    $order = Order::factory()->create();

    $this->service->apply($order, OrderStatus::Paid);
    $this->service->apply($order, OrderStatus::Shipped);
    $this->service->apply($order, OrderStatus::Delivered);

    expect($order->fresh()->status)->toBe(OrderStatus::Delivered);
    Event::assertDispatchedTimes(OrderShipped::class, 1);
});

it('gives the stock back when an order is cancelled', function () {
    $product = Product::factory()->create(['stock' => 4]);
    $order = Order::factory()->status(OrderStatus::Paid)->create();
    OrderItem::factory()->for($order)->for($product)->create(['quantity' => 3]);

    $this->service->cancel($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($order->fresh()->cancelled_at)->not->toBeNull()
        ->and($product->fresh()->stock)->toBe(7);
});

it('does not ship pending orders', function () {
    $this->service->ship(Order::factory()->create());
})->throws(InvalidOrderTransition::class);

it('never moves an order back to pending', function () {
    $this->service->apply(Order::factory()->status(OrderStatus::Paid)->create(), OrderStatus::Pending);
})->throws(InvalidOrderTransition::class);

<?php

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Exceptions\InvalidOrderTransition;
use App\Models\Order;
use App\Models\Product;
use Database\Seeders\OrderSeeder;

it('moves through its lifecycle and stamps each step', function () {
    $order = Order::factory()->create();

    $order->transitionTo(OrderStatus::Paid);
    $order->transitionTo(OrderStatus::Shipped);
    $order->transitionTo(OrderStatus::Delivered);

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Delivered)
        ->and($order->paid_at)->not->toBeNull()
        ->and($order->shipped_at)->not->toBeNull()
        ->and($order->delivered_at)->not->toBeNull()
        ->and($order->cancelled_at)->toBeNull();
});

it('refuses to ship an order that was not paid', function () {
    $order = Order::factory()->create();

    $order->transitionTo(OrderStatus::Shipped);
})->throws(InvalidOrderTransition::class, 'Order cannot move from [pending] to [shipped].');

it('keeps the status untouched when a transition fails', function () {
    $order = Order::factory()->status(OrderStatus::Cancelled)->create();

    try {
        $order->transitionTo(OrderStatus::Paid);
    } catch (InvalidOrderTransition) {
    }

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});

it('sums its items into the total', function () {
    $order = Order::factory()->withItems(3)->create();

    expect($order->fresh()->total_cents)
        ->toBe((int) $order->items()->sum('total_cents'))
        ->toBeGreaterThan(0);
});

it('filters orders by status', function () {
    Order::factory()->count(2)->status(OrderStatus::Paid)->create();
    Order::factory()->create();

    expect(Order::query()->withStatus(OrderStatus::Paid)->count())->toBe(2);
});

it('checks product stock', function () {
    $product = Product::factory()->create(['stock' => 3]);

    expect($product->hasStockFor(3))->toBeTrue()
        ->and($product->hasStockFor(4))->toBeFalse();
});

it('seeds orders with items and consistent totals', function () {
    putenv('SEED_ORDERS=20');
    $this->seed(OrderSeeder::class);
    putenv('SEED_ORDERS');

    $order = Order::query()->with('items')->firstOrFail();

    expect(Order::count())->toBe(20)
        ->and($order->items)->not->toBeEmpty()
        ->and($order->total_cents)->toBe($order->items->sum('total_cents'));
});

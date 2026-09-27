<?php

use App\Domain\Orders\Data\OrderItemData;
use App\Domain\Orders\Data\PlaceOrderData;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Events\OrderPlaced;
use App\Domain\Orders\Exceptions\InsufficientStock;
use App\Domain\Orders\Services\PlaceOrderService;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake([OrderPlaced::class]);
    $this->service = app(PlaceOrderService::class);
    $this->customer = Customer::factory()->create();
});

it('places an order, prices the items and reserves stock', function () {
    $keyboard = Product::factory()->create(['price_cents' => 15_000, 'stock' => 10]);
    $mouse = Product::factory()->create(['price_cents' => 5_000, 'stock' => 5]);

    $order = $this->service->handle(new PlaceOrderData($this->customer->id, [
        new OrderItemData($keyboard->id, 2),
        new OrderItemData($mouse->id, 1),
    ]));

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->number)->toMatch('/^ORD-\d{6}-[A-Z0-9]{6}$/')
        ->and($order->total_cents)->toBe(35_000)
        ->and($order->items)->toHaveCount(2)
        ->and($keyboard->fresh()->stock)->toBe(8)
        ->and($mouse->fresh()->stock)->toBe(4);

    Event::assertDispatched(OrderPlaced::class, fn (OrderPlaced $event) => $event->order->is($order));
});

it('does not persist anything when stock is short', function () {
    $available = Product::factory()->create(['stock' => 10]);
    $scarce = Product::factory()->create(['stock' => 1]);

    expect(fn () => $this->service->handle(new PlaceOrderData($this->customer->id, [
        new OrderItemData($available->id, 1),
        new OrderItemData($scarce->id, 2),
    ])))->toThrow(InsufficientStock::class);

    expect(Order::count())->toBe(0)
        ->and($available->fresh()->stock)->toBe(10);

    Event::assertNotDispatched(OrderPlaced::class);
});

it('fails for unknown products', function () {
    $this->service->handle(new PlaceOrderData($this->customer->id, [new OrderItemData(999, 1)]));
})->throws(ModelNotFoundException::class);

<?php

use App\Domain\Orders\Contracts\OrderEventPublisher;
use App\Domain\Orders\Data\OrderItemData;
use App\Domain\Orders\Data\PlaceOrderData;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Services\OrderStatusService;
use App\Domain\Orders\Services\PlaceOrderService;
use App\Infrastructure\Messaging\LogOrderEventPublisher;
use App\Infrastructure\Messaging\RabbitMqOrderEventPublisher;
use App\Listeners\PublishOrderEvent;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Queue;
use Psr\Log\LoggerInterface;

class SpyPublisher implements OrderEventPublisher
{
    /** @var list<array{0: string, 1: array<string, mixed>}> */
    public array $published = [];

    public function publish(string $routingKey, array $payload): void
    {
        $this->published[] = [$routingKey, $payload];
    }
}

beforeEach(function () {
    $this->publisher = new SpyPublisher;
    $this->app->instance(OrderEventPublisher::class, $this->publisher);
});

it('queues the broker publication on the events queue', function () {
    Queue::fake();

    app(PlaceOrderService::class)->handle(new PlaceOrderData(
        Customer::factory()->create()->id,
        [new OrderItemData(Product::factory()->create(['stock' => 5])->id, 1)],
    ));

    Queue::assertPushedOn('events', CallQueuedListener::class, fn (CallQueuedListener $job) => $job->class === PublishOrderEvent::class);
});

it('publishes order.placed with the shared contract', function () {
    $customer = Customer::factory()->create(['name' => 'Acme', 'email' => 'compras@acme.test']);
    $product = Product::factory()->create(['price_cents' => 1_000, 'stock' => 5]);

    $order = app(PlaceOrderService::class)->handle(new PlaceOrderData($customer->id, [new OrderItemData($product->id, 3)]));

    expect($this->publisher->published)->toHaveCount(1);
    [$routingKey, $payload] = $this->publisher->published[0];

    expect($routingKey)->toBe('order.placed')
        ->and($payload)->toMatchArray(['event' => 'order.placed', 'version' => 1])
        ->and($payload['event_id'])->toBeString()->toHaveLength(36)
        ->and($payload['order'])->toBe([
            'id' => $order->id,
            'number' => $order->number,
            'status' => 'pending',
            'total_cents' => 3_000,
            'items_count' => 1,
            'customer' => ['name' => 'Acme', 'email' => 'compras@acme.test'],
        ]);
});

it('publishes order.shipped when an order leaves the warehouse', function () {
    $order = Order::factory()->status(OrderStatus::Paid)->withItems()->create();

    app(OrderStatusService::class)->ship($order);

    expect($this->publisher->published)->toHaveCount(1)
        ->and($this->publisher->published[0][0])->toBe('order.shipped')
        ->and($this->publisher->published[0][1]['order']['status'])->toBe('shipped');
});

it('does not publish for other transitions', function () {
    app(OrderStatusService::class)->pay(Order::factory()->create());

    expect($this->publisher->published)->toBeEmpty();
});

it('retries with backoff', function () {
    $listener = new PublishOrderEvent(new SpyPublisher);

    expect($listener->queue)->toBe('events')
        ->and($listener->tries)->toBe(5)
        ->and($listener->backoff)->toBe([1, 5, 15, 60]);
});

it('resolves the publisher from config', function () {
    $this->app->forgetInstance(OrderEventPublisher::class);

    config(['messaging.driver' => 'log']);
    expect(app(OrderEventPublisher::class))->toBeInstanceOf(LogOrderEventPublisher::class);

    $this->app->forgetInstance(OrderEventPublisher::class);
    config(['messaging.driver' => 'rabbitmq']);
    expect(app(OrderEventPublisher::class))->toBeInstanceOf(RabbitMqOrderEventPublisher::class);
});

it('logs events when the log driver is used', function () {
    $logger = Mockery::spy(LoggerInterface::class);

    (new LogOrderEventPublisher($logger))->publish('order.placed', ['event_id' => 'abc']);

    $logger->shouldHaveReceived('info')->with('Order event [order.placed]', ['event_id' => 'abc']);
});

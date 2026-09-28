<?php

namespace App\Listeners;

use App\Domain\Orders\Contracts\OrderEventPublisher;
use App\Domain\Orders\Events\OrderPlaced;
use App\Domain\Orders\Events\OrderShipped;
use App\Infrastructure\Messaging\OrderEventMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

class PublishOrderEvent implements ShouldQueue
{
    public string $queue = 'events';

    public int $tries = 5;

    /**
     * @var list<int>
     */
    public array $backoff = [1, 5, 15, 60];

    public function __construct(private readonly OrderEventPublisher $publisher) {}

    public function handle(OrderPlaced|OrderShipped $event): void
    {
        $routingKey = match (true) {
            $event instanceof OrderPlaced => 'order.placed',
            $event instanceof OrderShipped => 'order.shipped',
        };

        $this->publisher->publish($routingKey, OrderEventMessage::from($routingKey, $event->order));
    }
}

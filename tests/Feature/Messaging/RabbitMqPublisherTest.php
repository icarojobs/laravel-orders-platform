<?php

use App\Infrastructure\Messaging\RabbitMqOrderEventPublisher;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;

it('delivers messages to queues bound to the exchange', function () {
    $config = config('messaging.rabbitmq');
    $exchange = 'orders.events.test';

    $connection = new AMQPStreamConnection($config['host'], $config['port'], $config['user'], $config['password'], $config['vhost']);
    $channel = $connection->channel();
    $channel->exchange_declare($exchange, AMQPExchangeType::TOPIC, durable: true, auto_delete: false);
    [$queue] = $channel->queue_declare('', exclusive: true);
    $channel->queue_bind($queue, $exchange, 'order.*');

    (new RabbitMqOrderEventPublisher([...$config, 'exchange' => $exchange]))
        ->publish('order.placed', ['event_id' => 'f1b1c1d1', 'event' => 'order.placed']);

    $message = null;
    for ($attempt = 0; $attempt < 20 && $message === null; $attempt++) {
        $message = $channel->basic_get($queue, no_ack: true);
        $message ?? usleep(50_000);
    }

    expect($message)->not->toBeNull()
        ->and(json_decode($message->getBody(), true))->toBe(['event_id' => 'f1b1c1d1', 'event' => 'order.placed'])
        ->and($message->get('message_id'))->toBe('f1b1c1d1')
        ->and($message->getRoutingKey())->toBe('order.placed');

    $channel->exchange_delete($exchange);
    $channel->close();
    $connection->close();
})->group('integration')->skip(function () {
    $connection = @fsockopen(config('messaging.rabbitmq.host'), config('messaging.rabbitmq.port'), timeout: 0.5);

    return $connection === false;
}, 'RabbitMQ is not reachable.');

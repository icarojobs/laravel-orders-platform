<?php

namespace App\Infrastructure\Messaging;

use App\Domain\Orders\Contracts\OrderEventPublisher;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMqOrderEventPublisher implements OrderEventPublisher
{
    private ?AMQPStreamConnection $connection = null;

    private ?AMQPChannel $channel = null;

    /**
     * @param  array{host: string, port: int, user: string, password: string, vhost: string, exchange: string}  $config
     */
    public function __construct(private readonly array $config) {}

    public function publish(string $routingKey, array $payload): void
    {
        $message = new AMQPMessage((string) json_encode($payload, JSON_THROW_ON_ERROR), [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'message_id' => $payload['event_id'] ?? null,
            'type' => $routingKey,
            'timestamp' => time(),
        ]);

        $this->channel()->basic_publish($message, $this->config['exchange'], $routingKey);
    }

    private function channel(): AMQPChannel
    {
        if ($this->channel?->is_open()) {
            return $this->channel;
        }

        $this->connection = new AMQPStreamConnection(
            $this->config['host'],
            $this->config['port'],
            $this->config['user'],
            $this->config['password'],
            $this->config['vhost'],
        );

        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare($this->config['exchange'], AMQPExchangeType::TOPIC, durable: true, auto_delete: false);

        return $this->channel;
    }

    public function __destruct()
    {
        $this->channel?->close();
        $this->connection?->close();
    }
}

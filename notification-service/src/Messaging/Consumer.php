<?php

declare(strict_types=1);

namespace NotificationService\Messaging;

use NotificationService\Config;
use NotificationService\Dispatcher;
use NotificationService\Message\InvalidMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerInterface;
use Throwable;

final class Consumer
{
    public const ROUTING_KEYS = ['order.placed', 'order.shipped'];

    private bool $running = true;

    public function __construct(
        private readonly AMQPChannel $channel,
        private readonly Dispatcher $dispatcher,
        private readonly Config $config,
        private readonly LoggerInterface $logger,
    ) {}

    public function declareTopology(): void
    {
        $deadLetterExchange = $this->config->exchange.'.dlx';
        $deadLetterQueue = $this->config->queue.'.dlq';

        $this->channel->exchange_declare($this->config->exchange, AMQPExchangeType::TOPIC, durable: true, auto_delete: false);
        $this->channel->exchange_declare($deadLetterExchange, AMQPExchangeType::FANOUT, durable: true, auto_delete: false);

        $this->channel->queue_declare($deadLetterQueue, durable: true, auto_delete: false);
        $this->channel->queue_bind($deadLetterQueue, $deadLetterExchange);

        $this->channel->queue_declare($this->config->queue, durable: true, auto_delete: false, arguments: new AMQPTable([
            'x-dead-letter-exchange' => $deadLetterExchange,
        ]));

        foreach (self::ROUTING_KEYS as $routingKey) {
            $this->channel->queue_bind($this->config->queue, $this->config->exchange, $routingKey);
        }
    }

    public function run(): void
    {
        $this->channel->basic_qos(0, $this->config->prefetch, false);
        $this->channel->basic_consume($this->config->queue, callback: $this->handle(...));

        $this->logger->info('Consuming', ['queue' => $this->config->queue]);

        while ($this->running && $this->channel->is_consuming()) {
            $this->heartbeat();

            try {
                $this->channel->wait(timeout: 5);
            } catch (AMQPTimeoutException) {
                // idle, loop again to refresh the heartbeat
            }
        }
    }

    public function stop(): void
    {
        $this->running = false;
    }

    public function handle(AMQPMessage $message): void
    {
        try {
            $result = $this->dispatcher->dispatch($message->getBody());
            $message->ack();

            $this->logger->debug('Message processed', ['result' => $result->name]);
        } catch (InvalidMessage $e) {
            $this->logger->warning('Rejecting invalid message', ['reason' => $e->getMessage()]);
            $message->reject(requeue: false);
        } catch (Throwable $e) {
            $this->logger->error('Failed to process message', ['exception' => $e->getMessage()]);
            $message->nack(requeue: ! $message->isRedelivered());
        }
    }

    private function heartbeat(): void
    {
        @touch($this->config->heartbeatFile);
    }
}

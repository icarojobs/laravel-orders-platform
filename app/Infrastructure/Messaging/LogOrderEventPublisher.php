<?php

namespace App\Infrastructure\Messaging;

use App\Domain\Orders\Contracts\OrderEventPublisher;
use Psr\Log\LoggerInterface;

class LogOrderEventPublisher implements OrderEventPublisher
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function publish(string $routingKey, array $payload): void
    {
        $this->logger->info("Order event [{$routingKey}]", $payload);
    }
}

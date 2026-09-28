<?php

namespace App\Domain\Orders\Contracts;

interface OrderEventPublisher
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function publish(string $routingKey, array $payload): void;
}

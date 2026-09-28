<?php

declare(strict_types=1);

namespace NotificationService;

final readonly class Config
{
    public function __construct(
        public string $amqpHost,
        public int $amqpPort,
        public string $amqpUser,
        public string $amqpPassword,
        public string $amqpVhost,
        public string $exchange,
        public string $queue,
        public string $dbHost,
        public int $dbPort,
        public string $dbName,
        public string $dbUser,
        public string $dbPassword,
        public int $prefetch,
        public string $heartbeatFile,
    ) {}

    /**
     * @param  array<string, string|false>  $env
     */
    public static function fromEnv(array $env): self
    {
        $get = fn (string $key, string $default): string => is_string($env[$key] ?? null) && $env[$key] !== '' ? $env[$key] : $default;

        return new self(
            amqpHost: $get('RABBITMQ_HOST', '127.0.0.1'),
            amqpPort: (int) $get('RABBITMQ_PORT', '5672'),
            amqpUser: $get('RABBITMQ_USER', 'guest'),
            amqpPassword: $get('RABBITMQ_PASSWORD', 'guest'),
            amqpVhost: $get('RABBITMQ_VHOST', '/'),
            exchange: $get('RABBITMQ_EXCHANGE', 'orders.events'),
            queue: $get('RABBITMQ_QUEUE', 'notifications.order-events'),
            dbHost: $get('DB_HOST', '127.0.0.1'),
            dbPort: (int) $get('DB_PORT', '5432'),
            dbName: $get('DB_DATABASE', 'notifications'),
            dbUser: $get('DB_USERNAME', 'postgres'),
            dbPassword: $get('DB_PASSWORD', ''),
            prefetch: (int) $get('PREFETCH', '10'),
            heartbeatFile: $get('HEARTBEAT_FILE', '/tmp/notification-service.heartbeat'),
        );
    }
}

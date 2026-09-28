<?php

declare(strict_types=1);

namespace NotificationService\Tests\Unit;

use NotificationService\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function test_it_uses_defaults(): void
    {
        $config = Config::fromEnv([]);

        self::assertSame('orders.events', $config->exchange);
        self::assertSame('notifications.order-events', $config->queue);
        self::assertSame(5672, $config->amqpPort);
        self::assertSame(10, $config->prefetch);
    }

    public function test_it_reads_the_environment(): void
    {
        $config = Config::fromEnv(['RABBITMQ_HOST' => 'rabbitmq', 'DB_PORT' => '6543', 'PREFETCH' => '', 'DB_DATABASE' => false]);

        self::assertSame('rabbitmq', $config->amqpHost);
        self::assertSame(6543, $config->dbPort);
        self::assertSame(10, $config->prefetch);
        self::assertSame('notifications', $config->dbName);
    }
}

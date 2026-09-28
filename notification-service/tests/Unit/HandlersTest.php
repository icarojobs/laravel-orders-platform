<?php

declare(strict_types=1);

namespace NotificationService\Tests\Unit;

use NotificationService\Handlers\OrderPlacedHandler;
use NotificationService\Handlers\OrderShippedHandler;
use NotificationService\Message\OrderEvent;
use PHPUnit\Framework\TestCase;

final class HandlersTest extends TestCase
{
    public function test_order_placed_notification(): void
    {
        $notification = (new OrderPlacedHandler)->notificationFor(OrderEvent::fromJson(Fixtures::payload()));

        self::assertSame('maria@example.com', $notification->recipient);
        self::assertSame('Recebemos o seu pedido ORD-260310-ABC123', $notification->subject);
        self::assertStringContainsString('3 item(ns) no valor de R$ 1.234,56', $notification->body);
    }

    public function test_order_shipped_notification(): void
    {
        $event = OrderEvent::fromJson(Fixtures::payload(['event' => 'order.shipped', 'order' => ['status' => 'shipped']]));

        $notification = (new OrderShippedHandler)->notificationFor($event);

        self::assertSame('order.shipped', $notification->type);
        self::assertSame('Seu pedido ORD-260310-ABC123 foi enviado', $notification->subject);
        self::assertStringContainsString('saiu para entrega em 10/03/2026 14:30', $notification->body);
    }
}

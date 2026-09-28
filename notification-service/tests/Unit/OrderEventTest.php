<?php

declare(strict_types=1);

namespace NotificationService\Tests\Unit;

use NotificationService\Message\InvalidMessage;
use NotificationService\Message\OrderEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OrderEventTest extends TestCase
{
    public function test_it_parses_a_valid_message(): void
    {
        $event = OrderEvent::fromJson(Fixtures::payload());

        self::assertSame('order.placed', $event->event);
        self::assertSame(42, $event->orderId);
        self::assertSame('ORD-260310-ABC123', $event->orderNumber);
        self::assertSame(123_456, $event->totalCents);
        self::assertSame('maria@example.com', $event->customerEmail);
        self::assertSame('2026-03-10 14:30', $event->occurredAt->format('Y-m-d H:i'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidMessages(): iterable
    {
        yield 'not json' => ['{oops'];
        yield 'not an object' => ['"text"'];
        yield 'unknown version' => [Fixtures::payload(['version' => 2])];
        yield 'missing order' => [json_encode(['event_id' => 'x', 'event' => 'order.placed', 'version' => 1], JSON_THROW_ON_ERROR)];
        yield 'invalid email' => [Fixtures::payload(['order' => ['customer' => ['email' => 'nope']]])];
        yield 'string total' => [Fixtures::payload(['order' => ['total_cents' => '10']])];
        yield 'empty number' => [Fixtures::payload(['order' => ['number' => '']])];
        yield 'bad date' => [Fixtures::payload(['occurred_at' => 'yesterday-ish'])];
    }

    #[DataProvider('invalidMessages')]
    public function test_it_rejects_invalid_messages(string $body): void
    {
        $this->expectException(InvalidMessage::class);

        OrderEvent::fromJson($body);
    }
}

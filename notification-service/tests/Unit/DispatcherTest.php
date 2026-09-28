<?php

declare(strict_types=1);

namespace NotificationService\Tests\Unit;

use NotificationService\Dispatcher;
use NotificationService\DispatchResult;
use NotificationService\Handlers\OrderPlacedHandler;
use NotificationService\Handlers\OrderShippedHandler;
use NotificationService\Message\InvalidMessage;
use NotificationService\Notification;
use NotificationService\Sender\NotificationSender;
use NotificationService\Store\InMemoryNotificationStore;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class DispatcherTest extends TestCase
{
    private InMemoryNotificationStore $store;

    /** @var list<Notification> */
    private array $sent = [];

    private Dispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->store = new InMemoryNotificationStore;
        $sent = &$this->sent;
        $sender = new class($sent) implements NotificationSender
        {
            /** @param list<Notification> $sent */
            public function __construct(private array &$sent) {}

            public function send(Notification $notification): void
            {
                $this->sent[] = $notification;
            }
        };

        $this->dispatcher = new Dispatcher([new OrderPlacedHandler, new OrderShippedHandler], $this->store, $sender, new NullLogger);
    }

    public function test_it_stores_and_sends_a_notification(): void
    {
        self::assertSame(DispatchResult::Sent, $this->dispatcher->dispatch(Fixtures::payload()));
        self::assertCount(1, $this->store->notifications);
        self::assertCount(1, $this->sent);
    }

    public function test_redelivered_events_are_sent_only_once(): void
    {
        $this->dispatcher->dispatch(Fixtures::payload());

        self::assertSame(DispatchResult::Duplicate, $this->dispatcher->dispatch(Fixtures::payload()));
        self::assertCount(1, $this->sent);
    }

    public function test_events_without_handler_are_ignored(): void
    {
        self::assertSame(DispatchResult::Ignored, $this->dispatcher->dispatch(Fixtures::payload(['event' => 'order.delivered'])));
        self::assertSame([], $this->sent);
    }

    public function test_invalid_messages_bubble_up(): void
    {
        $this->expectException(InvalidMessage::class);

        $this->dispatcher->dispatch('not json');
    }
}
